<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmartInsight;
use App\Services\InsightService; // Pastikan namespace sesuai

class GenerateDailyInsights extends Command
{
    protected $signature = 'insight:generate';
    protected $description = 'Menjalankan analisa malam menggunakan InsightService';

    public function handle(InsightService $insightService)
    {
        $this->info('🚀 Memulai Analisa Malam...');

        // Kita siapkan tanggal BESOK, karena ini laporannya buat besok pagi
        $besok = now()->addDay()->format('Y-m-d');

        // ==========================================
        // 1. HARIAN: RESTOCK PLAN
        // Menggunakan: analyzeSmartRestock()
        // ==========================================
        $this->info('- Menganalisa Restock...');
        $restockData = $insightService->analyzeSmartRestock(false);
        // Asumsi: $restockData mengembalikan Collection/Array produk yg perlu di-restock
        if (!empty($restockData)) {
            // Kita bisa langsung pakai Collection Laravel untuk hitung-hitungan
            $collection = collect($restockData);

            $totalBiaya = $collection->sum('estimasi_biaya');
            $topItems   = $collection->sortBy('days_left')->values()->all(); // Urutkan berdasarkan yang paling mendesak

            // Simpan SATU Summary Row saja
            SmartInsight::create([
                'type'        => SmartInsight::TYPE_DAILY_RESTOCK,
                'severity'    => SmartInsight::SEVERITY_WARNING,
                'title'       => 'Rencana Belanja',
                'message'     => 'Rangkuman restock harian',
                'payload'     => [
                    'target_date' => $besok,
                    'total_biaya' => $totalBiaya,
                    'items'       => $topItems // Simpan array item di dalam JSON
                ],
                'is_notified' => false
            ]);

            $this->info("  Found: " . count($restockData) . " items. Est: Rp " . number_format($totalBiaya));
        }

        // ==========================================
        // 2. HARIAN: STRATEGI (High Margin)
        // Menggunakan: analyzeHighMargins()
        // ==========================================
        $highMarginData = $insightService->analyzeHighMargins(false);

        // Ambil 1 teratas saja sebagai rekomendasi fokus
        $topMargin = collect($highMarginData)->first();

        if ($topMargin) {
            SmartInsight::create([
                'type'        => SmartInsight::TYPE_DAILY_STRATEGY,
                'severity'    => SmartInsight::SEVERITY_INFO,
                'title'       => 'Strategi Fokus',
                'message'     => 'Fokus jualan barang margin tebal',
                // 'payload'     => [
                //     'target_date' => $besok,
                //     'item_snapshot'   => $topMargin['product_snapshot'],
                //     'margin'      => $topMargin['margin'] ?? 0,

                // ],
                'payload' => [
                    'all' => $topMargin,
                ],
                'is_notified' => false
            ]);
        }

        // ==========================================
        // 3. MINGGUAN: DSS (Dead Stock & Trend)
        // Hanya jalan Minggu Malam
        // ==========================================
        if (now()->isSunday()) {
            $this->info('🕵️ Menjalankan Analisa Mingguan...');

            // A. Dead Stock
            $deadStockData = $insightService->analyzeDeadStock(false);
            if (!empty($deadStockData)) {
                $collection = collect($deadStockData);
                $totalFrozen = $collection->sum('frozen_asset');
                $topZombie   = $collection->sortByDesc('frozen_asset')->take(10)->values()->all();

                SmartInsight::create([
                    'type'        => SmartInsight::TYPE_WEEKLY_DSS_DEADSTOCK,
                    'severity'    => SmartInsight::SEVERITY_CRITICAL,
                    'title'       => 'Dead Stock Analysis',
                    'message'     => 'Aset mati terdeteksi',
                    'payload'     => [
                        'total_frozen_asset' => $totalFrozen,
                        'items' => $topZombie
                    ],
                    'is_notified' => false
                ]);
            }

            // B. Trending
            $trendData = $insightService->analyzeTrend(false);
            if (!empty($trendData)) {
                SmartInsight::create([
                    'type'        => SmartInsight::TYPE_WEEKLY_DSS_TRENDING,
                    'severity'    => SmartInsight::SEVERITY_INFO,
                    'title'       => 'Trending Items',
                    'message'     => 'Produk sedang naik daun',
                    'payload'     => [
                        'items' => array_slice($trendData, 0, 5) // Ambil Top 5
                    ],
                    'is_notified' => false
                ]);
            }
        }

        $this->info('✅ Analisa Selesai. Data siap untuk laporan besok pagi.');
    }
}
