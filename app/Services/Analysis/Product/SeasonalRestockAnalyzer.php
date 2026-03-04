<?php

namespace App\Services\Analysis\Product;

use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Collection;

/**
 * =========================================================================
 * SEASONAL RESTOCKING PLANNER
 * =========================================================================
 * Menganalisa pola historis penjualan dan faktor musiman untuk menghasilkan
 * rencana pembelian (Purchase Order draft) yang akan dieksekusi sebelum
 * musim ramai datang, sehingga tidak terjadi kehabisan stok di waktu puncak.
 *
 * Formula:
 *   peak_daily_velocity = avg_daily × seasonal_factor
 *   days_to_peak_start  = jarak hari ke awal bulan dengan seasonal_factor tertinggi dalam 2 bulan ke depan
 *   buffer_qty          = peak_daily_velocity × peak_duration × safety_factor (1.2)
 *   restock_needed      = max(0, buffer_qty - current_stock)
 */
class SeasonalRestockAnalyzer
{
    /**
     * Menganalisa satu produk untuk kebutuhan restock musiman.
     *
     * @param  Product $product
     * @param  float   $avgDailyVelocity  Velocitas harian saat ini (dari InventoryAnalyzer)
     * @return array
     */
    public function analyze(Product $product, float $avgDailyVelocity): array
    {
        // 1. Cari bulan mana dalam 2 bulan ke depan yang paling ramai (musiman)
        $upcomingPeakData = $this->detectUpcomingPeak($product);

        $hasPeak = $upcomingPeakData['has_peak'];
        $peakFactor = $upcomingPeakData['peak_factor'];
        $peakMonth = $upcomingPeakData['peak_month'];
        $daysUntilPeak = $upcomingPeakData['days_until_peak'];
        $peakDuration = $upcomingPeakData['peak_duration_days'];

        // 2. Kalkulasi kebutuhan stok saat musim puncak
        $currentStock = (int) $product->stock;
        $peakDailyVelocity = $avgDailyVelocity * $peakFactor;

        // Buffer: stok yang dibutuhkan selama musim puncak + safety margin 20%
        $safetyFactor = 1.2;
        $bufferQtyNeeded = ceil($peakDailyVelocity * $peakDuration * $safetyFactor);

        // Berapa yang harus dibeli sebelum musim puncak?
        $restockNeeded = max(0, $bufferQtyNeeded - $currentStock);

        // 3. Estimasi biaya restok
        $estimatedCost = $restockNeeded * (float) $product->purchase_price;

        // 4. Deadline pembelian: harus selesai sebelum peak minus lead time supplier (7 hari)
        $leadTimeDays = 7;
        $buyDeadlineDays = max(0, $daysUntilPeak - $leadTimeDays);

        // 5. Buat label urgensi
        $urgency = 'low';
        if ($hasPeak && $restockNeeded > 0) {
            if ($buyDeadlineDays <= 7) {
                $urgency = 'critical'; // Harus beli sekarang!
            } elseif ($buyDeadlineDays <= 21) {
                $urgency = 'warning';  // Segera rencanakan
            } else {
                $urgency = 'info';     // Ada waktu, mulai rencanakan
            }
        }

        return [
            'has_seasonal_peak'  => $hasPeak,
            'peak_month'         => $peakMonth,
            'peak_factor'        => round($peakFactor, 2),
            'days_until_peak'    => $daysUntilPeak,
            'buy_deadline_days'  => $buyDeadlineDays,
            'peak_daily_velocity' => round($peakDailyVelocity, 2),
            'buffer_qty_needed'  => $bufferQtyNeeded,
            'restock_needed'     => $restockNeeded,
            'current_stock'      => $currentStock,
            'estimated_cost'     => $estimatedCost,
            'urgency'            => $urgency,
            'recommendation'     => $this->buildRecommendation($product, $hasPeak, $peakMonth, $restockNeeded, $estimatedCost, $buyDeadlineDays),
        ];
    }

    /**
     * Mendeteksi puncak musiman dalam 2 bulan ke depan berdasarkan histori tahun lalu.
     */
    private function detectUpcomingPeak(Product $product): array
    {
        $noPeak = [
            'has_peak' => false,
            'peak_factor' => 1.0,
            'peak_month' => null,
            'days_until_peak' => 999,
            'peak_duration_days' => 30,
        ];

        // Produk baru belum punya histori 1 tahun
        if ($product->created_at->diffInDays(now()) < 365) {
            return $nopeak ?? $noPeak;
        }

        $bestFactor = 1.0;
        $bestMonth = null;
        $bestDaysUntil = 999;

        // Bandingkan 2 bulan ke depan dengan bulan yang sama tahun lalu
        for ($offset = 0; $offset <= 2; $offset++) {
            $futureMoment = now()->addMonths($offset);
            $lastYearStart = (clone $futureMoment)->subYear()->startOfMonth();
            $lastYearEnd   = (clone $futureMoment)->subYear()->endOfMonth();
            $lastYearPrevStart = (clone $futureMoment)->subYear()->subMonth()->startOfMonth();
            $lastYearPrevEnd   = (clone $futureMoment)->subYear()->subMonth()->endOfMonth();

            $stats = SaleItem::where('sale_items.product_id', $product->id)
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->whereBetween('sales.transaction_date', [$lastYearPrevStart, $lastYearEnd])
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN sales.transaction_date >= ? AND sales.transaction_date <= ? THEN sale_items.quantity ELSE 0 END), 0) as this_month_qty,
                    COALESCE(SUM(CASE WHEN sales.transaction_date >= ? AND sales.transaction_date <= ? THEN sale_items.quantity ELSE 0 END), 0) as prev_month_qty
                ", [$lastYearStart, $lastYearEnd, $lastYearPrevStart, $lastYearPrevEnd])
                ->first();

            $thisQty = $stats->this_month_qty ?? 0;
            $prevQty = $stats->prev_month_qty ?? 0;

            if ($prevQty > 0 && $thisQty > $prevQty) {
                $factor = $thisQty / $prevQty;
                $factor = max(0.5, min($factor, 3.0)); // Limit 0.5x – 3.0x

                if ($factor > 1.3 && $factor > $bestFactor) { // Minimal 30% kenaikan
                    $bestFactor = $factor;
                    $bestMonth = $futureMoment->format('F Y');
                    $bestDaysUntil = (int) now()->diffInDays($futureMoment->startOfMonth());
                }
            }
        }

        if ($bestFactor > 1.3) {
            return [
                'has_peak'           => true,
                'peak_factor'        => $bestFactor,
                'peak_month'         => $bestMonth,
                'days_until_peak'    => $bestDaysUntil,
                'peak_duration_days' => 30,
            ];
        }

        return $noPeak;
    }

    /**
     * Membangun teks rekomendasi yang mudah dibaca.
     */
    private function buildRecommendation(
        Product $product,
        bool $hasPeak,
        ?string $peakMonth,
        int $restockNeeded,
        float $estimatedCost,
        int $buyDeadlineDays
    ): string {
        if (!$hasPeak || $restockNeeded <= 0) {
            return 'Tidak ada prediksi puncak musiman dalam 2 bulan ke depan.';
        }

        $costFormatted = 'Rp ' . number_format($estimatedCost, 0, ',', '.');

        if ($buyDeadlineDays <= 7) {
            return "SEGERA BELI {$restockNeeded} pcs ({$costFormatted}) untuk menghadapi musim ramai {$peakMonth}. Deadline pembelian {$buyDeadlineDays} hari lagi!";
        }

        return "Rencanakan beli {$restockNeeded} pcs ({$costFormatted}) sebelum {$peakMonth} (dalam {$buyDeadlineDays} hari). Histori tahun lalu menunjukkan lonjakan permintaan pada periode ini.";
    }
}
