<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Logic Scheduler (Hanya info, belum dipasang sekarang)
Schedule::command('backup:run --only-db')
    ->hourly()
    ->when(function () {
        // Cek database dulu, kalau 'true' baru jalan
        return \App\Models\Setting::where('key', 'enable_auto_backup')->value('value') === 'true';
    });
