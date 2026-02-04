<?php

namespace App\Console\Commands;

use App\Models\PurchaseInvoice;
use App\Models\Sale;
use App\Models\SmartInsight;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMorningReport extends Command
{
    protected $signature = 'report:morning';

    protected $description = 'Mengirim laporan pagi dari tabel insight';

    public function handle()
    {
        // 1. FAIL-SAFE CHECK (Cek data 12 jam terakhir)
        $insights = SmartInsight::where('is_notified', false)
            ->where('created_at', '>=', now()->subHours(12))
            ->get();

        if ($insights->isEmpty()) {
            $this->info('Data kosong. Menjalankan generator paksa...');
            $this->call('insight:generate');
            $insights = SmartInsight::where('is_notified', false)
                ->where('created_at', '>=', now()->subHours(12))
                ->get();
        }

        // ==========================================
        // PERSIAPAN DATA (Query di Awal biar Rapi)
        // ==========================================

        // A. Data Restock
        $restockRaw = $insights->firstWhere('type', SmartInsight::TYPE_DAILY_RESTOCK); // Pastikan constant benar

        // B. Data Strategy (High Margin & Price Alert)
        $stratRaw = $insights->firstWhere('type', 'daily_strategy'); // atau constant
        $stratData = $stratRaw ? ($stratRaw->payload['all'] ?? null) : null;

        // C. Data Tagihan Supplier (PurchaseInvoice) - H-0 s/d H-3
        $dueInvoices = PurchaseInvoice::with('purchase:supplier')->where('payment_status', '!=', 'paid')
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(3))
            ->orderBy('due_date', 'asc')
            ->get();

        // ==========================================
        // RANGKAI PESAN (HIERARKI VISUAL)
        // ==========================================

        $message = "☀️ <b>MORNING BRIEFING</b>\n";
        $message .= '🗓 '.now()->isoFormat('dddd, D MMMM Y')."\n\n";
        $message .= "------------------------------------------------------------\n\n";
        // ------------------------------------------
        // BLOK 1: PERHATIAN KHUSUS (MERAH)
        // Gabungan Tagihan Supplier & Alert Harga Naik
        // ------------------------------------------
        $hasUrgent = false;
        $priceAlertMsg = null;

        // Cek Price Alert dari Payload Strategy
        if ($stratData && ! empty($stratData['price_trend']['has_alert']) && $stratData['price_trend']['has_alert'] === true) {
            $priceAlertMsg = $stratData['price_trend']['message'] ?? 'Perubahan harga terdeteksi.';
        }

        if ($dueInvoices->count() > 0 || $priceAlertMsg) {
            $message .= "🔴 <b>PERLU PERHATIAN (URGENT)</b>\n\n";
            $hasUrgent = true;

            // 1. List Tagihan
            if ($dueInvoices->count() > 0) {
                foreach ($dueInvoices as $inv) {
                    $diff = round(now()->diffInDays(Carbon::parse($inv->due_date), false));

                    // Logic Label Waktu
                    if ($diff < 0) {
                        $label = '🔥 LEWAT JATUH TEMPO';
                    } elseif ($diff == 0) {
                        $label = '🚨 HARI INI';
                    } else {
                        $label = "⏳ H-{$diff}";
                    }

                    $sisa = number_format($inv->total_amount - $inv->amount_paid, 0, ',', '.');
                    $supplierName = $inv->purchase->supplier->name ?? 'Supplier'; // Asumsi relasi ada

                    $message .= "• <b>{$supplierName}</b> ({$label})\n";
                    $message .= "   Inv: #{$inv->invoice_number} (Rp {$sisa})\n";
                }
                $message .= "\n";
            }

            // 2. List Price Alert
            if ($priceAlertMsg) {
                $prodName = $stratData['product_snapshot']['name'] ?? 'Produk';
                $message .= "⚠️ <b>ALERT HARGA MODAL</b>\n";
                $message .= "   • <b>{$prodName}</b>\n";
                $message .= "   <i>{$priceAlertMsg}</i>\n";
            }

            $message .= "\n------------------------------------------------------------\n\n";
        }

        // ------------------------------------------
        // BLOK 2: RENCANA BELANJA (BIRU)
        // ------------------------------------------
        if ($restockRaw) {
            $p = $restockRaw->payload;
            $message .= "📦 <b>RENCANA BELANJA (RESTOCK)</b>\n";

            // Limit 5 item
            $items = array_slice($p['items'], 0, 5);
            foreach ($items as $item) {
                $msgEst = number_format(($item['est'] ?? 0) / 1000, 0).'rb'; // "150rb"
                $message .= "• <b>{$item['name']}</b> (Sisa {$item['current_stock']}) - Est: {$msgEst}\n";
            }

            if (count($p['items']) > 5) {
                $sisaC = count($p['items']) - 5;
                $message .= "<i>...dan {$sisaC} item lainnya.</i>\n";
            }

            $totalRp = number_format($p['total_biaya'] ?? 0, 0, ',', '.');
            $message .= "\n💰 <b>Siapkan Dana: Rp {$totalRp}</b>\n";
        } else {
            $message .= "📦 <b>Restock:</b> Stok Aman.\n";
        }

        $message .= "\n------------------------------------------------------------\n\n";

        // ------------------------------------------
        // BLOK 3: STRATEGI CUAN (HIJAU)
        // ------------------------------------------
        if ($stratData) {
            $message .= "🎯 <b>STRATEGI FOKUS (HIGH MARGIN)</b>\n";

            $itemName = $stratData['product_snapshot']['name'] ?? 'Unknown';
            $cuanRp = number_format($stratData['margin']['rp'] ?? 0, 0, ',', '.');
            $persen = $stratData['margin']['percent'] ?? 0;

            $message .= "🔥 <b>{$itemName}</b>\n";
            $message .= "   Potensi Cuan: <b>Rp {$cuanRp} ({$persen}%)</b>\n";
            $message .= "   <i>\"Tawarkan produk ini untuk profit maksimal!\"</i>\n";
        }

        // ------------------------------------------
        // FOOTER
        // ------------------------------------------
        $message .= "\n🚀 <b>Semangat Bos!</b>";

        // 3. KIRIM TELEGRAM
        $sent = TelegramService::send($message);

        // 4. UPDATE STATUS DATABASE
        if ($sent) {
            // SmartInsight::whereIn('id', $insights->pluck('id'))->update(['is_notified' => true]);
            $this->info('Laporan pagi terkirim.');
        } else {
            $this->error('Gagal kirim Telegram.');
        }

        // Bulanan
        if (now()->day === 1) {
            // Hitung Rekap Bulan Lalu
            $lastMonth = now()->subMonth();
            $bulanLalu = Sale::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)->get();
            $omzetBulanLalu = $bulanLalu->sum('total_revenue');
            $profitBulanLalu = $bulanLalu->sum('total_profit');

            $totalTrx = Sale::whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year)
                ->count();

            $msgBulanan = '🗓 <b>REKAP BULANAN ('.$lastMonth->isoFormat('MMMM Y').")</b>\n";
            $msgBulanan .= 'Total Omzet: <b>Rp '.number_format($omzetBulanLalu, 0, ',', '.')."</b>\n";
            $msgBulanan .= 'Total Profit: <b>Rp '.number_format($profitBulanLalu, 0, ',', '.')."</b>\n";
            $msgBulanan .= "Total Transaksi: {$totalTrx} struk\n";
            $msgBulanan .= "<i>Performance review bulan baru dimulai!</i> 🚀\n\n";

            // Kirim terpisah atau gabung ke laporan pagi
            TelegramService::send($msgBulanan);
        }
    }
}
