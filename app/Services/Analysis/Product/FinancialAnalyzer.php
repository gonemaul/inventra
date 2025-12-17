<?php

namespace App\Services\Analysis\Product;

use App\Models\Product;
use App\Models\SaleItem;
use Carbon\Carbon;

class FinancialAnalyzer
{
    /**
     * =========================================================================
     * FUNGSI UTAMA (PARENT): FINANCIAL INTELLIGENCE
     * =========================================================================
     * Fungsi ini mengumpulkan semua metrik keuangan: Margin, Tren Harga,
     * Pertumbuhan Penjualan, dan Kontribusi Profit.
     */
    public function calculate(Product $product): array
    {
        // 1. Analisa Margin (Sekarang mendeteksi High Margin juga)
        $marginMetrics = $this->analyzeMargin($product);

        // 2. Analisa Siklus Hidup (Produk Baru)
        $lifecycle = $this->analyzeLifecycle($product);

        // 3. Analisa Tren Harga (Apakah modal naik? Apakah harga jual turun?)
        $priceTrend = $this->analyzePriceTrend($product);

        // 4. Analisa Tren Penjualan (Growth: Apakah produk ini makin laris?)
        $salesTrend = $this->analyzeSalesGrowth($product);

        // 5. Analisa Kontribusi (Cash Cow: Seberapa besar sumbangan uangnya?)
        $contribution = $this->analyzeProfitContribution($product, $salesTrend['qty_this_month']);

        return [
            'margin'        => $marginMetrics,
            'lifecycle'     => $lifecycle,
            'price_trend'   => $priceTrend,
            'sales_trend'   => $salesTrend,
            'contribution'  => $contribution,

            // Kesimpulan Singkat untuk UI
            'financial_status' => $this->determineFinancialStatus($marginMetrics, $priceTrend, $salesTrend)
        ];
    }

    /**
     * =========================================================================
     * PRIVATE 1: MARGIN ANALYZER (Penjaga Profit)
     * =========================================================================
     * Menghitung keuntungan real dan mendeteksi jika profit terlalu tipis.
     * Sekarang mendeteksi Critical (Tipis) DAN High (Tinggi)
     */
    private function analyzeMargin(Product $product): array
    {
        $buy = (float) $product->purchase_price;
        $sell = (float) $product->selling_price;
        $marginRp = $sell - $buy;
        // Menghindari error division by zero jika harga beli 0 (misal barang bonus)
        $marginPercent = $buy > 0 ? ($marginRp / $buy) * 100 : 100;
        // Logic Alert:
        $isCritical = ($marginPercent < 10 && $buy > 0);
        $isHighMargin = ($marginPercent > 40);

        return [
            'rp'          => $marginRp,
            'percent'     => round($marginPercent, 1),
            'is_critical' => $isCritical,
            'is_high_margin' => $isHighMargin,
            'message'     => $isCritical ? "Profit Tipis" : ($isHighMargin ? "Profit Tebal" : "Normal")
        ];
    }

    /**
     * =========================================================================
     * PRIVATE 2: NEW PRODUCT (Detektif Produk Baru)
     * =========================================================================
     * Menghitung kapan produk dibuat
     * Membantu mendeteksi produk yang dibuat kurang dari 30 hari
     */
    private function analyzeLifecycle(Product $product): array
    {
        // Definisi Produk Baru: Diinput kurang dari 30 hari yang lalu
        $daysSinceCreated = $product->created_at->diffInDays(now());
        $isNew = $daysSinceCreated <= 30;

        return [
            'is_new_product' => $isNew,
            'days_active'    => round($daysSinceCreated)
        ];
    }
    /**
     * =========================================================================
     * PRIVATE 3: PRICE TREND (Detektif Harga Modal)
     * =========================================================================
     * Membandingkan harga beli saat ini dengan harga beli terakhir (Snapshot).
     * Berguna untuk mendeteksi supplier yang menaikkan harga diam-diam.
     */
    private function analyzePriceTrend(Product $product): array
    {
        $currentBuy = (float) $product->purchase_price;

        // Ambil data snapshot terakhir (disimpan saat Purchase Order sebelumnya)
        // Jika tidak ada snapshot, anggap sama dengan harga sekarang.
        $lastBuy = (float) ($product->snapshot['purchase_price'] ?? $currentBuy);

        $diff = $currentBuy - $lastBuy;
        $trendDirection = 'flat'; // flat, up, down

        $alertMessage = '';

        if ($diff > 0) {
            $trendDirection = 'up'; // BAHAYA: Modal Naik

            // Cek apakah harga jual juga dinaikkan?
            // (Logikanya kita harus cek harga jual snapshot vs current juga,
            // tapi disini kita asumsikan warning dulu ke user).
            $alertMessage = "Harga Modal NAIK Rp " . number_format($diff) . ". Cek harga jual!";
        } elseif ($diff < 0) {
            $trendDirection = 'down'; // BAGUS: Modal Turun (Diskon supplier)
            $alertMessage = "Modal turun. Kesempatan margin lebih besar.";
        }

        return [
            'direction'   => $trendDirection,
            'diff_rp'     => abs($diff),
            'old_price'   => $lastBuy,
            'has_alert'   => ($trendDirection === 'up'), // Alert jika modal naik
            'message'     => $alertMessage
        ];
    }

    /**
     * =========================================================================
     * PRIVATE 4: SALES GROWTH (Analis Pertumbuhan)
     * =========================================================================
     * Membandingkan performa penjualan 30 hari ini vs 30 hari lalu.
     * Menentukan apakah barang sedang "Naik Daun" (Trending) atau "Meredup".
     */
    private function analyzeSalesGrowth(Product $product): array
    {
        // Periode A: 30 Hari Terakhir
        $startThisMonth = now()->subDays(30);
        $qtyThisMonth = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn($q) => $q->where('transaction_date', '>=', $startThisMonth))
            ->sum('quantity');

        // Periode B: 30 Hari Sebelumnya (Bulan Lalu)
        $startLastMonth = now()->subDays(60);
        $endLastMonth   = now()->subDays(30);
        $qtyLastMonth = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn($q) => $q->whereBetween('transaction_date', [$startLastMonth, $endLastMonth]))
            ->sum('quantity');

        // Hitung Persentase Pertumbuhan
        $growthPercent = 0;
        if ($qtyLastMonth > 0) {
            $growthPercent = (($qtyThisMonth - $qtyLastMonth) / $qtyLastMonth) * 100;
        } elseif ($qtyThisMonth > 0) {
            $growthPercent = 100; // Dari 0 jadi ada penjualan = 100% growth
        }

        // Logic Status Trending
        // Syarat: Minimal laku 10 pcs DAN tumbuh > 20%
        $isTrending = ($qtyThisMonth >= 10 && $growthPercent > 20);

        // Logic Status Declining (Meredup)
        // Syarat: Penurunan > 30%
        $isDeclining = ($growthPercent < -30);

        return [
            'qty_this_month' => $qtyThisMonth,
            'qty_last_month' => $qtyLastMonth,
            'growth_percent' => round($growthPercent, 1),
            'is_trending'    => $isTrending,
            'is_declining'   => $isDeclining
        ];
    }

    /**
     * =========================================================================
     * PRIVATE 5: CONTRIBUTION (Analisa Cash Cow)
     * =========================================================================
     * Menghitung total uang (Gross Profit) yang dihasilkan produk ini sebulan.
     * Penting untuk mengidentifikasi barang yang jarang laku tapi untungnya besar.
     */
    private function analyzeProfitContribution(Product $product, int $qtySold): array
    {
        $marginRp = $product->selling_price - $product->purchase_price;

        // Total Cuan Sebulan
        $totalGrossProfit = $qtySold * $marginRp;

        // Logic Klasifikasi Aset (Pareto Principle)
        // Angka threshold bisa disesuaikan dengan skala toko Anda.
        // Misal: Toko kecil, sumbangan profit > 500rb/bulan itu besar.
        $class = 'C'; // C = Receh
        if ($totalGrossProfit > 1000000) {
            $class = 'A'; // A = Sultan (Cash Cow)
        } elseif ($totalGrossProfit > 500000) {
            $class = 'B'; // B = Lumayan
        }

        return [
            'total_profit' => $totalGrossProfit,
            'class'        => $class, // A, B, atau C
            'message'      => ($class === 'A') ? "Produk Sultan! (Profit > 1 Juta)" : ""
        ];
    }

    /**
     * PRIVATE HELPER: KESIMPULAN AKHIR
     */
    private function determineFinancialStatus($margin, $price, $sales): string
    {
        if ($margin['is_critical']) return 'danger'; // Prioritas 1: Profit tipis
        if ($price['has_alert']) return 'warning';   // Prioritas 2: Modal naik
        if ($sales['is_trending']) return 'success'; // Prioritas 3: Lagi laris
        return 'neutral';
    }
}
