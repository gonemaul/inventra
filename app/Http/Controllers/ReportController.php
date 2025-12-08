<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sale;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        // Render tampilan Menu Laporan
        return Inertia::render('Reports/Index');
    }

    //PILAR 1
    /**
     * Summary of stockCard
     * @param Request $request
     * @return \Inertia\Response
     * Kartu Stock
     * Traking keluar masuk stok per produk
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
                ->where('created_at', '<', $startDate . ' 00:00:00')
                ->sum('quantity');

            // 2. AMBIL MUTASI PERIODE INI
            $movements = StockMovement::where('product_id', $productId)
                ->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ])
                ->orderBy('created_at', 'asc') // Urut kronologis (Lama -> Baru)
                ->get();

            $data = [
                'product' => $product,
                'opening_stock' => (int) $openingStock,
                'movements' => $movements
            ];
        }

        return Inertia::render('Reports/StockCard', [
            'filters' => [
                'product_id' => $productId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            // List produk untuk dropdown filter (Optimasi: ambil yg perlu aja)
            'products' => Product::select('id', 'code', 'name')->orderBy('name')->get(),
            'reportData' => $data
        ]);
    }

    /**
     * Summary of stockValue
     * @param Request $request
     * @return \Inertia\Response
     * Valuasi Aset
     * Total nilai uang dalam bentuk barang
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

        $products = $query->orderBy('name')->get();

        // Hitung Ringkasan di Backend biar Frontend ringan
        $summary = [
            'total_items' => $products->sum('stock'),
            'total_asset_value' => $products->sum(fn($p) => $p->stock * $p->purchase_price), // Total Modal
            'potential_revenue' => $products->sum(fn($p) => $p->stock * $p->selling_price), // Total Jika Laku
            'potential_profit'  => 0, // Dihitung di bawah
        ];
        $summary['potential_profit'] = $summary['potential_revenue'] - $summary['total_asset_value'];

        return Inertia::render('Reports/StockValue', [
            'products' => $products,
            'summary' => $summary,
            'categories' => \App\Models\Category::all(), // Untuk filter
            'filters' => $request->all(['category_id'])
        ]);
    }

    /**
     * Summary of deadStock
     * @param Request $request
     * @return \Inertia\Response
     * Dead Stock Analis
     * Deteksi barang macet atau mati
     */
    public function deadStock(Request $request)
    {
        // Default ambang batas: 90 Hari (3 Bulan)
        $thresholdDays = $request->input('days', 90);
        $cutOffDate = now()->subDays($thresholdDays);

        // Query: Ambil produk stok > 0 DAN (Tidak punya penjualan SEJAK cutOffDate)
        $products = Product::with(['category', 'unit', 'lastSale'])
            ->where('stock', '>', 0)
            ->whereDoesntHave('movements', function ($query) use ($cutOffDate) {
                $query->where('type', StockMovement::TYPE_SALE)
                    ->where('created_at', '>=', $cutOffDate);
            })
            ->get()
            ->map(function ($product) {
                // Hitung Hari Mandek
                $lastSaleDate = $product->lastSale ? $product->lastSale->created_at : $product->created_at;
                // PERBAIKAN: Gunakan startOfDay() untuk reset jam ke 00:00:00
                // Agar perhitungan murni berdasarkan tanggal kalender (Integer)
                $date1 = Carbon::parse($lastSaleDate)->startOfDay();
                $date2 = now()->startOfDay();
                $daysSilent = (int) $date1->diffInDays($date2);

                // Klasifikasi Saran Tindakan
                $suggestion = 'Promosi';
                if ($daysSilent > 180) $suggestion = 'Cuci Gudang / Obral';
                elseif ($daysSilent > 365) $suggestion = 'Scrap / Musnahkan';
                elseif (!$product->lastSale) $suggestion = 'Cek Display (Blm Pernah Laku)';

                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'category' => $product->category->name ?? '-',
                    'stock' => $product->stock,
                    'unit' => $product->unit->name ?? '',
                    'price' => $product->purchase_price, // HPP
                    'asset_value' => $product->stock * $product->purchase_price, // Uang Mandek
                    'last_sale_date' => $product->lastSale ? $product->lastSale->created_at : null,
                    'days_silent' => $daysSilent,
                    'suggestion' => $suggestion,
                ];
            })
            ->sortByDesc('days_silent') // Urutkan dari yang paling "berkarat"
            ->values();

        return Inertia::render('Reports/DeadStock', [
            'products' => $products,
            'filters' => ['days' => $thresholdDays],
            'total_frozen_asset' => $products->sum('asset_value'),
        ]);
    }

    //PILAR 2 SALES
    /**
     * Summary of salesRevenue
     * @param Request $request
     * @return \Inertia\Response
     * Laporan Omset
     */
    public function salesRevenue(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // 1. Query Agregat Harian (Untuk Grafik & Tabel)
        $dailySales = Sale::whereBetween('transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
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
                : 0
        ];

        return Inertia::render('Reports/SalesRevenue', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'data' => $dailySales,
            'summary' => $summary
        ]);
    }

    /**
     * Summary of topProducts
     * @param Request $request
     * @return \Inertia\Response
     * Produk Terlaris (Pareto)
     * 20% barang penyumbang 80% profit
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
            ->whereBetween('sales.transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
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
                'sort_by' => $sortBy
            ],
            'data' => $products
        ]);
    }

    /**
     * Summary of grossProfit
     * @param Request $request
     * @return \Inertia\Response
     * Laba kotor (margin)
     * Analisa keuntungan per transaksi
     */
    public function grossProfit(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Query Agregat Per Produk
        // Asumsi: tabel sale_items punya kolom 'purchase_price' (HPP saat jual)
        $items = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('sales.transaction_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            // ->where('sales.status', '!=', 'cancelled')
            ->select(
                'products.name',
                'products.code',
                'categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue'), // Omzet
                DB::raw('SUM(sale_items.quantity * sale_items.capital_price) as total_cogs') // HPP
            )
            ->groupBy('sale_items.product_id', 'products.name', 'products.code', 'categories.name')
            ->get()
            ->map(function ($item) {
                $grossProfit = $item->total_revenue - $item->total_cogs;
                $marginPercent = $item->total_revenue > 0
                    ? ($grossProfit / $item->total_revenue) * 100
                    : 0;

                return [
                    'name' => $item->name,
                    'code' => $item->code,
                    'category' => $item->category_name ?? '-',
                    'qty' => $item->total_qty,
                    'revenue' => $item->total_revenue,
                    'cogs' => $item->total_cogs,
                    'profit' => $grossProfit,
                    'margin' => round($marginPercent, 1)
                ];
            })
            ->sortByDesc('profit') // Default urutkan dari profit terbesar (Sultan)
            ->values();

        return Inertia::render('Reports/GrossProfit', [
            'filters' => ['start_date' => $startDate, 'end_date' => $endDate],
            'data' => $items,
            'summary' => [
                'total_profit' => $items->sum('profit'),
                'avg_margin' => $items->count() > 0 ? round($items->avg('margin'), 1) : 0
            ]
        ]);
    }
}
