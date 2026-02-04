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

        // 2. HITUNG VELOCITY (KECEPATAN JUAL SAAT INI)
        // Mengambil data 30 hari terakhir sebagai patokan dasar (Short-term trend)
        // $sales30Days = SaleItem::where('product_id', $product->id)
        //     ->whereHas('sale', fn($q) => $q->where('transaction_date', '>=', now()->subDays(30)))
        //     ->sum('quantity');
        $sales30Days = $product->qty_this_month ?? 0;

        $avgDailyRaw = $sales30Days > 0 ? ($sales30Days / 30) : 0;

        // 3. PANGGIL KECERDASAN BUATAN (PRIVATE METHODS)

        // A. Cek Faktor Musiman (Apakah bulan ini tahun lalu ramai?)
        $seasonalFactor = $this->calculateSeasonalFactor($product);

        // B. Sesuaikan Rata-rata dengan Prediksi Musim
        // Jika seasonalFactor 1.5 (Ramai), maka avgDaily dinaikkan 50% untuk persiapan.
        $avgDailyPredicted = $avgDailyRaw * $seasonalFactor;

        // C. Cek Kesehatan Aset (Dead Stock Analysis)
        $deadStockMetrics = $this->getDeadStockMetrics($product);
        $daysInactive = $deadStockMetrics['days_inactive'];
        $isDeadStock = $deadStockMetrics['is_dead_stock'];

        // 4. FORECASTING (PREDIKSI HABIS)
        // Menghitung berapa hari lagi stok akan nol berdasarkan kecepatan yang sudah disesuaikan musim
        $daysLeft = 999;
        $stockoutDate = null;
        if ($avgDailyPredicted > 0) {
            $daysLeft = $stock / $avgDailyPredicted;
            $stockoutDate = now()->addDays($daysLeft);
        }

        // 5. STATUS DETERMINATION (WATERFALL PRIORITY)
        // Menentukan tingkat urgensi berdasarkan "3 Lapisan Pertahanan"
        $status = SmartInsight::SEVERITY_SAFE;
        $message = '';
        $priorityScore = 0;

        if ($stock <= 0) {
            $status = SmartInsight::SEVERITY_CRITICAL;
            $message = 'Stok HABIS! (Lost Sales)';
            $priorityScore = 100;
        } elseif ($stock <= 2 && $avgDailyPredicted > 0) {
            $status = SmartInsight::SEVERITY_CRITICAL;
            $message = "Stok Sisa {$stock} (Danger Zone)";
            $priorityScore = 90;
        } elseif ($daysLeft <= 3 && $avgDailyPredicted > 0) {
            $status = SmartInsight::SEVERITY_CRITICAL;
            $message = 'Habis < 3 Hari (Laris)';
            $priorityScore = 80;
        } elseif ($stock <= $minStock) {
            $status = SmartInsight::SEVERITY_WARNING;
            $message = 'Di bawah Min. Stock User';
            $priorityScore = 60;
        } elseif ($daysLeft <= 7 && $avgDailyPredicted > 0) {
            $status = SmartInsight::SEVERITY_WARNING;
            $message = 'Habis < 1 Minggu';
            $priorityScore = 50;
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
                // Cek apakah Slow Moving? (< 1 item per 3 hari)
                $isSlowMoving = $avgDailyPredicted < 0.3;

                if ($isSlowMoving) {
                    // KEPUTUSAN: Tolak keinginan User demi Cashflow.
                    $targetStock = $dataDrivenTarget; // Ambil angka kecil (2)

                    if ($stock < $targetStock) {
                        $restockReason = "Target dibatasi ke {$targetStock} (Display Only). Min Stock ({$userTarget}) diabaikan karena Slow Moving.";
                        $isOverstockRisk = true;
                    }
                } else {
                    // Barang lumayan jalan, turuti user.
                    $targetStock = $userTarget;
                    $restockReason = "Mengikuti Min Stock User ({$userTarget}).";
                }
            } else {
                // KASUS NORMAL / FAST MOVING
                // Data butuh lebih banyak dari user, ikuti Data.
                $targetStock = max($dataDrivenTarget, $userTarget);
                $restockReason = "Target stok optimal: {$targetStock} pcs (Cover {$targetDays} hari).";
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
            'seasonal_factor' => $seasonalFactor,
            'days_left' => floor($daysLeft),
            'stockout_date' => $stockoutDate ? $stockoutDate->isoFormat('D MMM Y') : '-',
            'current_stock' => $stock,
            'suggested_qty' => $suggestedQty,
            'target_stock' => $targetStock,
            'restock_reason' => $restockReason,
            'is_dead_stock' => $isDeadStock,
            'days_inactive' => $daysInactive,
            'frozen_asset' => $deadStockMetrics['frozen_asset'],
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

        // Query Statistik
        $salesLastYear = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn ($q) => $q->whereBetween('transaction_date', [$startLastYear, $endLastYear]))
            ->sum('quantity');

        $salesPrevLastYear = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn ($q) => $q->whereBetween('transaction_date', [$startPrevLastYear, $endPrevLastYear]))
            ->sum('quantity');

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
        $availableStock = 0;

        foreach ($candidates as $sub) {
            // Cek performa barang pengganti
            // Kita hanya menghitungnya sebagai substitusi valid jika dia KURANG LAKU.
            // (Kalau substitusinya laris juga, ya jangan diganggu).

            $subSales = SaleItem::where('product_id', $sub->id)
                ->whereHas('sale', fn ($q) => $q->where('transaction_date', '>=', now()->subDays(30)))
                ->sum('quantity');

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

        $isDead = ($product->stock > 0 && $daysInactive > $THRESHOLD_DAYS);

        return [
            'is_dead_stock' => $isDead,
            'days_inactive' => $daysInactive,
            'frozen_asset' => $isDead ? ($product->stock * $product->purchase_price) : 0,
        ];
    }
}
