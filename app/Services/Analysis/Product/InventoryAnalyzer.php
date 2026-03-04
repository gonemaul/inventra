<?php

namespace App\Services\Analysis\Product;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SmartInsight;
use Carbon\Carbon;

class InventoryAnalyzer
{
    /**
     * =========================================================================
     * FUNGSI UTAMA (PARENT): INVENTORY INTELLIGENCE SYSTEM
     * =========================================================================
     * Fungsi ini adalah "Otak" yang mengorkestrasi seluruh logika bisnis,
     * matematika, marketing, dan data historis untuk mengambil keputusan stok.
     */
    public function calculateInventoryHealth(Product $product): array
    {
        // 1. DATA DASAR
        $stock = (int) $product->stock;
        $minStock = (int) ($product->min_stock ?? 0);

        // 2. HITUNG VELOCITY (KECEPATAN JUAL SAAT INI) BERTENAGA AI
        // Menggeser dari Simple Moving Average ke Weighted Average
        // Cek 7 hari terakhir (Tren Panas), 14 hari terakhir (Tren Sedang), 30 hari terakhir (Tren Dasar)
        
        $today = now()->endOfDay();
        $days7Ago = now()->subDays(7)->startOfDay();
        $days14Ago = now()->subDays(14)->startOfDay();
        $days30Ago = now()->subDays(30)->startOfDay();

        $velocityStats = SaleItem::where('sale_items.product_id', $product->id)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.transaction_date', '>=', $days30Ago)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN sales.transaction_date >= ? THEN sale_items.quantity ELSE 0 END), 0) as qty_7_days,
                COALESCE(SUM(CASE WHEN sales.transaction_date >= ? THEN sale_items.quantity ELSE 0 END), 0) as qty_14_days,
                COALESCE(SUM(CASE WHEN sales.transaction_date >= ? THEN sale_items.quantity ELSE 0 END), 0) as qty_30_days
            ", [$days7Ago, $days14Ago, $days30Ago])
            ->first();

        // Kecepatan harian berdasarkan periode
        $velocity7D = ($velocityStats->qty_7_days ?? 0) / 7;
        $velocity14D = ($velocityStats->qty_14_days ?? 0) / 14;
        $velocity30D = ($velocityStats->qty_30_days ?? 0) / 30;

        // Weighted Average Algorithm: Lebih menitikberatkan tren yang paling baru
        // 50% bobot untuk 1 minggu terakhir, 30% untuk 2 minggu terakhir, 20% untuk 1 bulan terakhir
        $weightedAvgDaily = ($velocity7D * 0.5) + ($velocity14D * 0.3) + ($velocity30D * 0.2);

        // 3. PANGGIL KECERDASAN BUATAN (PRIVATE METHODS)

        // A. Cek Faktor Musiman (Apakah bulan ini tahun lalu ramai?)
        $seasonalFactor = $this->calculateSeasonalFactor($product);

        // B. Sesuaikan velocity dengan Prediksi Musim
        $avgDailyPredicted = $weightedAvgDaily * $seasonalFactor;

        // C. Cek Kesehatan Aset (Dead Stock Analysis)
        $deadStockMetrics = $this->getDeadStockMetrics($product);
        $daysInactive = $deadStockMetrics['days_inactive'];
        $isDeadStock = $deadStockMetrics['is_dead_stock'];

        // 4. FORECASTING & DYNAMIC MIN STOCK (AI Feature 2)
        $daysLeft = 999;
        $stockoutDate = null;
        if ($avgDailyPredicted > 0) {
            $daysLeft = $stock / $avgDailyPredicted;
            $stockoutDate = now()->addDays($daysLeft);
        }

        // Kalkulasi Dynamic Min Stock: 
        // Waktu Lead Time Supplier rata-rata 7 hari + 30% buffer keamanan
        $leadTimeDays = 7; 
        $safetyBuffer = 1.3;
        $dynamicMinStock = ceil($avgDailyPredicted * $leadTimeDays * $safetyBuffer);

        // 5. STATUS DETERMINATION (WATERFALL PRIORITY)
        // Menentukan tingkat urgensi berdasarkan "3 Lapisan Pertahanan"
        $status = SmartInsight::SEVERITY_SAFE;
        $message = '';
        $priorityScore = 0;
        
        // Peringatan Dynamic Stock
        $isDynamicStockWarning = false;

        if ($stock <= 0) {
            $status = SmartInsight::SEVERITY_CRITICAL;
            $message = 'Stok HABIS! (Lost Sales)';
            $priorityScore = 100;
        } elseif ($stock <= 2 && $avgDailyPredicted > 0) {
            $status = SmartInsight::SEVERITY_CRITICAL;
            $message = "Stok Kritis Sisa {$stock}";
            $priorityScore = 90;
        } elseif ($daysLeft <= 3 && $avgDailyPredicted > 0) {
            $status = SmartInsight::SEVERITY_CRITICAL;
            $message = "Prediksi Habis ".floor($daysLeft)." Hari Lagi";
            $priorityScore = 80;
        } elseif ($daysLeft <= 7 && $avgDailyPredicted > 0) {
            $status = SmartInsight::SEVERITY_WARNING;
            $message = "Prediksi Habis < 1 Minggu";
            $priorityScore = 70;
        } elseif ($stock <= $minStock) {
            $status = SmartInsight::SEVERITY_WARNING;
            $message = "Menyentuh Min. Stok Manual ({$minStock})";
            $priorityScore = 60;
        } elseif ($dynamicMinStock > $minStock && $stock <= $dynamicMinStock) {
            // Ini Trigger AI Feature #2: Dynamic Safety Stock
            $status = SmartInsight::SEVERITY_WARNING;
            $message = "Peringatan AI: Tren penjualan tinggi, batas min stok ({$minStock}) kurang aman.";
            $priorityScore = 55;
            $isDynamicStockWarning = true;
        } elseif ($isDeadStock) {
            $status = 'dead';
            $message = "Barang Mati (Tidak laku {$daysInactive} hari)";
            $priorityScore = 10; // Prioritas rendah untuk dibeli, tapi tinggi untuk diawasi
        }

        // 6. RESTOCK PLAN (REKOMENDASI CERDAS)
        // Menghitung berapa yang harus dibeli dengan menyeimbangkan Data vs User

        $suggestedQty = 0;
        $restockReason = '';
        $targetStock = 0;
        $isOverstockRisk = false;

        // Hanya hitung jika status BUKAN Safe/Dead (kecuali dipaksa min_stock user)
        if ($status !== SmartInsight::SEVERITY_SAFE && $status !== 'dead') {

            // --- LOGIC A: MATEMATIKA (Kebutuhan Nyata) ---
            $targetDays = 14;
            $safetyFactor = 1.5; // Buffer 50% untuk antisipasi lonjakan
            $mathTarget = ceil($avgDailyPredicted * $targetDays * $safetyFactor);

            // --- LOGIC B: MARKETING (Aturan Display) ---
            // Jika barang "Hidup" (laku), minimal pajang 2 agar rak tidak kosong.
            $marketingTarget = ($avgDailyPredicted > 0) ? 2 : 0;

            // --- LOGIC C: BISNIS (Keinginan Owner) ---
            $userTarget = $minStock;

            // --- THE BALANCING ACT (PENYEIMBANG) ---
            // Cari target ideal berdasarkan Data & Marketing dulu
            $dataDrivenTarget = max($mathTarget, $marketingTarget);

            if ($userTarget > $dataDrivenTarget) {
                // KONFLIK: User minta banyak (5), Data bilang sepi (butuh 1).
                $isSlowMoving = $avgDailyPredicted < 0.3;

                if ($isSlowMoving) {
                    $targetStock = $dataDrivenTarget; 
                    if ($stock < $targetStock) {
                        $restockReason = "Target dibatasi ke {$targetStock} (Display Only). Min Stock ({$userTarget}) diabaikan karena penjualan lambat.";
                        $isOverstockRisk = true;
                    }
                } else {
                    $targetStock = $userTarget;
                    $restockReason = "Mengikuti Min Stock Manual ({$userTarget}).";
                }
            } else {
                // KASUS AI: Data butuh lebih banyak dari manual user.
                $targetStock = max($dataDrivenTarget, $userTarget);
                $restockReason = "AI Suggestion: Beli ".($targetStock - $stock)." pcs untuk cover {$targetDays} hari kedepan berdasar Tren Velocity.";
            }

            // Hitung selisih yang harus dibeli
            $suggestedQty = max(0, $targetStock - $stock);
        }

        // 7. SUBSTITUTION CHECK (FINAL FILTER)
        // Cek apakah ada barang pengganti yang dead stock sebelum menyuruh beli
        $substituteStock = 0;
        if ($suggestedQty > 0) {
            $substituteStock = $this->analyzeSubstitutes($product);

            if ($substituteStock > 5) {
                // Ada > 5 barang merk lain nganggur. Kurangi saran beli!
                // Strategi: Beli sekadarnya saja (Marketing Target), dorong jual barang substitusi.
                $originalSuggestion = $suggestedQty;

                // Pastikan minimal beli untuk display (2) tetap terpenuhi jika kosong banget
                $marketingTarget = ($avgDailyPredicted > 0) ? 2 : 0;
                $newSuggestion = max($marketingTarget, $suggestedQty - $substituteStock);

                $suggestedQty = $newSuggestion;

                if ($suggestedQty < $originalSuggestion) {
                    $restockReason = "Saran beli DIKURANGI. Ada {$substituteStock} stok substitusi (Merk Lain) nganggur.";
                    $status = SmartInsight::SEVERITY_WARNING; // Force warning agar dinotice
                }
            }
        }

        // Return Data Lengkap untuk View & Dashboard
        return [
            'status' => $status, // critical, warning, safe, dead
            'message' => $message,
            'avg_daily' => round($avgDailyPredicted, 2),
            'velocity_7d' => round($velocity7D ?? 0, 2),
            'velocity_30d' => round($velocity30D ?? 0, 2),
            'seasonal_factor' => $seasonalFactor,
            'days_left' => floor($daysLeft),
            'stockout_date' => $stockoutDate ? $stockoutDate->isoFormat('D MMM Y') : '-',
            'current_stock' => $stock,
            'dynamic_min_stock' => $dynamicMinStock ?? 0,
            'is_dynamic_warning' => $isDynamicStockWarning ?? false,
            'suggested_qty' => $suggestedQty,
            'target_stock' => $targetStock,
            'restock_reason' => $restockReason,
            'is_dead_stock' => $isDeadStock,
            'days_inactive' => $daysInactive,
            'frozen_asset' => $deadStockMetrics['frozen_asset'] ?? 0,
            'substitute_stock' => $substituteStock,
        ];
    }

    /**
     * =========================================================================
     * PRIVATE 1: SEASONALITY (Membaca Pola Tahunan)
     * =========================================================================
     * Membandingkan penjualan bulan ini dengan bulan yang sama tahun lalu
     * untuk mendeteksi tren musiman (Lebaran, Akhir Tahun, dll).
     *
     * * @return float (Multiplier: 1.0 = Normal, >1.0 = Musim Ramai)
     */
    private function calculateSeasonalFactor(Product $product): float
    {
        // Skip jika produk baru (< 1 tahun)
        if ($product->created_at->diffInDays(now()) < 365) {
            return 1.0;
        }

        // Periode Tahun Lalu (Bulan Ini)
        $startLastYear = now()->subYear()->startOfMonth();
        $endLastYear = now()->subYear()->endOfMonth();

        // Periode Tahun Lalu (Bulan Sebelumnya - untuk baseline)
        $startPrevLastYear = now()->subYear()->subMonth()->startOfMonth();
        $endPrevLastYear = now()->subYear()->subMonth()->endOfMonth();

        // Query Statistik Musiman (Dioptimasi jadi 1 Query menggunakan Inner Join)
        $stats = SaleItem::where('sale_items.product_id', $product->id)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.transaction_date', [$startPrevLastYear, $endLastYear])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN sales.transaction_date >= ? THEN sale_items.quantity ELSE 0 END), 0) as sales_last_year,
                COALESCE(SUM(CASE WHEN sales.transaction_date <= ? THEN sale_items.quantity ELSE 0 END), 0) as sales_prev_last_year
            ", [$startLastYear, $endPrevLastYear])
            ->first();

        $salesLastYear = $stats->sales_last_year ?? 0;
        $salesPrevLastYear = $stats->sales_prev_last_year ?? 0;

        // Analisa Lonjakan
        if ($salesPrevLastYear > 0) {
            $growthFactor = $salesLastYear / $salesPrevLastYear;

            // Limitasi faktor pengali (Min 0.5x, Max 2.0x) agar tidak terlalu ekstrim
            return max(0.5, min($growthFactor, 2.0));
        }

        return 1.0;
    }

    /**
     * =========================================================================
     * PRIVATE 2: SUBSTITUTION CHECKER (Cek Barang Pengganti)
     * =========================================================================
     * Mencari produk lain dengan spesifikasi SAMA PERSIS (Kategori, Tipe, Ukuran)
     * tapi Merk berbeda, yang stoknya nganggur (Slow/Dead).
     *
     * * @return int (Total stok substitusi yang tersedia)
     */
    private function analyzeSubstitutes(Product $product): int
    {
        // Query Produk Serupa (Kategori & Ukuran sama, tapi BUKAN produk ini)
        $query = Product::where('id', '!=', $product->id)
            ->where('stock', '>', 0)
            ->where('category_id', $product->category_id)
            ->where('unit_id', $product->unit_id);

        if ($product->product_type_id) {
            $query->where('product_type_id', $product->product_type_id);
        }

        if ($product->size_id) {
            $query->where('size_id', $product->size_id);
        }

        $candidates = $query->get();
        if ($candidates->isEmpty()) {
            return 0;
        }

        $availableStock = 0;
        $subIds = $candidates->pluck('id');

        // Optimasi Performa DB: Hitung performa kandidat substitusi dalam 1 Query (Menghindari N+1 Query Loop)
        $subSalesData = SaleItem::whereIn('sale_items.product_id', $subIds)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.transaction_date', '>=', now()->subDays(30))
            ->selectRaw('sale_items.product_id, SUM(sale_items.quantity) as total_qty')
            ->groupBy('sale_items.product_id')
            ->pluck('total_qty', 'sale_items.product_id');

        foreach ($candidates as $sub) {
            // Kita hanya menghitungnya sebagai substitusi valid jika dia KURANG LAKU.
            // (Kalau substitusinya laris juga, ya jangan diganggu).
            $subSales = $subSalesData->get($sub->id, 0);

            // Batasan: Dianggap "Nganggur" jika laku < 5 pcs sebulan
            if ($subSales <= 5) {
                $availableStock += $sub->stock;
            }
        }

        return $availableStock;
    }

    /**
     * =========================================================================
     * PRIVATE 3: DEAD STOCK METRICS (Analisa Stok Mati)
     * =========================================================================
     * Memeriksa kapan terakhir kali barang ini terjual untuk menentukan
     * status kematian produk.
     *
     * * @return array [is_dead_stock, days_inactive, frozen_asset]
     */
    private function getDeadStockMetrics(Product $product): array
    {
        // Rule: Stok > 0 DAN Tidak laku > 90 Hari
        $THRESHOLD_DAYS = 90;

        $lastItem = SaleItem::where('product_id', $product->id)
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->orderBy('sales.transaction_date', 'desc')
            ->select('sales.transaction_date')
            ->first();

        $daysInactive = 0;

        if ($lastItem) {
            $daysInactive = Carbon::parse($lastItem->transaction_date)->diffInDays(now());
        } else {
            // Jika belum pernah laku, hitung dari tanggal input barang
            $daysInactive = $product->created_at->diffInDays(now());
        }

        $isDead = ($product->stock > 0 && $daysInactive >= $THRESHOLD_DAYS);

        return [
            'is_dead_stock' => $isDead,
            'days_inactive' => $daysInactive,
            'frozen_asset' => $isDead ? ($product->stock * $product->purchase_price) : 0,
        ];
    }
}
