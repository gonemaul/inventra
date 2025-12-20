<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\Insight;
use Illuminate\Console\Command;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

class SendAfternoonReport extends Command
{
    protected $signature = 'report:afternoon';
    public function handle()
    {
        // Ambil trx dari jam 07:00 sampai 13:00
        $omzet = Sale::whereDate('created_at', today())
            ->whereTime('created_at', '<=', '13:00:00')
            ->sum('total');

        $msg = "🌤 <b>UPDATE SIANG (13:00)</b>\n";
        $msg .= "Omzet Sesi 1: <b>Rp " . number_format($omzet, 0, ',', '.') . "</b>\n";
        $msg .= "Semangat lanjut sesi 2! 🔥";

        TelegramService::send($msg);
    }
}
