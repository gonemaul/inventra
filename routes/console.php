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
    Schedule::command('backup:run --only-db --only-to-disk=public --disable-notifications')
        ->hourly()
        ->skip(function () {
            return date('H') == '22'; // Jangan jalan jam 23:00 (karena ada heavy backup)
        })
        ->timezone('Asia/Jakarta');

    // 2. HEAVY BACKUP (Setiap Hari Jam 23:00 / Tutup Toko)
    // Backup DB + Files + Upload Google Drive + Hapus Backup Lama
    Schedule::command('backup:run --disable-notifications')
        ->dailyAt('22:00')
        ->timezone('Asia/Jakarta');

    // 3. CLEANUP (Bersihkan file lama setelah Heavy Backup)
    Schedule::command('backup:clean --disable-notifications')
        ->dailyAt('22:30')
        ->timezone('Asia/Jakarta');
}
// php artisan schedule:run
// Jika jam komputer Anda sekarang pas dengan jadwal (misal XX:00), perintah backup akan jalan. Jika tidak, dia akan bilang "No commands are ready to run".

// php artisan schedule:work
// Perintah ini akan mengecek setiap menit: "Apakah ada tugas yang harus dikerjakan sekarang?"