<?php

namespace App\Services\Analysis;

use App\Models\Product;
use App\Models\SmartInsight;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * =========================================================================
 * PRODUCT ASSOCIATION ANALYZER (Simplified Apriori / Co-Purchase Mining)
 * =========================================================================
 * Menganalisa ribuan transaksi (receipt/nota) untuk menemukan pasangan produk
 * yang sering dibeli bersamaan. Berbasis konsep Market Basket Analysis.
 *
 * Key Metrics:
 * - Support: Seberapa sering pasangan muncul dibanding total transaksi
 *   Support(A→B) = Transaksi mengandung A&B / Total Transaksi
 *
 * - Confidence: Dari semua orang beli A, berapa % yang juga beli B?
 *   Confidence(A→B) = Transaksi mengandung A&B / Transaksi mengandung A
 *
 * - Lift: Apakah hubungan A→B lebih dari kebetulan?
 *   Lift(A→B) = Confidence(A→B) / Support(B)
 *   Lift > 1.0 = ada korelasi positif, Lift > 1.5 = korelasi kuat
 *
 * Algoritma yang digunakan: Simplified Apriori (fokus pada pasangan/pairs)
 * untuk performa cepat tanpa overhead full itemset generation.
 */
class ProductAssociationAnalyzer
{
    // Threshold minimum
    private float $minSupport    = 0.01;  // Setidaknya 1% dari total transaksi
    private float $minConfidence = 0.25;  // Minimal 25% customer A juga beli B
    private float $minLift       = 1.2;   // Minimal 20% lebih sering dari kebetulan

    /**
     * Menjalankan analisa asosiasi produk dan menghasilkan pasangan produk populer.
     * Hanya melihat data 90 hari terakhir untuk relevansi.
     *
     * @return Collection  Koleksi pasangan produk dengan metrics asosiasi
     */
    public function findTopPairs(): Collection
    {
        // 1. Ambil data transaksi (nota → produk apa saja yang dibeli bersamaan)
        $transactionData = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.transaction_date', '>=', now()->subDays(90))
            ->select('sales.id as sale_id', 'sale_items.product_id')
            ->get();

        if ($transactionData->isEmpty()) {
            return collect([]);
        }

        $totalTransactions = $transactionData->pluck('sale_id')->unique()->count();

        if ($totalTransactions < 10) {
            return collect([]); // Data terlalu sedikit untuk analisa yang berarti
        }

        // 2. Kelompokkan per transaksi (sale_id → [product_id, ...])
        $baskets = $transactionData->groupBy('sale_id')
            ->map(fn($items) => $items->pluck('product_id')->unique()->toArray());

        // 3. Hitung Support untuk setiap produk tunggal
        $singleSupport = [];
        foreach ($baskets as $items) {
            foreach ($items as $pid) {
                $singleSupport[$pid] = ($singleSupport[$pid] ?? 0) + 1;
            }
        }

        // 4. Hitung Support untuk setiap PASANGAN produk
        $pairCounts = [];
        foreach ($baskets as $items) {
            $items = array_values($items);
            $n = count($items);
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    // Gunakan sorted key agar A,B == B,A
                    $pairKey = min($items[$i], $items[$j]) . ':' . max($items[$i], $items[$j]);
                    $pairCounts[$pairKey] = ($pairCounts[$pairKey] ?? 0) + 1;
                }
            }
        }

        // 5. Filter berdasarkan thresholds & hitung metrics lengkap
        $results = collect([]);
        foreach ($pairCounts as $pairKey => $pairCount) {
            [$pidA, $pidB] = explode(':', $pairKey);
            $pidA = (int) $pidA;
            $pidB = (int) $pidB;

            $support = $pairCount / $totalTransactions;
            if ($support < $this->minSupport) continue;

            $supportA = ($singleSupport[$pidA] ?? 0) / $totalTransactions;
            $supportB = ($singleSupport[$pidB] ?? 0) / $totalTransactions;

            $confidenceAB = $supportA > 0 ? $support / $supportA : 0;
            $confidenceBA = $supportB > 0 ? $support / $supportB : 0;
            if ($confidenceAB < $this->minConfidence && $confidenceBA < $this->minConfidence) continue;

            $lift = $supportB > 0 ? $confidenceAB / $supportB : 0;
            if ($lift < $this->minLift) continue;

            $results->push([
                'product_id_a'   => $pidA,
                'product_id_b'   => $pidB,
                'pair_count'     => $pairCount,
                'support'        => round($support, 4),
                'confidence_a_b' => round($confidenceAB, 4),
                'confidence_b_a' => round($confidenceBA, 4),
                'lift'           => round($lift, 3),
            ]);
        }

        // 6. Sort by lift descending (pasangan paling kuat di atas)
        return $results->sortByDesc('lift')->values();
    }

    /**
     * Membangun rekomendasi bundling yang bisa dibaca manusia.
     */
    public function buildBundlingRecommendations(Collection $pairs, Collection $products): Collection
    {
        $productMap = $products->keyBy('id');

        return $pairs->map(function ($pair) use ($productMap) {
            $productA = $productMap->get($pair['product_id_a']);
            $productB = $productMap->get($pair['product_id_b']);

            if (!$productA || !$productB) return null;

            $confidencePct = round(max($pair['confidence_a_b'], $pair['confidence_b_a']) * 100);
            $supportPct    = round($pair['support'] * 100, 1);

            return [
                'product_a'       => $productA,
                'product_b'       => $productB,
                'pair_count'      => $pair['pair_count'],
                'support_pct'     => $supportPct,
                'confidence_pct'  => $confidencePct,
                'lift'            => $pair['lift'],
                'lift_label'      => $pair['lift'] >= 2.0 ? 'Sangat Kuat' : ($pair['lift'] >= 1.5 ? 'Kuat' : 'Moderat'),
                'recommendation'  => "{$confidencePct}% pelanggan yang membeli \"{$productA->name}\" juga membeli \"{$productB->name}\" (terjadi {$pair['pair_count']}x dalam 90 hari). Pertimbangkan bundling kedua produk ini.",
            ];
        })->filter()->values();
    }
}
