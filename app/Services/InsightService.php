<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use App\Models\SmartInsight;
use Illuminate\Support\Facades\DB;
use App\Models\SalesRecap; // Pastikan model ini sesuai dengan model Penjualan Anda

class InsightService
{
    /**
     * FUNGSI UTAMA: Menjalankan semua analisa
     */
    public function runAnalysis()
    {
        $this->analyzeSmartRestock();
        $this->analyzeDeadStock();
        $this->analyzeTrend();   // <--- BARU
        $this->analyzeMargins();
    }

    /**
     * FITUR DASHBOARD: Hitung Skor Kesehatan Toko (0-100)
     */
    public function calculateShopHealth()
    {
        // 1. Indikator Stok (Bobot 50%)
        // Logika: Semakin sedikit barang kritis, semakin bagus.
        $totalProducts = Product::count();
        if ($totalProducts == 0) return 100; // Toko baru dianggap sehat

        $criticalStockCount = SmartInsight::where('type', 'restock')
            ->where('severity', 'critical')
            ->count();

        // Rumus: Jika 10% barang kritis, nilai turun.
        $stockHealth = max(0, 100 - (($criticalStockCount / $totalProducts) * 100 * 2));

        // 2. Indikator Keuangan (Bobot 50%)
        // Logika: Semakin sedikit hutang jatuh tempo, semakin bagus.
        $overdueDebts = DB::table('purchase_invoices')
            ->where('payment_status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->count();

        // Tiap 1 nota jatuh tempo mengurangi nilai 10 poin
        $financeHealth = max(0, 100 - ($overdueDebts * 10));

        // Gabungkan (Rata-rata)
        $totalScore = round(($stockHealth + $financeHealth) / 2);

        // Tentukan Status Teks
        $status = 'Sehat Walafiat';
        $color = 'text-green-600';

        if ($totalScore < 50) {
            $status = 'Kritis (Perlu Tindakan)';
            $color = 'text-red-600';
        } elseif ($totalScore < 80) {
            $status = 'Kurang Fit (Demam Ringan)';
            $color = 'text-yellow-600';
        }

        return [
            'score' => $totalScore,
            'status' => $status,
            'color' => $color,
            'details' => [
                'stock_score' => $stockHealth,
                'finance_score' => $financeHealth
            ]
        ];
    }

    /**
     * FITUR DASHBOARD: Proyeksi Cashflow 7 Hari Ke Depan
     */
    public function predictCashflow()
    {
        // 1. Estimasi Uang Masuk (IN)
        // Ambil rata-rata omzet harian 30 hari terakhir x 7 hari ke depan
        $avgDailyRevenue = Sale::where('transaction_date', '>=', now()->subDays(30))
            ->avg('total_revenue') ?? 0;

        $projectedIn = $avgDailyRevenue * 7;

        // 2. Pasti Uang Keluar (OUT)
        // Hutang yang jatuh tempo dalam 7 hari ke depan
        $projectedOut = DB::table('purchase_invoices')
            ->where('payment_status', '!=', 'paid')
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->sum(DB::raw('total_amount - amount_paid'));

        // 3. Kesimpulan
        $balance = $projectedIn - $projectedOut;

        return [
            'in' => $projectedIn,
            'out' => $projectedOut,
            'balance' => $balance,
            'status' => $balance >= 0 ? 'safe' : 'danger',
            'message' => $balance >= 0
                ? "Aman. Surplus sekitar " . number_format($balance, 0, ',', '.')
                : "AWAS! Defisit " . number_format(abs($balance), 0, ',', '.') . ". Siapkan dana talangan."
        ];
    }

    /**
     * KECERDASAN 1: SMART RESTOCK
     * Menggabungkan Min Stock (Statis) & Forecasting (Dinamis)
     */
    protected function analyzeSmartRestock()
    {
        // Ambil semua produk aktif
        $products = Product::with('supplier')->get();

        // Ambil data penjualan 30 hari terakhir untuk forecasting
        $startDate = Carbon::now()->subDays(30);

        foreach ($products as $product) {
            // A. LOGIC DATA PENJUALAN
            // Hitung total qty terjual 30 hari terakhir
            // (Sesuaikan query ini dengan struktur tabel sale_items Anda)
            $totalSold30Days = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $product->id)
                ->where('sales.transaction_date', '>=', $startDate)
                ->sum('sale_items.quantity');

            // Hitung Rata-rata per hari (Daily Burn Rate)
            $avgDailySales = $totalSold30Days / 30;

            // B. LOGIC DETEKSI
            $isCritical = false;
            $message = '';
            $payload = [];

            // Skenario 1: Stok Kosong Melompong (Realtime)
            if ($product->stock <= 0) {
                $isCritical = true;
                $message = "Stok HABIS! Pelanggan tidak bisa beli.";
            }
            // Skenario 2: Forecasting (Prediksi Habis < 3 Hari)
            elseif ($avgDailySales > 0) {
                $daysRemaining = $product->stock / $avgDailySales;

                if ($daysRemaining < 3) {
                    $isCritical = true;
                    $estDate = Carbon::now()->addDays(ceil($daysRemaining))->isoFormat('dddd, D MMM');
                    $message = "Diprediksi HABIS dalam " . ceil($daysRemaining) . " hari ({$estDate}).";
                    $payload['avg_daily'] = number_format($avgDailySales, 1);
                    $payload['days_left'] = ceil($daysRemaining);
                }
            }
            // Skenario 3: Min Stock Manual (Cadangan logic jika data penjualan belum cukup)
            elseif ($product->stock <= $product->min_stock) {
                $isCritical = true;
                $message = "Stok di bawah batas minimum ({$product->min_stock}).";
            }

            // C. EKSEKUSI (SIMPAN KE TABLE INSIGHT)
            if ($isCritical) {
                // Simpan atau Update Insight yang sudah ada (biar gak spam duplikat)
                SmartInsight::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'type' => 'restock', // Kunci unik per produk per tipe
                        'is_read' => false,  // Hanya update jika belum dibaca
                    ],
                    [
                        'severity' => ($product->stock <= 0) ? 'critical' : 'warning',
                        'title' => ($product->stock <= 0) ? 'Stok Habis: ' . $product->name : 'Restock Segera: ' . $product->name,
                        'message' => $message,
                        'payload' => array_merge($payload, [
                            'current_stock' => $product->stock,
                            'min_stock' => $product->min_stock,
                            'supplier' => $product->supplier->name ?? 'Tidak ada'
                        ]),
                        'action_url' => '/purchases/create?product_id=' . $product->id,
                        'updated_at' => now(), // Refresh waktu agar naik ke atas
                    ]
                );
            } else {
                // Jika stok sudah aman (misal baru belanja), HAPUS insight lama otomatis
                SmartInsight::where('product_id', $product->id)
                    ->where('type', 'restock')
                    ->delete();
            }
        }
    }

    /**
     * KECERDASAN 2: DEAD STOCK DETECTOR
     * Mencari barang yang menimbun uang (Aset Mandek)
     */
    protected function analyzeDeadStock()
    {
        // Kriteria Dead Stock: Stok > 0 DAN Tidak laku > 90 Hari
        $thresholdDate = Carbon::now()->subDays(90);

        // Ambil produk yang stoknya ada
        $products = Product::where('stock', '>', 0)->get();

        foreach ($products as $product) {

            // Cari tanggal transaksi terakhir
            // Asumsi: Relasi product -> saleItems -> sale
            $lastSale = DB::table('sale_items') // Sesuaikan nama tabel item penjualan Anda
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $product->id)
                ->orderBy('sales.transaction_date', 'desc')
                ->first();

            $isDeadStock = false;
            $daysInactive = 0;

            if ($lastSale) {
                // Jika pernah laku, hitung hari sejak transaksi terakhir
                $lastDate = Carbon::parse($lastSale->transaction_date);
                if ($lastDate->lt($thresholdDate)) {
                    $isDeadStock = true;
                    $daysInactive = $lastDate->diffInDays(now());
                }
            } else {
                // Jika BELUM PERNAH laku sama sekali
                // Cek kapan produk dibuat (created_at)
                $createdDate = Carbon::parse($product->created_at);
                if ($createdDate->lt($thresholdDate)) {
                    $isDeadStock = true;
                    $daysInactive = $createdDate->diffInDays(now());
                }
            }

            // EKSEKUSI: Simpan ke Insight
            if ($isDeadStock) {
                // Hitung nilai uang yang mandek
                $frozenAsset = $product->stock * $product->purchase_price;

                SmartInsight::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'type' => 'dead_stock',
                    ],
                    [
                        'severity' => 'warning', // Kuning (Hati-hati)
                        'title' => 'Barang Mati: ' . $product->name,
                        'message' => "Tidak laku selama {$daysInactive} hari. Uang mandek Rp " . number_format($frozenAsset, 0, ',', '.'),
                        'payload' => [
                            'days_inactive' => $daysInactive,
                            'frozen_asset' => $frozenAsset,
                            'current_stock' => $product->stock
                        ],
                        'action_url' => '/products/' . $product->id, // Link ke detail produk
                        'updated_at' => now(),
                    ]
                );
            } else {
                // Jika barang sudah laku lagi, hapus label dead stock
                SmartInsight::where('product_id', $product->id)
                    ->where('type', 'dead_stock')
                    ->delete();
            }
        }
    }

    /**
     * KECERDASAN 3: TREND WATCHER (Pendeteksi Tren)
     * Mencari barang yang penjualannya melonjak (Fast Moving)
     */
    protected function analyzeTrend()
    {
        // Bandingkan Penjualan: 30 Hari Terakhir vs 30 Hari Sebelumnya
        $thisMonthStart = Carbon::now()->subDays(30);
        $lastMonthStart = Carbon::now()->subDays(60);

        $products = Product::all();

        foreach ($products as $product) {
            // Hitung Qty Bulan Ini
            $qtyThisMonth = SaleItem::where('product_id', $product->id)
                ->whereHas('sale', fn($q) => $q->where('transaction_date', '>=', $thisMonthStart))
                ->sum('quantity');

            // Hitung Qty Bulan Lalu
            $qtyLastMonth = SaleItem::where('product_id', $product->id)
                ->whereHas('sale', fn($q) => $q->whereBetween('transaction_date', [$lastMonthStart, $thisMonthStart]))
                ->sum('quantity');

            // SYARAT TRENDING:
            // 1. Bulan ini laku minimal 10 item (biar ga false alarm barang receh)
            // 2. Kenaikan > 30% dibanding bulan lalu

            if ($qtyThisMonth >= 10 && $qtyThisMonth > ($qtyLastMonth * 1.3)) {

                $growth = $qtyLastMonth > 0
                    ? round((($qtyThisMonth - $qtyLastMonth) / $qtyLastMonth) * 100)
                    : 100; // Jika bulan lalu 0, dianggap naik 100%

                SmartInsight::updateOrCreate(
                    ['product_id' => $product->id, 'type' => 'trend'],
                    [
                        'severity' => 'info', // Biru (Kabar Baik)
                        'title' => 'Produk Trending: ' . $product->name,
                        'message' => "Penjualan naik {$growth}% dibanding periode lalu (Total: {$qtyThisMonth} pcs).",
                        'payload' => [
                            'growth_percent' => $growth,
                            'qty_now' => $qtyThisMonth,
                            'qty_prev' => $qtyLastMonth
                        ],
                        'updated_at' => now(),
                    ]
                );
            } else {
                // Jika performa turun/biasa aja, hapus status trending
                SmartInsight::where('product_id', $product->id)->where('type', 'trend')->delete();
            }
        }
    }

    /**
     * KECERDASAN 4: MARGIN GUARDIAN (Penjaga Profit)
     * Mendeteksi produk yang marginnya terlalu tipis akibat kenaikan modal
     */
    protected function analyzeMargins()
    {
        // Batas Margin Kritis (Misal: Profit di bawah 10% dianggap bahaya)
        $MIN_MARGIN_PERCENT = 10;

        $products = Product::where('purchase_price', '>', 0)->get();

        foreach ($products as $product) {
            $profit = $product->selling_price - $product->purchase_price;
            $marginPercent = ($profit / $product->purchase_price) * 100;

            if ($marginPercent < $MIN_MARGIN_PERCENT) {

                // Cek apakah baru-baru ini ada kenaikan harga beli?
                // Kita ambil dari logic Price Trend sederhana
                $lastPurchase = PurchaseItem::where('product_id', $product->id)->latest()->first();
                $isPriceHike = false;

                if ($lastPurchase && $lastPurchase->product_snapshot) {
                    $oldPrice = $lastPurchase->product_snapshot['purchase_price'] ?? 0;
                    if ($product->purchase_price > $oldPrice) $isPriceHike = true;
                }

                SmartInsight::updateOrCreate(
                    ['product_id' => $product->id, 'type' => 'margin_alert'],
                    [
                        'severity' => 'warning', // Kuning (Peringatan)
                        'title' => 'Margin Menipis: ' . $product->name,
                        'message' => $isPriceHike
                            ? "Harga modal NAIK tapi harga jual belum disesuaikan. Margin sisa " . number_format($marginPercent, 1) . "%."
                            : "Profit produk ini terlalu kecil (Hanya " . number_format($marginPercent, 1) . "%). Pertimbangkan naikkan harga.",
                        'payload' => [
                            'margin_percent' => $marginPercent,
                            'profit_rp' => $profit,
                            'purchase_price' => $product->purchase_price
                        ],
                        'action_url' => '/products/' . $product->id . '/edit', // Arahkan ke edit harga
                        'updated_at' => now(),
                    ]
                );
            } else {
                // Jika margin sudah sehat, hapus alert
                SmartInsight::where('product_id', $product->id)->where('type', 'margin_alert')->delete();
            }
        }
    }
}
