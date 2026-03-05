<?php

namespace App\Services\Analysis\Product;

use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * =========================================================================
 * CLASSIFICATION ANALYZER: ABC/XYZ Matrix
 * =========================================================================
 * ABC = Klasifikasi berdasarkan KONTRIBUSI REVENUE (Prinsip Pareto)
 *   A = Top 20% produk yang menghasilkan 80% revenue → Priority High
 *   B = 30% produk berikutnya, kontribusi 15% revenue → Priority Medium
 *   C = 50% produk sisanya, kontribusi 5% revenue → Low Priority
 *
 * XYZ = Klasifikasi berdasarkan PREDIKTABILITAS PERMINTAAN (Coefficient of Variation)
 *   X = Permintaan Stabil (CV < 0.5) → Mudah direncanakan
 *   Y = Permintaan Fluktuatif (0.5 ≤ CV < 1.0) → Perlu buffer stok
 *   Z = Permintaan Tidak Terprediksi (CV ≥ 1.0) → Risiko tinggi
 */
class ClassificationAnalyzer
{
    /**
     * Mengklasifikasikan satu produk ke dalam matriks ABC/XYZ.
     * Memerlukan data penjualan yang sudah di-query secara batch (bulk stats) 
     * untuk performa terbaik — melewatkan stats sebagai parameter.
     *
     * @param  Product   $product
     * @param  float     $productRevenue    Total revenue produk ini dalam periode
     * @param  float     $totalRevenue      Total revenue SEMUA produk dalam periode
     * @param  array     $monthlyQtys       Array qty terjual per bulan (12 bulan terakhir) [int, int, ...]
     * @return array
     */
    public function classify(Product $product, float $productRevenue, float $totalRevenue, array $monthlyQtys): array
    {
        // --- BAGIAN A: KLASIFIKASI ABC ---
        $revenueShare = $totalRevenue > 0 ? ($productRevenue / $totalRevenue) * 100 : 0;

        // Catatan: Untuk full Pareto, idealnya kita sort semua produk dulu.
        // Kita simpan share % ini dan InsightService yang menentukan A/B/C via cumulative ranking.
        // Tapi untuk payload single-product, kita simpan share-nya saja.
        $abcClass = 'C'; // Default
        // Threshold kasar berdasarkan revenue share per-produk
        // In a full system this would be cumulative sorting across all products
        if ($revenueShare >= 10) {
            $abcClass = 'A';
        } elseif ($revenueShare >= 3) {
            $abcClass = 'B';
        }

        // --- BAGIAN B: KLASIFIKASI XYZ (Coefficient of Variation) ---
        $xyzClass = 'Z';
        $cv = 0;
        $mean = 0;
        $stdDev = 0;

        $validMonths = array_filter($monthlyQtys, fn($q) => $q >= 0);
        $n = count($validMonths);

        if ($n >= 3) {
            $mean = array_sum($validMonths) / $n;

            if ($mean > 0) {
                $variance = array_sum(array_map(fn($q) => pow($q - $mean, 2), $validMonths)) / $n;
                $stdDev = sqrt($variance);
                $cv = $stdDev / $mean; // Coefficient of Variation

                if ($cv < 0.5) {
                    $xyzClass = 'X'; // Stabil
                } elseif ($cv < 1.0) {
                    $xyzClass = 'Y'; // Fluktuatif
                } else {
                    $xyzClass = 'Z'; // Tidak terprediksi
                }
            }
        } elseif ($n > 0) {
            // Data terlalu sedikit — masuk Z (unknown/baru)
            $mean = array_sum($validMonths) / max($n, 1);
            $xyzClass = 'Z';
        }

        // --- BAGIAN C: KOMBINASI MATRIX & REKOMENDASI ---
        $matrix = $abcClass . '-' . $xyzClass;
        $recommendation = $this->getMatrixRecommendation($matrix, $mean);

        return [
            'abc_class'      => $abcClass,        // A, B, C
            'xyz_class'      => $xyzClass,         // X, Y, Z
            'matrix'         => $matrix,           // A-X, B-Y, C-Z, dll.
            'revenue_share'  => round($revenueShare, 2),   // % share revenue
            'avg_monthly_qty' => round($mean, 1),  // Rata-rata qty/bulan
            'cv'             => round($cv, 3),     // Coefficient of Variation
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Query data penjualan 12 bulan terakhir per bulan untuk satu produk.
     * Method ini bisa dipanggil langsung ketika tidak ada batch stats.
     */
    public function getMonthlyQtys(Product $product): array
    {
        $rows = SaleItem::where('sale_items.product_id', $product->id)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.transaction_date', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("strftime('%Y-%m', sales.transaction_date) as month, SUM(sale_items.quantity) as qty")
            ->groupBy(DB::raw("strftime('%Y-%m', sales.transaction_date)"))
            ->pluck('qty', 'month')
            ->toArray();

        // Pastikan 12 slot bulan selalu ada (isi 0 jika tidak ada penjualan)
        $qtys = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthKey = now()->subMonths($i)->format('Y-m');
            $qtys[] = (int) ($rows[$monthKey] ?? 0);
        }

        return $qtys;
    }

    /**
     * Batch query revenue semua produk aktif (dioptimasi untuk performa — 1 query).
     * Digunakan oleh InsightService sebelum memanggil classify() per produk.
     * 
     * @return Collection [product_id => total_revenue]
     */
    public function getBatchRevenue(): Collection
    {
        return SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.transaction_date', '>=', now()->subMonths(3)->startOfMonth())
            ->selectRaw('sale_items.product_id, SUM(sale_items.quantity * sale_items.price) as total_revenue')
            ->groupBy('sale_items.product_id')
            ->pluck('total_revenue', 'sale_items.product_id');
    }

    /**
     * Batch query monthly qtys untuk SEMUA produk — diperlukan oleh InsightService
     * agar tidak N+1 query saat chunk produk.
     *
     * @return Collection [product_id => [qty_month1, qty_month2, ...]]
     */
    public function getBatchMonthlyQtys(): Collection
    {
        $rows = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.transaction_date', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("sale_items.product_id, strftime('%Y-%m', sales.transaction_date) as month, SUM(sale_items.quantity) as qty")
            ->groupBy('sale_items.product_id', DB::raw("strftime('%Y-%m', sales.transaction_date)"))
            ->get();

        // Konversi ke format [product_id => ['2024-01' => qty, '2024-02' => qty, ...]]
        $grouped = $rows->groupBy('product_id')->map(function ($items) {
            return $items->pluck('qty', 'month')->toArray();
        });

        // Normalisasi: pastikan setiap produk punya array 12 angka berurutan
        return $grouped->map(function ($monthMap) {
            $qtys = [];
            for ($i = 11; $i >= 0; $i--) {
                $monthKey = now()->subMonths($i)->format('Y-m');
                $qtys[] = (int) ($monthMap[$monthKey] ?? 0);
            }
            return $qtys;
        });
    }

    /**
     * Rekomendasi bisnis berdasarkan kombinasi matriks A-X, B-Z, dll.
     */
    private function getMatrixRecommendation(string $matrix, float $avgQty): string
    {
        $recommendations = [
            'A-X' => 'Produk SULTAN STABIL. Prioritas tertinggi: pastikan tidak pernah kosong. Pertahankan safety stock tinggi.',
            'A-Y' => 'Produk SULTAN FLUKTUATIF. Revenue tinggi tapi permintaan naik-turun. Pantau ketat & siapkan buffer besar.',
            'A-Z' => 'Produk SULTAN LIAR. Revenue sangat besar tapi sulit diprediksi. Pertimbangkan MOQ (Min Order Qty) dari supplier.',
            'B-X' => 'Produk MENENGAH STABIL. Aman dan terprediksi. Kelola dengan reorder point rutin.',
            'B-Y' => 'Produk MENENGAH FLUKTUATIF. Pantau per kuartal. Pertimbangkan promosi untuk meningkatkan ke kelas A.',
            'B-Z' => 'Produk MENENGAH LIAR. Evaluasi apakah layak dipertahankan. Cari pola tersembunyi di histori penjualan.',
            'C-X' => 'Produk RECEH STABIL. Penjualan kecil tapi terprediksi. Minimalkan stok, cukup untuk display.',
            'C-Y' => 'Produk RECEH FLUKTUATIF. Revenue kecil dan tidak stabil. Evaluasi kelayakan di katalog.',
            'C-Z' => 'Produk BEBAN TERSEMBUNYI. Revenue receh dan tidak bisa diprediksi. Kandidat kuat untuk dihapus dari katalog.',
        ];

        return $recommendations[$matrix] ?? 'Klasifikasi baru — kumpulkan data lebih lama untuk analisa akurat.';
    }
}
