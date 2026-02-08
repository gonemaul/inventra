<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $totalAsset = Product::sum(DB::raw('stock * purchase_price'));

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $currentRevenue = Sale::whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            // ->where('status', '!=', 'cancelled')
            ->sum('total_revenue');

        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthQuery = $isSqlite
            ? "strftime('%Y-%m', transaction_date)" // Syntax SQLite
            : "DATE_FORMAT(transaction_date, '%Y-%m')"; // Syntax MySQL
        $historicalSales = Sale::select(
            DB::raw('sum(total_revenue) as monthly_total'),
            DB::raw("$monthQuery as month")
        )
            // ->where('status', '!=', 'cancelled')
            ->whereBetween('transaction_date', [$sixMonthsAgo, now()->subMonth()->endOfMonth()]) // Jangan hitung bulan ini karena belum selesai
            ->groupBy('month')
            ->get();

        $avgRevenue = $historicalSales->count() > 0
            ? $historicalSales->avg('monthly_total')
            : 0;

        $revenueProgress = $avgRevenue > 0 ? ($currentRevenue / $avgRevenue) * 100 : 0;

        $debtDueSoon = PurchaseInvoice::where('payment_status', '!=', PurchaseInvoice::PAYMENT_STATUS_PAID)
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->sum(DB::raw('total_amount - COALESCE(amount_paid, 0)'));

        $currentCOGS = SaleItem::whereHas('sale', function ($q) use ($startOfMonth, $endOfMonth) {
            $q->whereBetween('transaction_date', [$startOfMonth, $endOfMonth]);
            // ->where('status', '!=', 'cancelled');
        })
            ->sum(DB::raw('quantity * capital_price'));

        // Hitung Revenue Bulan Lalu untuk Komparasi Trend
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $lastMonthRevenue = Sale::whereBetween('transaction_date', [$lastMonthStart, $lastMonthEnd])->sum('total_revenue');
        
        $revenueGrowth = 0;
        if ($lastMonthRevenue > 0) {
            $revenueGrowth = (($currentRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        } elseif ($currentRevenue > 0) {
            $revenueGrowth = 100; // Jika bulan lalu 0 dan sekarang ada, anggap 100% (atau infinite)
        }

        $currentExpenses = 0;
        $lastMonthExpenses = 0;
        
        if (class_exists('App\Models\Expense')) {
            $currentExpenses = Expense::whereBetween('date', [$startOfMonth, $endOfMonth])->sum('amount');
            $lastMonthExpenses = Expense::whereBetween('date', [$lastMonthStart, $lastMonthEnd])->sum('amount');
        }

        // COGS Bulan Lalu
        $lastMonthCOGS = SaleItem::whereHas('sale', function ($q) use ($lastMonthStart, $lastMonthEnd) {
            $q->whereBetween('transaction_date', [$lastMonthStart, $lastMonthEnd]);
        })->sum(DB::raw('quantity * capital_price'));

        $netProfit = $currentRevenue - $currentCOGS - $currentExpenses;
        $lastMonthProfit = $lastMonthRevenue - $lastMonthCOGS - $lastMonthExpenses;

        $profitGrowth = 0;
        if ($lastMonthProfit > 0) {
            $profitGrowth = (($netProfit - $lastMonthProfit) / $lastMonthProfit) * 100;
        } elseif ($netProfit > 0) {
            $profitGrowth = 100;
        }
        $netMargin = $currentRevenue > 0 ? ($netProfit / $currentRevenue) * 100 : 0;

        // --- BARU: KESEHATAN STOK ---
        // 1. Produk Menipis (Stock <= min_stock atau default 5)
        $lowStockQuery = Product::where('stock', '>', 0)
            ->where(function ($q) {
                $q->whereColumn('stock', '<=', 'min_stock')
                  ->orWhere(function ($sub) {
                      $sub->whereNull('min_stock')->where('stock', '<=', 5);
                  });
            });

        $lowStockCount = $lowStockQuery->count();
        $lowStockItems = $lowStockQuery->select('name', 'stock', 'unit_id')
            ->with('unit:id,name')
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get();

        // 2. Dead Stock Value (90 Hari tanpa penjualan)
        $deadStockThreshold = now()->subDays(90);
        $deadStockQuery = Product::where('stock', '>', 0)
            ->whereDoesntHave('saleItems', function ($q) use ($deadStockThreshold) {
                $q->whereHas('sale', function ($s) use ($deadStockThreshold) {
                    $s->where('transaction_date', '>=', $deadStockThreshold);
                });
            });

        $deadStockValue = $deadStockQuery->sum(DB::raw('stock * purchase_price'));
        $deadStockItems = $deadStockQuery->select('name', 'stock', 'purchase_price', 'unit_id')
            ->with('unit:id,name')
            ->orderBy(DB::raw('stock * purchase_price'), 'desc') // Urutkan berdasarkan nilai uang mati terbesar
            ->limit(5)
            ->get();

        // 3. Top Movers (5 Produk Terlaris Bulan Ini)
        $topMovers = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.transaction_date', [$startOfMonth, $endOfMonth])
            ->select(
                'products.name',
                'products.code',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('sale_items.product_id', 'products.name', 'products.code')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Render tampilan Menu Laporan
        return Inertia::render('Reports/Index', [
            'summary' => [
                'total_asset' => (float) $totalAsset,
                'current_revenue' => (float) $currentRevenue,
                'avg_revenue' => (float) $avgRevenue,
                'revenue_progress' => round($revenueProgress, 1),
                'debt_due_soon' => (float) $debtDueSoon,
                'net_profit' => (float) $netProfit,
                'net_margin' => round($netMargin, 1),
                // New Metrics
                'low_stock_count' => $lowStockCount,
                'low_stock_items' => $lowStockItems, // New List
                'dead_stock_value' => (float) $deadStockValue,
                'dead_stock_items' => $deadStockItems, // New List
                'top_movers' => $topMovers,
                'revenue_growth' => round($revenueGrowth, 1),
                'profit_growth' => round($profitGrowth, 1),
            ],
        ]);
    }

    // PILAR 1
    /**
     * Summary of stockCard
     *
     * @return \Inertia\Response
     *                           Kartu Stock
     *                           Traking keluar masuk stok per produk
     */
    public function stockCard(Request $request)
    {
        // Default Tanggal: Awal bulan ini s/d Hari ini
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $productId = $request->input('product_id');

        $data = null;

        if ($productId) {
            $product = Product::with(['unit', 'category'])->findOrFail($productId);

            // 1. HITUNG SALDO AWAL (Opening Stock)
            // Rumus: Jumlahkan semua mutasi SEBELUM tanggal awal filter
            $openingStock = StockMovement::where('product_id', $productId)
                ->where('created_at', '<', $startDate.' 00:00:00')
                ->sum('quantity');

            // 2. AMBIL MUTASI PERIODE INI
            $movements = StockMovement::where('product_id', $productId)
                ->whereBetween('created_at', [
                    $startDate.' 00:00:00',
                    $endDate.' 23:59:59',
                ])
                ->orderBy('created_at', 'asc') // Urut kronologis (Lama -> Baru)
                ->get();

            $data = [
                'product' => $product,
                'opening_stock' => (int) $openingStock,
                'movements' => $movements,
            ];
        }

        return Inertia::render('Reports/StockCard', [
            'filters' => [
                'product_id' => $productId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            // products untuk dropdown tidak perlu diload semua (berat)
            // Frontend akan menggunakan API Search
            'products' => [], 
            'reportData' => $data,
        ]);
    }

    /**
     * Summary of stockValue
     *
     * @return \Inertia\Response
     *                           Valuasi Aset
     *                           Total nilai uang dalam bentuk barang
     */
    public function stockValue(Request $request)
    {
        // Ambil produk yang punya stok saja (agar laporan tidak penuh sampah stok 0)
        // Kecuali user ingin lihat semua, bisa diatur filter nanti.

        $query = Product::with(['category', 'unit'])
            ->where('stock', '>', 0);

        // Filter Kategori (Jika ada)
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        
        // Sorting logic
        $sortColumn = $request->input('sort', 'stock'); // Default sort by stock
        $sortDirection = $request->input('direction', 'desc'); // Default desc

        // Validasi kolom sort agar aman
        $validSortColumns = ['name', 'stock', 'purchase_price', 'asset_value', 'potential_revenue'];
        
        if (in_array($sortColumn, ['asset_value', 'potential_revenue'])) {
             // Sort by expression (calculated columns)
             if ($sortColumn === 'asset_value') {
                $query->orderByRaw('stock * purchase_price ' . $sortDirection);
             } elseif ($sortColumn === 'potential_revenue') {
                $query->orderByRaw('stock * selling_price ' . $sortDirection);
             }
        } else {
            // Sort by combined column or standard column
            if (in_array($sortColumn, $validSortColumns)) {
                 $query->orderBy($sortColumn, $sortDirection);
            } else {
                 $query->orderBy('stock', 'desc'); // Fallback
            }
        }


        // Hitung Ringkasan di Backend (Aggregate) - SEBELUM Pagination
        // Clone query agar filter tetap jalan tapi limit/offset tidak
        $summaryQuery = clone $query; 
        
        // Gunakan DB::raw untuk kalkulasi sum product
        $totalItems = $summaryQuery->sum('stock');
        $totalAssetValue = $summaryQuery->sum(DB::raw('stock * purchase_price'));
        $potentialRevenue = $summaryQuery->sum(DB::raw('stock * selling_price'));
        $potentialProfit = $potentialRevenue - $totalAssetValue;

        $summary = [
            'total_items' => (int) $totalItems,
            'total_asset_value' => (float) $totalAssetValue,
            'potential_revenue' => (float) $potentialRevenue, // Total Jika Laku
            'potential_profit' => (float) $potentialProfit, // Dihitung di bawah
        ];

        // Pagination
        $products = $query->paginate(20)->withQueryString();

        // Append custom calculated attributes to each item in the current page
        $products->getCollection()->transform(function ($product) {
            $product->asset_value = $product->stock * $product->purchase_price;
            $product->potential_sale = $product->stock * $product->selling_price;
            return $product;
        });

        return Inertia::render('Reports/StockValue', [
            'products' => $products,
            'summary' => $summary,
            'categories' => \App\Models\Category::all(), // Untuk filter
            'filters' => $request->only(['category_id', 'sort', 'direction']),
        ]);
    }

    /**
     * Summary of deadStock
     *
     * @return \Inertia\Response
     *                           Dead Stock Analis
     *                           Deteksi barang macet atau mati
     */
    public function deadStock(Request $request)
    {
        // Default ambang batas: 90 Hari (3 Bulan)
        $thresholdDays = $request->input('days', 90);
        $cutOffDate = now()->subDays($thresholdDays);

        // Query: Ambil produk stok > 0 DAN (Tidak punya penjualan SEJAK cutOffDate)
        // Gunakan addSelect untuk mengambil tanggal penjualan terakhir agar bisa disortir dlm SQL
        $query = Product::with(['category', 'unit'])
            ->where('stock', '>', 0)
            ->addSelect(['last_sale_at' => StockMovement::select('created_at')
                ->whereColumn('product_id', 'products.id')
                ->where('type', StockMovement::TYPE_SALE)
                ->latest()
                ->limit(1)
            ])
            ->whereDoesntHave('movements', function ($q) use ($cutOffDate) {
                $q->where('type', StockMovement::TYPE_SALE)
                    ->where('created_at', '>=', $cutOffDate);
            });

        // Hitung Total Aset Macet (Aggregate di level Database sebelum pagination)
        // Kita butuh query terpisah atau clone karena pagination akan memotong hasil
        $totalFrozenAsset = $query->sum(DB::raw('stock * purchase_price'));

        // Sorting: Prioritaskan yang BELUM PERNAH LAKU (last_sale_at IS NULL),
        // Kemudian urutkan dari yang Terakhir Lakunya Paling Lama (ASC)
        $query->orderByRaw('last_sale_at IS NULL DESC')
              ->orderBy('last_sale_at', 'asc');

        $products = $query->paginate(20)->withQueryString();

        $products->getCollection()->transform(function ($product) {
            // Hitung Hari Mandek
            // Gunakan atribut 'last_sale_at' yang sudah di-select via subquery atau dari relasi jika eager loaded (disini via subquery)
            // Jika null, berarti belum pernah laku sejak awal (gunakan created_at barang sbg basis atau anggap infinity?)
            // Logic lama: jika lastSale kosong, pakai created_at produk.
            
            $lastActivityDate = $product->last_sale_at ? $product->last_sale_at : $product->created_at;
            
            $date1 = Carbon::parse($lastActivityDate)->startOfDay();
            $date2 = now()->startOfDay();
            $daysSilent = (int) $date1->diffInDays($date2);

            // Klasifikasi Saran Tindakan
            $suggestion = 'Promosi';
            if ($daysSilent > 180) {
                $suggestion = 'Cuci Gudang / Obral';
            } elseif ($daysSilent > 365) {
                $suggestion = 'Scrap / Musnahkan';
            } elseif (! $product->last_sale_at) { // Cek subquery attribute
                $suggestion = 'Cek Display (Blm Pernah Laku)';
            }

            return [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'category' => $product->category->name ?? '-',
                'stock' => $product->stock,
                'unit' => $product->unit->name ?? '',
                'price' => $product->purchase_price, // HPP
                'asset_value' => $product->stock * $product->purchase_price, // Uang Mandek
                'last_sale_date' => $product->last_sale_at,
                'days_silent' => $daysSilent,
                'suggestion' => $suggestion,
            ];
        });

        return Inertia::render('Reports/DeadStock', [
            'products' => $products, // Paginated Object
            'filters' => ['days' => $thresholdDays],
            'total_frozen_asset' => (float) $totalFrozenAsset,
        ]);
    }

    // PILAR 2 SALES
    /**
     * Summary of salesRevenue
     *
     * @return \Inertia\Response
     *                           Laporan Omset
     */
    public function salesRevenue(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // 1. Query Agregat Harian (Untuk Grafik & Tabel)
        $dailySales = Sale::whereBetween('transaction_date', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(total_revenue) as revenue'),
                DB::raw('COUNT(id) as transaction_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // 2. Hitung Total Summary
        $summary = [
            'total_revenue' => $dailySales->sum('revenue'),
            'total_transactions' => $dailySales->sum('transaction_count'),
            // Rata-rata nilai belanja per orang (Basket Size)
            'average_basket_size' => $dailySales->sum('transaction_count') > 0
                ? $dailySales->sum('revenue') / $dailySales->sum('transaction_count')
                : 0,
        ];

        return Inertia::render('Reports/SalesRevenue', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'data' => $dailySales,
            'summary' => $summary,
        ]);
    }

    /**
     * Summary of topProducts
     *
     * @return \Inertia\Response
     *                           Produk Terlaris (Pareto)
     *                           20% barang penyumbang 80% profit
     */
    public function topProducts(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $limit = $request->input('limit', 10); // Top 10, 20, 50
        $sortBy = $request->input('sort_by', 'total_qty'); // 'total_qty' atau 'total_revenue'

        // Query Aggregation
        $products = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id') // Optional: ambil kategori
            ->whereBetween('sales.transaction_date', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->select(
                'products.name',
                'products.code',
                'categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('sale_items.product_id', 'products.name', 'products.code', 'categories.name')
            ->orderByDesc($sortBy) // Sort dinamis (Qty atau Omzet)
            ->limit($limit)
            ->get();

        return Inertia::render('Reports/TopProducts', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'limit' => $limit,
                'sort_by' => $sortBy,
            ],
            'data' => $products,
        ]);
    }

    /**
     * Summary of grossProfit
     *
     * @return \Inertia\Response
     *                           Laba kotor (margin)
     *                           Analisa keuntungan per transaksi
     */
    public function grossProfit(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Sorting logic
        $sortColumn = $request->input('sort', 'profit'); // Default sort by profit
        $sortDirection = $request->input('direction', 'desc');

        // Query Agregat Per Produk
        // Subquery atau Raw Select untuk kalkulasi performa
        // Kita hitung di level database sebisa mungkin
        $query = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('sales.transaction_date', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->where('sales.status', '!=', 'cancelled') // Pastikan tidak include batal (jika ada status)
            ->select(
                'products.name',
                'products.code',
                'categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue'), // Omzet
                DB::raw('SUM(sale_items.quantity * sale_items.capital_price) as total_cogs'), // HPP
                DB::raw('SUM(sale_items.subtotal - (sale_items.quantity * sale_items.capital_price)) as profit') // Profit
            )
            ->groupBy('sale_items.product_id', 'products.name', 'products.code', 'categories.name');

        // Total Summary (Dihitung dari query terpisah agar tidak kena limit pagination)
        // Kita gunakan query builder yang belum di-get
        // Clone untuk summary
        $summaryQuery = clone $query;
        // Bungkus dalam subquery untuk sum hasil group by
        $summaryStats = DB::table(DB::raw("({$summaryQuery->toSql()}) as grouped_sales"))
            ->mergeBindings($summaryQuery->getQuery())
            ->select(
                DB::raw('SUM(profit) as total_profit'),
                DB::raw('SUM(total_revenue) as total_revenue'),
                 // Rata-rata margin dari total global
            )
            ->first();
        
        $totalProfit = $summaryStats->total_profit ?? 0;
        $totalRevenue = $summaryStats->total_revenue ?? 0;
        $globalMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;


        // Apply Sorting
        // Perlu hati-hati sorting kolom alias (total_revenue, dll)
        if (in_array($sortColumn, ['total_qty', 'total_revenue', 'total_cogs', 'profit'])) {
             $query->orderBy($sortColumn, $sortDirection);
        } elseif ($sortColumn === 'margin') {
             // Margin = (profit / revenue) * 100.
             // Sort by expression
             $query->orderByRaw('SUM(sale_items.subtotal - (sale_items.quantity * sale_items.capital_price)) / NULLIF(SUM(sale_items.subtotal),0) ' . $sortDirection);
        } else {
             $query->orderBy('products.name', $sortDirection);
        }

        $items = $query->paginate(20)->withQueryString();

        // Transform data untuk frontend (terutama margin per item)
        $items->getCollection()->transform(function ($item) {
             $marginPercent = $item->total_revenue > 0
                ? ($item->profit / $item->total_revenue) * 100
                : 0;

            return [
                'name' => $item->name,
                'code' => $item->code,
                'category' => $item->category_name ?? '-',
                'qty' => (int) $item->total_qty,
                'revenue' => (float) $item->total_revenue,
                'cogs' => (float) $item->total_cogs,
                'profit' => (float) $item->profit,
                'margin' => round($marginPercent, 1),
            ];
        });

        return Inertia::render('Reports/GrossProfit', [
            'filters' => [
                'start_date' => $startDate, 
                'end_date' => $endDate,
                'sort' => $sortColumn,
                'direction' => $sortDirection
            ],
            'data' => $items, // Paginated
            'summary' => [
                'total_profit' => (float) $totalProfit,
                'avg_margin' => round($globalMargin, 1), // Margin global rata-rata
            ],
        ]);
    }

    //  PILAR 3 SUPPLIER DAN TAGIHAN
    /**
     * Summary of purchaseBySupplier
     *
     * @return \Inertia\Response
     *                           Pembelian Supplier
     *                           Volume belanja per vendor
     */
    public function purchaseBySupplier(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Query Agregat
        $data = PurchaseInvoice::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_invoices.purchase_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->whereBetween('purchase_invoices.invoice_date', [$startDate, $endDate])
            ->select(
                'suppliers.name',
                DB::raw('COUNT(purchase_invoices.id) as total_trx'),
                DB::raw('SUM(purchase_invoices.total_amount) as total_spend')
            )
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_spend') // Urutkan dari belanja terbesar
            ->get();

        return Inertia::render('Reports/PurchaseBySupplier', [
            'filters' => ['start_date' => $startDate, 'end_date' => $endDate],
            'data' => $data,
            'total_all_spend' => $data->sum('total_spend'),
        ]);
    }

    /**
     * Summary of accountsPayable
     *
     * @return \Inertia\Response
     *                           Buku Hutang
     *                           Jadwal jatuh tempo hutang
     */
    public function accountsPayable()
    {
        // Ambil Invoice yang Belum Lunas
        $invoices = PurchaseInvoice::with(['purchase.supplier'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderBy('due_date', 'asc') // Paling urgent di atas
            ->get()
            ->map(function ($inv) {
                // Hitung Hari Jatuh Tempo (Aging)
                // Positif = Lewat Jatuh Tempo (Telat)
                // Negatif = Masih Aman
                $daysOverdue = now()->diffInDays($inv->due_date, false) * -1; // Dibalik logicnya biar enak

                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'supplier_name' => $inv->purchase->supplier->name ?? 'Umum',
                    'invoice_date' => $inv->invoice_date,
                    'due_date' => $inv->due_date,
                    'total_amount' => $inv->total_amount,
                    'paid_amount' => $inv->paid_amount ?? 0, // Asumsi ada kolom ini
                    'remaining_amount' => $inv->total_amount - ($inv->paid_amount ?? 0),
                    'status' => $inv->payment_status,
                    'days_overdue' => (int) $daysOverdue,
                ];
            });

        return Inertia::render('Reports/AccountsPayable', [
            'invoices' => $invoices,
            'summary' => [
                'total_debt' => $invoices->sum('remaining_amount'),
                'overdue_debt' => $invoices->where('days_overdue', '>', 0)->sum('remaining_amount'),
                'count' => $invoices->count(),
            ],
        ]);
    }

    /**
     * Summary of priceWatch
     *
     * @return \Inertia\Response
     *                           Price Watch
     *                           Tren kenaikan harga modal
     */
    public function priceWatch(Request $request)
    {
        $productId = $request->input('product_id');
        $startDate = $request->input('start_date', now()->subMonths(6)->toDateString()); // Default 6 bulan terakhir
        $endDate = $request->input('end_date', now()->toDateString());

        $history = [];
        $product = null;
        $summary = [];

        if ($productId) {
            $product = Product::select('id', 'name', 'code', 'stock', 'purchase_price', 'selling_price')
                ->find($productId);

            // Ambil History Pembelian Barang Tersebut
            $history = PurchaseItem::query()
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
                ->where('purchase_items.product_id', $productId)
                ->whereBetween('purchases.transaction_date', [$startDate, $endDate])
                ->where('purchases.status', '!=', 'cancelled') // Hanya yang valid
                ->select(
                    'purchases.transaction_date',
                    'purchases.reference_no',
                    'suppliers.name as supplier_name',
                    'purchase_items.quantity',
                    'purchase_items.purchase_price' // Harga Beli saat itu
                )
                ->orderBy('purchases.transaction_date', 'asc') // Urut kronologis
                ->get();

            // Hitung Statistik Sederhana
            if ($history->count() > 0) {
                $lowest = $history->min('purchase_price');
                $highest = $history->max('purchase_price');
                $average = $history->avg('purchase_price');
                $latest = $history->last()->purchase_price;

                // Trend: Bandingkan harga awal periode vs akhir periode
                $firstPrice = $history->first()->purchase_price;
                $trend = $latest - $firstPrice; // Positif = Naik, Negatif = Turun

                $summary = [
                    'min' => $lowest,
                    'max' => $highest,
                    'avg' => round($average),
                    'trend' => $trend,
                    'latest' => $latest,
                ];
            }
        }

        return Inertia::render('Reports/PriceWatch', [
            'products' => Product::select('id', 'code', 'name')->orderBy('name')->get(), // Dropdown
            'filters' => [
                'product_id' => $productId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'data' => $history,
            'product' => $product,
            'summary' => $summary,
        ]);
    }

    // PILAR 4 FINANCE
    /**
     * Summary of profitLoss
     *
     * @return \Inertia\Response
     *                           Laba Rugi
     */
    public function profitLoss(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // 1. REVENUE (Pendapatan Penjualan)
        // Ambil total penjualan bersih (Status valid)
        $revenue = Sale::whereBetween('transaction_date', [$startDate, $endDate])
            // ->where('status', '!=', 'cancelled')
            ->sum('grand_total');

        // 2. COGS (Harga Pokok Penjualan)
        // Ambil dari SaleItem agar akurat sesuai tanggal transaksi
        $cogs = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.transaction_date', [$startDate, $endDate])
            // ->where('sales.status', '!=', 'cancelled')
            ->sum(DB::raw('sale_items.quantity * sale_items.capital_price'));

        // 3. EXPENSES (Biaya Operasional)
        $expenses = DB::table('expenses')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Rincian Expenses per Kategori (Untuk Chart/Tabel)
        $expenseDetails = DB::table('expenses')
            ->whereBetween('date', [$startDate, $endDate])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        // 4. KALKULASI HASIL
        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;

        // Hitung Margin (%)
        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
        $netMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        return Inertia::render('Reports/ProfitLoss', [
            'filters' => ['start_date' => $startDate, 'end_date' => $endDate],
            'data' => [
                'revenue' => (float) $revenue,
                'cogs' => (float) $cogs,
                'expenses' => (float) $expenses,
                'gross_profit' => (float) $grossProfit,
                'net_profit' => (float) $netProfit,
                'gross_margin' => round($grossMargin, 1),
                'net_margin' => round($netMargin, 1),
                'expense_details' => $expenseDetails,
            ],
        ]);
    }

    /**
     * Summary of cashFlow
     *
     * @return \Inertia\Response
     *                           Arus Kas (CASHFLOW)
     */
    public function cashFlow(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // 1. CASH IN (Uang Masuk)
        // Asumsi: Semua sales lunas/dp dihitung sebagai cash in
        // Idealnya: Ambil dari tabel 'payments' jika ada. Jika tidak, pakai sales.paid_amount
        $cashIn = Sale::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('paid_amount'); // Menggunakan paid_amount (uang yg diterima kasir)

        // 2. CASH OUT (Uang Keluar)
        // A. Pembayaran ke Supplier
        $paymentToSupplier = Purchase::whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('paid_amount'); // Asumsi: Pembayaran terjadi di tgl transaksi (Simplifikasi)

        // B. Biaya Operasional
        $operationalCost = Expense::whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $totalCashOut = $paymentToSupplier + $operationalCost;
        $netCashFlow = $cashIn - $totalCashOut;

        return Inertia::render('Reports/CashFlow', [
            'filters' => ['start_date' => $startDate, 'end_date' => $endDate],
            'data' => [
                'cash_in' => (float) $cashIn,
                'cash_out' => (float) $totalCashOut,
                'net_flow' => (float) $netCashFlow,
                'breakdown' => [
                    'sales' => (float) $cashIn,
                    'supplier_payment' => (float) $paymentToSupplier,
                    'expenses' => (float) $operationalCost,
                ],
            ],
        ]);
    }
    public function expenseSummary(Request $request)
    {
        // 1. Filter Date
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // 2. Query Base
        $query = Expense::whereBetween('date', [$startDate, $endDate]);

        // 3. Summary Stats
        $totalExpense = (clone $query)->sum('amount');
        
        $daysCount = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        $avgDaily = $daysCount > 0 ? $totalExpense / $daysCount : 0;

        // 4. Chart Data (Group by Category)
        $chartData = (clone $query)
            ->select('category', DB::raw('sum(amount) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // 5. Paginated List
        $expenses = (clone $query)
            ->orderBy('date', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Reports/ExpenseSummary', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_expense' => $totalExpense,
                'avg_daily' => $avgDaily,
            ],
            'chart_data' => $chartData,
            'expenses' => $expenses,
        ]);
    }
    
    public function topCustomers()
    {
        $customers = Sale::whereNotNull('customer_id')
            ->select(
                'customer_id',
                DB::raw('sum(total_revenue) as total_spent'),
                DB::raw('count(id) as visit_count'),
                DB::raw('max(transaction_date) as last_seen')
            )
            ->groupBy('customer_id')
            ->with('customer')
            ->orderByDesc('total_spent')
            ->paginate(20);

        return Inertia::render('Reports/TopCustomers', [
            'customers' => $customers,
        ]);
    }
}
