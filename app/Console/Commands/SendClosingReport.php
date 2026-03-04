<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\Transaction;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SendClosingReport extends Command
{
    protected $signature = 'report:closing';

    protected $description = 'Kirim laporan tutup toko (Omzet & Profit)';

    public function handle()
    {
        $today = now()->format('Y-m-d');

        // Ambil Transaksi Hari Ini
        // Pastikan Transaction punya relasi 'details' atau kolom 'profit'
        $transactions = Sale::whereDate('created_at', $today)->get();

        if ($transactions->isEmpty()) {
            TelegramService::send("🌙 <b>Laporan Tutup Toko</b>\n\nTidak ada transaksi hari ini (Rp 0).");

            return;
        }

        // Hitung Data
        $totalOmzet = $transactions->sum('total_revenue'); // Total Penjualan
        $totalTrx = $transactions->count();
        $totalProfit = $transactions->sum('total_profit');
        $margin = $totalOmzet > 0 ? round(($totalProfit / $totalOmzet) * 100, 1) : 0;

        // Rangkai Pesan
        $msg = "🌙 <b>LAPORAN TUTUP TOKO</b>\n";
        $msg .= '🗓 '.now()->isoFormat('dddd, D MMMM Y')."\n\n";

        $msg .= "💰 <b>RINGKASAN FINANSIAL</b>\n";
        $msg .= '🔹 Omzet: <b>Rp '.number_format($totalOmzet, 0, ',', '.')."</b>\n";
        $msg .= '🔹 Profit: <b>Rp '.number_format($totalProfit, 0, ',', '.')."</b> ({$margin}%)\n";
        $msg .= "🔹 Transaksi: <b>{$totalTrx} struk</b>\n\n";

        // Breakdown Pembayaran
        $tunai = $transactions->where('payment_method', 'cash')->sum('total_revenue');
        $nonTunai = $totalOmzet - $tunai;
        $msg .= "💳 <b>METODE PEMBAYARAN</b>\n";
        $msg .= '💵 Tunai: <b>Rp '.number_format($tunai, 0, ',', '.')."</b>\n";
        $msg .= '🏧 Non-Tunai: <b>Rp '.number_format($nonTunai, 0, ',', '.')."</b>\n\n";

        $msg .= "<i>Sistem melakukan analisa malam dan backup sebentar lagi...</i>\n";
        $msg .= '<b>Selamat istirahat!</b> 😴';

        TelegramService::send($msg);
        $this->info('Laporan Closing Terkirim');
    }
}
