<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseInvoice;
use App\Models\Sale;
use App\Models\SmartInsight;
use App\Services\Analysis\ProductDSSCalculator;
use Illuminate\Support\Facades\DB;

// Pastikan model ini sesuai dengan model Penjualan Anda

class InsightService
{
    protected $calculator;

    public function __construct(ProductDSSCalculator $calculator)
    {
        $this->calculator = $calculator;
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
        Product::with(['category', 'unit'])
            ->chunk(100, function ($products) {
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
                }
            });
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
        // Cek status menggunakan Konstanta Severity
        if ($metrics['status'] === SmartInsight::SEVERITY_CRITICAL || $metrics['status'] === SmartInsight::SEVERITY_WARNING) {
            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_RESTOCK], // TYPE RESTOCK
                [
                    'severity' => $metrics['status'], // Ini sudah return 'critical' atau 'warning' dari calculator
                    'title' => 'Perlu Restock: '.$product->name,
                    'message' => $metrics['message'],
                    'payload' => $metrics,
                    'action_url' => '/purchases/create?product_id='.$product->id,
                    'updated_at' => now(),
                ]
            );
        } else {
            SmartInsight::where('product_id', $product->id)->where('type', SmartInsight::TYPE_RESTOCK)->delete();
        }
    }

    private function processDeadStockInsight(Product $product, array $metrics)
    {
        if ($metrics['is_dead_stock']) {
            $frozenRp = number_format($metrics['frozen_asset'], 0, ',', '.');

            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_DEAD_STOCK], // TYPE DEAD STOCK
                [
                    'severity' => SmartInsight::SEVERITY_WARNING, // HARDCODED CONSTANT
                    'title' => 'Barang Mati: '.$product->name,
                    'message' => "Tidak laku {$metrics['days_inactive']} hari. Uang mandek Rp {$frozenRp}",
                    'payload' => $metrics,
                    'action_url' => '/products/'.$product->id,
                    'updated_at' => now(),
                ]
            );
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
            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_MARGIN], // TYPE MARGIN
                [
                    'severity' => SmartInsight::SEVERITY_WARNING, // HARDCODED CONSTANT
                    'title' => 'Margin Menipis: '.$product->name,
                    'message' => "Margin drop ke {$currentMargin}% (Target: {$targetMargin}%). Modal naik menjadi Rp {$beli}.",
                    'payload' => $metrics['margin'],
                    'action_url' => '/products/'.$product->id.'/edit',
                    'updated_at' => now(),
                ]
            );
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
                    'action_url' => '/products/'.$product->id,
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
                    'action_url' => '/products/'.$product->id,
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
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_TREND], // TYPE TREND
                [
                    'severity' => SmartInsight::SEVERITY_INFO, // Gunakan INFO untuk kabar baik (Success biasanya tidak ada di konstanta standar)
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
}
