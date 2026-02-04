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
} catch (\Exception $e) {
    $autoBackupEnabled = false;
}
if ($autoBackupEnabled) {
    // 1. LIGHT BACKUP (Setiap Jam - Kecuali jam 23:00)
    // Hanya simpan di Local, Hanya DB. Cepat (< 5 detik).
    Schedule::command('backup:run --only-db --disable-notifications')
        ->everyThreeHours()
        ->skip(function () {
            return date('H') == '22'; // Jangan jalan jam 23:00 (karena ada heavy backup)
        })
        ->timezone('Asia/Jakarta');

    // 2. HEAVY BACKUP (Setiap Hari Jam 23:00 / Tutup Toko)
    // Backup DB + Files + Upload Google Drive + Hapus Backup Lama
    Schedule::command('backup:run')
        ->dailyAt('22:00')
        ->timezone('Asia/Jakarta');

    // 3. CLEANUP (Bersihkan file lama setelah Heavy Backup)
    Schedule::command('backup:clean')
        ->dailyAt('22:15')
        ->timezone('Asia/Jakarta');
}

// === 1. LAPORAN RUTIN (Reporter) ===

// Pagi (06:30): Laporan Rencana & Restock
// (Mengambil data hasil analisa tadi malam)
Schedule::command('report:morning')->dailyAt('06:30');

// SIANG: Laporan Finansial (Jam 12:30)
Schedule::command('report:financial')->dailyAt('12:30');
// Malam (21:00): Tutup Toko
// (Hitung final omzet hari ini)
Schedule::command('report:closing')->dailyAt('21:00');

// === 2. ANALISA & PERSIAPAN (Generator) ===

// Malam (21:30): Analisa Berat
// - Menjalankan InventoryService ke semua produk
// - Menjalankan DSS (Dead Stock) jika hari Minggu
// - Menyimpan hasil ke tabel Insight untuk dibaca report:morning besok
Schedule::command('insight:generate')->dailyAt('21:30');
