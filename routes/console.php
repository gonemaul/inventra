<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek apakah fitur Auto Backup diaktifkan di Database?
// Kita bungkus dalam try-catch agar tidak error saat migrate awal
try {
    $autoBackupEnabled = Setting::where('key', 'enable_auto_backup')->value('value') === 'true';
    $dailyTime = Setting::where('key', 'backup_daily_time')->value('value') ?? '22:00';
    $frequency = Setting::where('key', 'backup_frequency')->value('value') ?? '3'; 
    $morningTime = Setting::where('key', 'report_morning_time')->value('value') ?? '06:30';
    $financialTime = Setting::where('key', 'report_financial_time')->value('value') ?? '12:30';
    $closingTime = Setting::where('key', 'report_closing_time')->value('value') ?? '21:00';
    $insightTime = Setting::where('key', 'insight_generate_time')->value('value') ?? '21:30';
} catch (\Exception $e) {
    $autoBackupEnabled = false;
    // Default values if DB fails
    $dailyTime = '22:00';
    $frequency = '3';
    $morningTime = '06:30';
    $financialTime = '12:30';
    $closingTime = '21:00';
    $insightTime = '21:30';
}
if ($autoBackupEnabled) {
    // 1. LIGHT BACKUP (Custom Frequency)
    // Hanya simpan di Local, Hanya DB. Cepat (< 5 detik).
    Schedule::command('backup:run --only-db --disable-notifications')
        ->cron("0 */{$frequency} * * *") // Jalan setiap x jam
        ->skip(function () use ($dailyTime) {
            // Jangan jalan jika berdekatan dengan heavy backup (jam sama)
            return date('H:i') == $dailyTime; 
        })
        ->timezone('Asia/Jakarta');

    // 2. HEAVY BACKUP (Custom Time)
    // Backup DB + Files + Upload Google Drive + Hapus Backup Lama
    Schedule::command('backup:run')
        ->dailyAt($dailyTime)
        ->timezone('Asia/Jakarta');

    // 3. CLEANUP (Bersihkan file lama setelah Heavy Backup - +15 menit dari daily time)
    $cleanupTime = date('H:i', strtotime($dailyTime) + 900); // 15 menit setelah backup
    Schedule::command('backup:clean')
        ->dailyAt($cleanupTime)
        ->timezone('Asia/Jakarta');
}

// === 1. LAPORAN RUTIN (Reporter) ===

// Pagi: Laporan Rencana & Restock
Schedule::command('report:morning')->dailyAt($morningTime);

// SIANG: Laporan Finansial
Schedule::command('report:financial')->dailyAt($financialTime);
// Malam: Tutup Toko
Schedule::command('report:closing')->dailyAt($closingTime);

// === 2. ANALISA & PERSIAPAN (Generator) ===

// Malam: Analisa Berat (Insight)
// - Menjalankan InventoryService ke semua produk
// - Menjalankan DSS (Dead Stock) jika hari Minggu
// - Menyimpan hasil ke tabel Insight untuk dibaca report:morning besok
Schedule::command('insight:generate')->dailyAt($insightTime);
