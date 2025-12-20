<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\TelegramService;

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
        $totalTrx   = $transactions->count();

        // Asumsi Anda punya cara hitung profit (Gross Profit)
        // Misal: $trx->total - $trx->modal
        $totalProfit = $transactions->sum('total_profit');

        // Rangkai Pesan
        $msg = "🌙 <b>LAPORAN TUTUP TOKO</b>\n";
        $msg .= "🗓 " . now()->isoFormat('D MMM Y') . "\n\n";

        $msg .= "💰 <b>FINANSIAL</b>\n";
        $msg .= "Omzet: <b>Rp " . number_format($totalOmzet, 0, ',', '.') . "</b>\n";
        $msg .= "Profit: <b>Rp " . number_format($totalProfit, 0, ',', '.') . "</b>\n";
        $msg .= "Trx: {$totalTrx} struk\n\n";

        // Breakdown Pembayaran (Opsional, sesuaikan kolom di DB Anda)
        $tunai = $transactions->where('payment_method', 'cash')->sum('total_revenue');
        $msg .= "💵 Tunai: Rp " . number_format($tunai) . "\n";

        $msg .= "Sistem melakukan analisa malam dan backup sebentar lagi...\n";
        $msg .= "Selamat istirahat! 😴";

        TelegramService::send($msg);
        $this->info('Laporan Closing Terkirim');
    }
}
