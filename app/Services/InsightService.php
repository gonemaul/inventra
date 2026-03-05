<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseInvoice;
use App\Models\Sale;
use App\Models\SmartInsight;
use App\Services\Analysis\Product\ClassificationAnalyzer;
use App\Services\Analysis\ProductAssociationAnalyzer;
use App\Services\Analysis\ProductDSSCalculator;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Pastikan model ini sesuai dengan model Penjualan Anda

class InsightService
{
    protected $calculator;
    protected $associationAnalyzer;

    public function __construct(ProductDSSCalculator $calculator, ProductAssociationAnalyzer $associationAnalyzer)
    {
        $this->calculator = $calculator;
        $this->associationAnalyzer = $associationAnalyzer;
    }

    /**
     * =========================================================================
     * 1. MASTER BATCH (UNTUK CRONJOB / JADWAL HARIAN)
     * =========================================================================
     * Fungsi ini sangat efisien. Hanya mengambil data produk sekali,
     * menghitung semua metrik sekali, lalu mendistribusikan ke semua analisa.
     */
    public function runScheduledAnalysis()
    {
        // Pre-load batch stats agar ClassificationAnalyzer tidak N+1 query
        $classificationAnalyzer = $this->calculator->getClassificationAnalyzer();
        $batchRevenue    = $classificationAnalyzer->getBatchRevenue();
        $batchMonthlyQty = $classificationAnalyzer->getBatchMonthlyQtys();
        $totalRevenue    = $batchRevenue->sum();

        Product::with(['category', 'unit'])
            ->chunk(100, function ($products) use ($batchRevenue, $batchMonthlyQty, $totalRevenue) {
                foreach ($products as $product) {

                    // 1. HITUNG SEMUA DATA
                    $analysis = $this->calculator->getFullAnalysis($product);

                    // 2. DISPATCH KE MASING-MASING PROCESSOR

                    // Group Inventory
                    $this->processRestockInsight($product, $analysis['inventory']);
                    $this->processDeadStockInsight($product, $analysis['inventory']);

                    // Group Financial
                    $this->processMarginInsight($product, $analysis['financial']);
                    $this->processTrendInsight($product, $analysis['financial']);
                    $this->processNewProductInsight($product, $analysis['financial']);
                    $this->processHighMarginInsight($product, $analysis['financial']);

                    // Group AI Pricing
                    $this->processPricingInsight($product, $analysis['financial']);

                    // Group Classification (ABC/XYZ)
                    $productRevenue = (float) ($batchRevenue->get($product->id, 0));
                    $monthlyQtys    = $batchMonthlyQty->get($product->id, array_fill(0, 12, 0));
                    $classification = $this->calculator->getClassification($product, $productRevenue, $totalRevenue, $monthlyQtys);
                    $this->processClassificationInsight($product, $classification);

                    // Group Seasonal Restocking
                    $avgVelocity = (float) ($analysis['inventory']['avg_daily'] ?? 0);
                    $seasonal = $this->calculator->getSeasonalRestock($product, $avgVelocity);
                    $this->processSeasonalRestockInsight($product, $seasonal);

                    // Group Capital Efficiency
                    $capital = $this->calculator->getCapitalEfficiency($product);
                    $this->processCapitalEfficiencyInsight($product, $capital);
                }
            });

        // Jalankan analisa bundling secara global (tidak per-produk)
        $this->runBundlingAnalysis();
    }

    /**
     * Analisa bundling/asosiasi produk — dijalankan setelah per-produk selesai.
     * Membersihkan pair lama dan menyimpan pair baru yang memenuhi threshold.
     */
    public function runBundlingAnalysis(): void
    {
        // Hapus semua bundling rekomendasi lama agar tidak stale
        SmartInsight::where('type', SmartInsight::TYPE_BUNDLING)->delete();

        $pairs = $this->associationAnalyzer->findTopPairs();
        if ($pairs->isEmpty()) return;

        // Load data produk yang terlibat saja (efisiensi)
        $productIds = $pairs->flatMap(fn($p) => [$p['product_id_a'], $p['product_id_b']])->unique()->values();
        $products = Product::whereIn('id', $productIds)->get();

        $recommendations = $this->associationAnalyzer->buildBundlingRecommendations($pairs, $products);

        // Simpan top 20 rekomendasi bundling (hindari noise)
        foreach ($recommendations->take(20) as $rec) {
            $productA = $rec['product_a'];
            $productB = $rec['product_b'];

            // Insight dikaitkan ke produk A dengan referensi ke produk B di payload
            SmartInsight::create([
                'product_id' => $productA->id,
                'type'       => SmartInsight::TYPE_BUNDLING,
                'severity'   => SmartInsight::SEVERITY_INFO,
                'title'      => "Bundling: {$productA->name} + {$productB->name}",
                'message'    => $rec['recommendation'],
                'payload'    => $rec,
                'action_url' => '/products/'.$productA->slug,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * FITUR DASHBOARD: Hitung Skor Kesehatan Toko (0-100)
     */
    public function calculateShopHealth()
    {
        $totalProducts = Product::count();
        if ($totalProducts == 0) {
            return 100;
        }

        // Gunakan Konstanta disini
        $criticalStockCount = SmartInsight::where('type', SmartInsight::TYPE_RESTOCK)
            ->where('severity', SmartInsight::SEVERITY_CRITICAL)
            ->count();

        $stockHealth = round(max(0, 100 - (($criticalStockCount / $totalProducts) * 100 * 2)));

        $overdueDebts = DB::table('purchase_invoices')
            ->where('payment_status', '!=', PurchaseInvoice::PAYMENT_STATUS_PAID)
            ->where('due_date', '<', now())
            ->count();

        $financeHealth = max(0, 100 - ($overdueDebts * 10));
        $totalScore = round(($stockHealth + $financeHealth) / 2);

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
                'finance_score' => $financeHealth,
            ],
        ];
    }

    /**
     * FITUR DASHBOARD: Proyeksi Cashflow 7 Hari Ke Depan
     */
    public function predictCashflow()
    {
        $avgDailyRevenue = Sale::where('transaction_date', '>=', now()->subDays(30))
            ->avg('total_revenue') ?? 0;

        $projectedIn = $avgDailyRevenue * 7;

        $projectedOut = DB::table('purchase_invoices')
            ->join('purchases', 'purchase_invoices.purchase_id', '=', 'purchases.id') // Join Manual
            ->where('purchases.status', Purchase::STATUS_COMPLETED)
            ->where('purchase_invoices.payment_status', '!=', PurchaseInvoice::PAYMENT_STATUS_PAID)
            ->whereBetween('purchase_invoices.due_date', [now(), now()->addDays(7)])
            ->sum(DB::raw('purchase_invoices.total_amount - purchase_invoices.amount_paid'));

        $balance = $projectedIn - $projectedOut;

        return [
            'in' => $projectedIn,
            'out' => $projectedOut,
            'balance' => $balance,
            'status' => $balance >= 0 ? 'safe' : 'danger',
            'message' => $balance >= 0
                ? 'Aman. Surplus sekitar '.number_format($balance, 0, ',', '.')
                : 'AWAS! Defisit '.number_format(abs($balance), 0, ',', '.').'. Siapkan dana talangan.',
        ];
    }

    /*
     * =========================================================================
     * 2. SINGLE FUNCTIONS (UNTUK PEMANGGILAN MANUAL/TERPISAH)
     * =========================================================================
     * Fungsi ini tetap bisa dipanggil sendiri-sendiri misal saat user klik
     * tombol "Refresh Restock" di dashboard.
     */

    public function analyzeSmartRestock($saveToDb = false)
    {
        $results = [];
        Product::chunk(100, function ($products) use ($saveToDb, &$results) {
            foreach ($products as $product) {
                $metrics = $this->calculator->calculateInventoryHealth($product);
                if ($metrics['status'] === SmartInsight::SEVERITY_CRITICAL || $metrics['status'] === SmartInsight::SEVERITY_WARNING) {
                    $data = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'current_stock' => $metrics['current_stock'],
                        'suggested_qty' => $metrics['suggested_qty'],
                        'buy_price' => $product->purchase_price,
                        'estimasi_biaya' => $metrics['suggested_qty'] * $product->purchase_price,
                        'days_left' => $metrics['days_left'],
                        'status' => $metrics['status'],
                    ];
                    $results[] = $data;
                }
                if ($saveToDb) {
                    $this->processRestockInsight($product, $metrics);
                }
            }
        });

        return $results;
    }

    public function analyzeDeadStock($saveToDb = false)
    {
        $results = [];
        Product::where('stock', '>', 0)->chunk(100, function ($products) use ($saveToDb, &$results) {
            foreach ($products as $product) {
                $metrics = $this->calculator->calculateInventoryHealth($product);
                if ($metrics['is_dead_stock']) {
                    $results[] = $metrics;
                }
                if ($saveToDb) {
                    $this->processDeadStockInsight($product, $metrics);
                }
            }
        });

        return $results;
    }

    public function analyzeMargins($saveToDb = false)
    {
        $results = [];
        Product::where('purchase_price', '>', 0)->chunk(100, function ($products) use ($saveToDb, &$results) {
            foreach ($products as $product) {
                $metrics = $this->calculator->calculateFinancialHealth($product);
                if ($metrics['margin']['is_critical']) {
                    $results[] = $metrics;
                }
                if ($saveToDb) {
                    $this->processMarginInsight($product, $metrics);
                }
            }
        });

        return $results;
    }

    public function analyzeTrend($saveToDb = false)
    {
        $results = [];
        Product::chunk(100, function ($products) use ($saveToDb, &$results) {
            foreach ($products as $product) {
                $metrics = $this->calculator->calculateFinancialHealth($product);
                if ($metrics['sales_trend']) {
                    $results[] = $metrics;
                }
                if ($saveToDb) {
                    $this->processTrendInsight($product, $metrics);
                }
            }
        });

        return $results;
    }

    public function analyzeNewProducts($saveToDb = false)
    {
        $results = [];
        Product::chunk(100, function ($products) use ($saveToDb, &$results) {
            foreach ($products as $product) {
                $metrics = $this->calculator->calculateFinancialHealth($product);
                if ($metrics['lifecycle']['is_new_product']) {
                    $results[] = $metrics;
                }
                if ($saveToDb) {
                    $this->processNewProductInsight($product, $metrics);
                }
            }
        });

        return $results;
    }

    public function analyzeHighMargins($saveToDb = false)
    {
        $results = [];
        Product::chunk(100, function ($products) use ($saveToDb, &$results) {
            foreach ($products as $product) {
                $metrics = $this->calculator->calculateFinancialHealth($product);
                if ($metrics['margin']['is_high_margin']) {
                    $metrics['product_snapshot'] = $product;
                    $results[] = $metrics;
                }
                if ($saveToDb) {
                    $this->processHighMarginInsight($product, $metrics);
                }
            }
        });

        return $results;
    }

    /**
     * =========================================================================
     * 3. PRIVATE PROCESSORS (LOGIC PENYIMPANAN DB)
     * =========================================================================
     * Bagian ini yang melakukan Create/Update/Delete ke tabel smart_insights.
     * Menerima $product dan $metrics (hasil hitungan) sebagai parameter.
     */
    private function processRestockInsight(Product $product, array $metrics)
    {
        if ($metrics['status'] === SmartInsight::SEVERITY_CRITICAL || $metrics['status'] === SmartInsight::SEVERITY_WARNING) {
            // Build a descriptive title with velocity info
            $velocityHint = $metrics['avg_daily'] > 0
                ? " (~{$metrics['avg_daily']} pcs/hari)"
                : '';

            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_RESTOCK],
                [
                    'severity' => $metrics['status'],
                    'title' => 'Perlu Restock: '.$product->name.$velocityHint,
                    'message' => $metrics['message'],
                    'payload' => $metrics,
                    'action_url' => '/purchases/create?product_slug='.$product->slug,
                    'updated_at' => now(),
                ]
            );

            // Telegram — hanya untuk CRITICAL (stok < safety stock, akan habis < 3 hari)
            if ($metrics['status'] === SmartInsight::SEVERITY_CRITICAL) {
                $cacheKey = 'notif_restock_critical_'.$product->id;
                if (! Cache::has($cacheKey)) {
                    $daysLeft = $metrics['days_left'] ?? '?';
                    $stockNow = $metrics['stock'] ?? $product->stock ?? '?';
                    $msg  = "🚨 <b>STOK KRITIS: PERLU BELI SEKARANG!</b>\n\n";
                    $msg .= "📦 <b>{$product->name}</b>\n";
                    $msg .= "📉 Stok Sekarang: <b>{$stockNow} pcs</b>\n";
                    $msg .= "⏰ Sisa: <b>~{$daysLeft} hari</b>\n";
                    $msg .= "📈 Kecepatan jual: <b>{$metrics['avg_daily']} pcs/hari</b>\n\n";
                    $msg .= "👉 Segera lakukan pembelian di aplikasi.";
                    TelegramService::send($msg);
                    Cache::put($cacheKey, true, now()->addHours(6));
                }
            }
        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_RESTOCK)->delete();
        }
    }

    private function processDeadStockInsight(Product $product, array $metrics)
    {
        if ($metrics['is_dead_stock']) {
            $frozenRp = number_format($metrics['frozen_asset'], 0, ',', '.');

            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_DEAD_STOCK],
                [
                    'severity' => SmartInsight::SEVERITY_WARNING,
                    'title' => 'Barang Mati: '.$product->name,
                    'message' => "Tidak laku {$metrics['days_inactive']} hari. Uang mandek Rp {$frozenRp}",
                    'payload' => $metrics,
                    'action_url' => '/products/'.$product->slug.'/edit',
                    'updated_at' => now(),
                ]
            );

            // Telegram — hanya jika mandek > 180 hari DAN frozen_asset signifikan (> 500rb)
            if ($metrics['days_inactive'] > 180 && $metrics['frozen_asset'] >= 500000) {
                $cacheKey = 'notif_deadstock_'.$product->id;
                if (! Cache::has($cacheKey)) {
                    $msg  = "💀 <b>DEAD STOCK: ASET MEMBEKU!</b>\n\n";
                    $msg .= "📦 <b>{$product->name}</b>\n";
                    $msg .= "💤 Tidak terjual: <b>{$metrics['days_inactive']} hari</b>\n";
                    $msg .= "💰 Modal terkunci: <b>Rp {$frozenRp}</b>\n\n";
                    $msg .= "💡 Pertimbangkan: flash sale, diskon, atau retur ke supplier.";
                    TelegramService::send($msg);
                    Cache::put($cacheKey, true, now()->addDays(3)); // Re-notify tiap 3 hari
                }
            }
        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_DEAD_STOCK)->delete();
        }
    }

    private function processMarginInsight(Product $product, array $metrics)
    {
        if ($metrics['margin']['is_critical']) {
            $currentMargin = round($metrics['margin']['percent'], 2);
            $targetMargin = $product->target_margin_percent;
            $beli = number_format($product->purchase_price, 0, ',', '.');

            // Smart Pricing context jika ada
            $smartHint = '';
            if (isset($metrics['smart_pricing']['has_recommendation']) && $metrics['smart_pricing']['has_recommendation']) {
                $recPrice = number_format($metrics['smart_pricing']['recommended_price'], 0, ',', '.');
                $smartHint = " AI Pricing: Harga rekomendasi Rp {$recPrice}.";
            }

            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_MARGIN],
                [
                    'severity' => SmartInsight::SEVERITY_WARNING,
                    'title' => 'Margin Menipis: '.$product->name,
                    'message' => "Margin drop ke {$currentMargin}% (Target: {$targetMargin}%). Modal naik menjadi Rp {$beli}.{$smartHint}",
                    'payload' => array_merge($metrics['margin'], [
                        'smart_pricing' => $metrics['smart_pricing'] ?? null,
                    ]),
                    'action_url' => '/products/'.$product->slug.'/edit',
                    'updated_at' => now(),
                ]
            );

            // Kirim Notifikasi Telegram (sekali per 6 jam, pakai Cache Guard)
            $cacheKey = 'notif_margin_low_'.$product->id;
            if (! Cache::has($cacheKey)) {
                $msg = "<b>ALERT: PROFIT MARGIN RENDAH!</b>\n\n";
                $msg .= "<b>{$product->name}</b>\n";
                $msg .= "Margin Saat Ini: <b>{$currentMargin}%</b>\n";
                $msg .= "Target Margin: <b>{$targetMargin}%</b>\n";
                if ($smartHint) {
                    $msg .= "\n{$smartHint}";
                }
                TelegramService::send($msg);
                Cache::put($cacheKey, true, now()->addHours(6));
            }

        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_MARGIN)->delete();
        }
    }

    private function processHighMarginInsight(Product $product, array $metrics)
    {
        if ($metrics['margin']['is_high_margin']) {
            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_HIGH_MARGIN], // TYPE HIGH MARGIN
                [
                    'severity' => SmartInsight::SEVERITY_INFO, // HARDCODED CONSTANT (Info positif)
                    'title' => 'Produk High Margin: '.$product->name,
                    'message' => "Profit tebal ({$metrics['margin']['percent']}%). Prioritaskan stok agar tidak kosong.",
                    'payload' => $metrics['margin'],
                    'action_url' => '/products/'.$product->slug.'/edit',
                    'updated_at' => now(),
                ]
            );
        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_HIGH_MARGIN)->delete();
        }
    }

    private function processNewProductInsight(Product $product, array $metrics)
    {
        if ($metrics['lifecycle']['is_new_product']) {
            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_NEW], // TYPE NEW (Sesuai konstanta Anda)
                [
                    'severity' => SmartInsight::SEVERITY_INFO, // HARDCODED CONSTANT
                    'title' => 'Produk Baru: '.$product->name,
                    'message' => "Produk baru ditambahkan {$metrics['lifecycle']['days_active']} hari lalu.",
                    'payload' => $metrics['lifecycle'],
                    'action_url' => '/products/'.$product->slug.'/edit',
                    'updated_at' => now(),
                ]
            );
        } else {
            // Hapus status 'Produk Baru' jika sudah > 30 hari
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_NEW)->delete();
        }
    }

    private function processTrendInsight(Product $product, array $metrics)
    {
        $salesTrend = $metrics['sales_trend'];

        if ($salesTrend['is_trending']) {
            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_TREND],
                [
                    'severity' => SmartInsight::SEVERITY_INFO,
                    'title' => 'Lagi Laris: '.$product->name,
                    'message' => "Penjualan naik {$salesTrend['growth_percent']}% bulan ini.",
                    'payload' => $salesTrend,
                    'updated_at' => now(),
                ]
            );
        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_TREND)->delete();
        }
    }

    /**
     * =========================================================================
     * PROCESSOR BARU: AI SMART PRICING INSIGHT
     * =========================================================================
     * Menyimpan rekomendasi harga ke SmartInsight hanya jika ada action konkret.
     */
    private function processPricingInsight(Product $product, array $metrics)
    {
        $pricing = $metrics['smart_pricing'] ?? null;

        if ($pricing && $pricing['has_recommendation']) {
            $actionLabel = $pricing['action'] === 'raise' ? 'Peluang Naikkan Harga' : 'Saran Diskon Stok';
            $severity = $pricing['action'] === 'raise' ? SmartInsight::SEVERITY_INFO : SmartInsight::SEVERITY_WARNING;

            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_PRICE_RECOMMENDATION],
                [
                    'severity' => $severity,
                    'title' => "{$actionLabel}: {$product->name}",
                    'message' => $pricing['suggestion'],
                    'payload' => $pricing,
                    'action_url' => '/products/'.$product->slug.'/edit',
                    'updated_at' => now(),
                ]
            );
        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_PRICE_RECOMMENDATION)->delete();
        }
    }

    /**
     * PROCESSOR: ABC/XYZ PRODUCT CLASSIFICATION
     * Menyimpan klasifikasi matriks produk ke SmartInsight.
     * Hanya menyimpan produk yang berklasifikasi menonjol (A-class atau Z-class)
     * sebagai insight actionable. Produk B-X / C-X tidak perlu notifikasi.
     */
    private function processClassificationInsight(Product $product, array $classification): void
    {
        $matrix = $classification['matrix'] ?? 'C-Z';
        $abcClass = $classification['abc_class'] ?? 'C';
        $xyzClass = $classification['xyz_class'] ?? 'Z';

        $isActionable = $abcClass === 'A' || $xyzClass === 'Z';

        if ($isActionable) {
            $severity = SmartInsight::SEVERITY_INFO;
            if ($matrix === 'A-Z') $severity = SmartInsight::SEVERITY_WARNING;
            if ($matrix === 'C-Z') $severity = SmartInsight::SEVERITY_WARNING;

            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_ABC_XYZ],
                [
                    'severity'   => $severity,
                    'title'      => "Klasifikasi {$matrix}: {$product->name}",
                    'message'    => $classification['recommendation'],
                    'payload'    => $classification,
                    'action_url' => '/products/'.$product->slug.'/edit',
                    'updated_at' => now(),
                ]
            );
        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_ABC_XYZ)->delete();
        }
    }

    /**
     * PROCESSOR: SEASONAL RESTOCKING PLANNER
     */
    private function processSeasonalRestockInsight(Product $product, array $seasonal): void
    {
        if ($seasonal['has_seasonal_peak'] && $seasonal['restock_needed'] > 0) {
            $severityMap = [
                'critical' => SmartInsight::SEVERITY_CRITICAL,
                'warning'  => SmartInsight::SEVERITY_WARNING,
                'info'     => SmartInsight::SEVERITY_INFO,
            ];
            $severity = $severityMap[$seasonal['urgency']] ?? SmartInsight::SEVERITY_INFO;

            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_SEASONAL_RESTOCK],
                [
                    'severity'   => $severity,
                    'title'      => "Stok Musiman: {$product->name} (+{$seasonal['restock_needed']} pcs)",
                    'message'    => $seasonal['recommendation'],
                    'payload'    => $seasonal,
                    'action_url' => '/purchases/create?product_slug='.$product->slug,
                    'updated_at' => now(),
                ]
            );

            // Telegram — hanya untuk urgency critical (deadline beli < 3 hari)
            if ($seasonal['urgency'] === 'critical') {
                $cacheKey = 'notif_seasonal_'.$product->id;
                if (! Cache::has($cacheKey)) {
                    $deadlineDays = $seasonal['buy_deadline_days'] ?? '?';
                    $restockQty   = $seasonal['restock_needed'] ?? '?';
                    $peakMonth    = $seasonal['peak_month'] ?? '?';
                    $costFmt      = 'Rp '.number_format($seasonal['estimated_cost'] ?? 0, 0, ',', '.');
                    $msg  = "📅 <b>BELI MUSIMAN MENDESAK!</b>\n\n";
                    $msg .= "📦 <b>{$product->name}</b>\n";
                    $msg .= "⏰ Deadline beli: <b>{$deadlineDays} hari lagi</b>\n";
                    $msg .= "📈 Peak musiman: <b>{$peakMonth}</b>\n";
                    $msg .= "🛒 Perlu beli: <b>{$restockQty} pcs</b> ({$costFmt})\n\n";
                    $msg .= "👉 Segera lakukan pembelian sebelum terlambat!";
                    TelegramService::send($msg);
                    Cache::put($cacheKey, true, now()->addHours(12));
                }
            }
        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_SEASONAL_RESTOCK)->delete();
        }
    }

    /**
     * PROCESSOR: CAPITAL EFFICIENCY ADVISOR
     * Hanya menyimpan produk dengan efisiensi modal buruk (score D atau F).
     * Threshold: hanya warning jika modal tertahan > Rp 200.000 (tidak worth-notif untuk stok receh).
     */
    private function processCapitalEfficiencyInsight(Product $product, array $capital): void
    {
        $isLowEfficiency = $capital['is_actionable'] && in_array($capital['efficiency_score'], ['D', 'F']);
        $isSignificantCapital = $capital['capital_locked'] >= 200000;

        if ($isLowEfficiency && $isSignificantCapital) {
            $severity = $capital['efficiency_score'] === 'F'
                ? SmartInsight::SEVERITY_WARNING
                : SmartInsight::SEVERITY_INFO;

            $capitalFmt = 'Rp ' . number_format($capital['capital_locked'], 0, ',', '.');
            $holdingFmt = 'Rp ' . number_format($capital['holding_cost_monthly'], 0, ',', '.');

            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_CAPITAL_EFFICIENCY],
                [
                    'severity'   => $severity,
                    'title'      => "Modal Kurang Efisien: {$product->name} ({$capitalFmt} Terkunci)",
                    'message'    => $capital['recommendation'] . " Biaya menyimpan: ~{$holdingFmt}/bulan.",
                    'payload'    => $capital,
                    'action_url' => '/products/'.$product->slug.'/edit',
                    'updated_at' => now(),
                ]
            );
        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_CAPITAL_EFFICIENCY)->delete();
        }
    }
}

