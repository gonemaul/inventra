<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\StockMovement;

class ReportController extends Controller
{
    public function index()
    {
        // Render tampilan Menu Laporan
        return Inertia::render('Reports/Index');
    }
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
}
