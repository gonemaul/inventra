<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use Illuminate\Console\Command;
use App\Models\PurchaseInvoice;  // Model Pembelian/Belanja
use App\Models\Sale;
use App\Services\TelegramService;
use Carbon\Carbon;

class SendFinancialReport extends Command
{
    protected $signature = 'report:financial';
    protected $description = 'Laporan Finansial (Harian, Mingguan, Bulanan)';

    public function handle()
    {
        $message = "💰 <b>FINANCIAL UPDATE (12:30)</b>\n";
        $message .= "🗓 " . now()->isoFormat('dddd, D MMMM Y') . "\n\n";
        // $message .= "------------------------------------------------------------\n\n";
        // ==========================================
        // 1. LAPORAN BULANAN (Khusus Tanggal 1)
        // ==========================================
        if (now()->day === 1) {
            $this->appendMonthlyReport($message);
            // $message .= "\n------------------------------------------------------------\n\n";
        }
        // ==========================================
        // 2. LAPORAN MINGGUAN (Khusus Hari Senin)
        // ==========================================
        if (now()->isMonday()) {
            $this->appendWeeklyReport($message);
            // $message .= "\n------------------------------------------------------------\n\n";
        }
        // ==========================================
        // 3. LAPORAN HARIAN (Snapshot Siang Ini)
        // ==========================================

        $this->appendDailySnapshot($message);
        $message .= "\n<i>Jaga cashflow tetap hijau, Bos!</i> 💸";

        TelegramService::send($message);
        $this->info('Laporan Finansial Terkirim.');
    }

    /**
     * Logic Laporan Bulanan (Bulan Lalu vs 2 Bulan Lalu)
     */
    private function appendMonthlyReport(&$msg)
    {
        // 1. Setup Tanggal
        $targetDate = now()->subMonth();         // Bulan yang dianalisa (misal: Feb 2025)
        $momDate    = now()->subMonths(2);       // Bulan sebelumnya (Jan 2025)
        $yoyDate    = now()->subMonth()->subYear(); // Bulan sama tahun lalu (Feb 2024)

        // 2. Query PENDAPATAN (Sales)
        $incTarget = Sale::whereMonth('created_at', $targetDate->month)->whereYear('created_at', $targetDate->year)->sum('total_revenue');
        $incMoM    = Sale::whereMonth('created_at', $momDate->month)->whereYear('created_at', $momDate->year)->sum('total_revenue');
        $incYoY    = Sale::whereMonth('created_at', $yoyDate->month)->whereYear('created_at', $yoyDate->year)->sum('total_revenue');

        // 3. Query PENGELUARAN (Purchases)
        $expTarget = Purchase::whereMonth('transaction_date', $targetDate->month)->whereYear('transaction_date', $targetDate->year)->sum('grand_total');
        $expMoM    = Purchase::whereMonth('transaction_date', $momDate->month)->whereYear('transaction_date', $momDate->year)->sum('grand_total');
        $expYoY    = Purchase::whereMonth('transaction_date', $yoyDate->month)->whereYear('transaction_date', $yoyDate->year)->sum('grand_total');

        // 4. Hitung Laba Bersih Bulan Ini
        $profit = $incTarget - $expTarget;

        // 5. Render Pesan
        $msg .= "📊 <b>REKAP BULAN " . $targetDate->isoFormat('MMMM Y') . "</b>\n";

        $msg .= "------------------------------------------------------------\n\n";

        // Render Baris Pemasukan (False = Makin tinggi makin bagus)
        $msg .= $this->renderComparisonBlock("Pemasukan", $incTarget, $incMoM, $incYoY, false, $momDate, $yoyDate);
        // Render Baris Pengeluaran (True = Makin tinggi makin jelek/warning)
        $msg .= $this->renderComparisonBlock("Pembelian", $expTarget, $expMoM, $expYoY, true, $momDate, $yoyDate);

        $msg .= "💵 <b>Cashflow Bersih: Rp " . number_format($profit, 0, ',', '.') . "</b>\n\n";
        // $msg .= "------------------------------------------------------------\n\n";
    }

    /**
     * Logic Laporan Mingguan (Minggu Lalu vs 2 Minggu Lalu)
     */
    private function appendWeeklyReport(&$msg)
    {
        // Minggu Lalu (Senin - Minggu kemarin)
        $startLastWeek = now()->subWeek()->startOfWeek();
        $endLastWeek   = now()->subWeek()->endOfWeek();

        // 2 Minggu Lalu
        $startPrevWeek = now()->subWeeks(2)->startOfWeek();
        $endPrevWeek   = now()->subWeeks(2)->endOfWeek();

        // A. PENDAPATAN
        $incCurrent = Sale::whereBetween('created_at', [$startLastWeek, $endLastWeek])->sum('total_revenue');
        $incPrev    = Sale::whereBetween('created_at', [$startPrevWeek, $endPrevWeek])->sum('total_revenue');

        // B. PENGELUARAN
        $expCurrent = Purchase::whereBetween('transaction_date', [$startLastWeek, $endLastWeek])->sum('grand_total');
        $expPrev    = Purchase::whereBetween('transaction_date', [$startPrevWeek, $endPrevWeek])->sum('grand_total');

        $msg .= "📈 <b>REKAP MINGGU LALU</b>\n";
        $msg .= "------------------------------------------------------------\n\n";
        $msg .= $this->formatGrowthLine("Omzet", $incCurrent, $incPrev);
        $msg .= $this->formatGrowthLine("Belanja", $expCurrent, $expPrev, true);
        $msg .= "\n";
    }

    /**
     * Logic Snapshot Harian (Hari ini s/d jam 12:30)
     */
    private function appendDailySnapshot(&$msg)
    {
        $today = now()->format('Y-m-d');

        // Omzet hari ini sampai detik ini
        $omzet = Sale::whereDate('created_at', $today)->sum('total_revenue');

        // Belanja/Restock hari ini
        $belanja = Purchase::whereDate('transaction_date', $today)->sum('grand_total');

        $msg .= "🌤 <b>SNAPSHOT HARI INI (12:30)</b>\n";
        $msg .= "------------------------------------------------------------\n\n";
        $msg .= "🟢 Omzet: <b>Rp " . number_format($omzet, 0, ',', '.') . "</b>\n";
        $msg .= "🔴 Belanja: <b>Rp " . number_format($belanja, 0, ',', '.') . "</b>\n";

        // Indikator sederhana
        if ($belanja > $omzet) {
            $msg .= "⚠️ <i>Warning: Pengeluaran > Pemasukan hari ini.</i>\n";
        } else {
            $msg .= "✅ <i>Cashflow harian aman.</i>\n";
        }
    }

    /**
     * Helper untuk format baris kenaikan/penurunan
     * @param bool $isExpense Jika true, Naik = Merah (Buruk), Turun = Hijau (Bagus)
     */
    private function formatGrowthLine($label, $current, $prev, $isExpense = false)
    {
        $growth = 0;
        if ($prev > 0) {
            $growth = (($current - $prev) / $prev) * 100;
        } elseif ($current > 0) {
            $growth = 100;
        }

        $icon = $growth >= 0 ? "🔼" : "🔽";
        $percent = abs(round($growth, 1));

        // Tentukan warna emotikon berdasarkan konteks
        // Income: Naik (Bagus), Turun (Jelek)
        // Expense: Naik (Jelek/Wajar), Turun (Bagus/Hemat)
        if ($isExpense) {
            $trend = $growth > 0 ? "🔴" : "🟢"; // Expense naik = Merah
        } else {
            $trend = $growth > 0 ? "🟢" : "🔴"; // Income naik = Hijau
        }

        // Format angka (Jutaan/Ribuan biar pendek)
        $currFmt = number_format($current, 0, ',', '.');

        return "{$trend} <b>{$label}: Rp {$currFmt}</b>\n       {$icon} {$percent}% (vs lalu)\n\n";
    }

    /**
     * Helper Baru: Render Blok Perbandingan Lengkap (Current vs MoM vs YoY)
     */
    private function renderComparisonBlock($label, $current, $mom, $yoy, $isExpense, $dateMoM, $dateYoY)
    {
        // Format Angka Utama
        $currFmt = number_format($current, 0, ',', '.');

        // Tentukan Header Icon (Merah/Hijau berdasarkan konteks expense)
        if ($isExpense) {
            // Kalau expense > income bulan ini (kasarannya), atau sekedar indikator
            $headerIcon = "🔴";
        } else {
            $headerIcon = "🟢";
        }

        $text = "{$headerIcon} <b>{$label}: Rp {$currFmt}</b>\n";

        // --- 1. Hitung & Render MoM (Month on Month) ---
        $growthMoM = 0;
        if ($mom > 0) $growthMoM = (($current - $mom) / $mom) * 100;
        elseif ($current > 0) $growthMoM = 100;

        $iconMoM = $growthMoM >= 0 ? "📈" : "📉";
        $pctMoM  = round(abs($growthMoM), 1);
        $lblMoM  = $dateMoM->isoFormat('MMM'); // Jan

        // Warna MoM
        if ($isExpense) {
            // Kalau expense naik = jelek (merah), turun = bagus (hijau)
            $colorMoM = $growthMoM > 0 ? "🔴" : "🟢";
        } else {
            // Kalau income naik = bagus (hijau)
            $colorMoM = $growthMoM > 0 ? "🟢" : "🔴";
        }

        $text .= "\n   {$iconMoM} vs {$lblMoM}: <b>{$colorMoM} " . ($growthMoM >= 0 ? '+' : '-') . "{$pctMoM}%</b>\n";


        // --- 2. Hitung & Render YoY (Year on Year) ---
        $growthYoY = 0;
        if ($yoy > 0) $growthYoY = (($current - $yoy) / $yoy) * 100;
        elseif ($current > 0) $growthYoY = 100;

        $iconYoY = $growthYoY >= 0 ? "🚀" : "🔻";
        $pctYoY  = round(abs($growthYoY), 1);
        $lblYoY  = $dateYoY->isoFormat('MMM Y'); // Feb 2024

        // Warna YoY
        if ($isExpense) {
            $colorYoY = $growthYoY > 0 ? "🔴" : "🟢";
        } else {
            $colorYoY = $growthYoY > 0 ? "🟢" : "🔴";
        }

        $text .= "\n   {$iconYoY} vs {$lblYoY}: <b>{$colorYoY} " . ($growthYoY >= 0 ? '+' : '-') . "{$pctYoY}%</b>\n\n";

        return $text;
    }
}
