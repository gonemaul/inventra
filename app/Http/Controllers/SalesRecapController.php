<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Sale;
use App\Services\CategoryService;
use App\Services\SalesRecapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SalesRecapController extends Controller
{
    protected $service;

    protected $categoryService;

    public function __construct(SalesRecapService $service, CategoryService $categoryService)
    {
        $this->service = $service;
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Logic Global Search: Jika ada search, abaikan filter tanggal
        $filters = $request->all();
        if (!empty($request->search)) {
            // Hapus filter tanggal agar pencarian bersifat global
            unset($filters['min_date']);
            unset($filters['max_date']);
        } elseif (!$request->has('min_date') && !$request->has('max_date') && $request->input('period') !== 'all') {
            // FIX: Default ke Hari Ini jika tidak ada filter sama sekali
            $filters['min_date'] = now()->subDays(1)->format('Y-m-d'); // Mundur 1 hari untuk safety timezone
            $filters['max_date'] = now()->format('Y-m-d');
        }
        
        $sales = $this->service->get($filters);

        // 2. Hitung Ringkasan (Dashboard & Best Seller)
        // REVISI: Menggunakan Rolling Period (Hari ini ke belakang)
        $todayDate = now();
        $weekDate = now()->subDays(6); // 7 Hari Terakhir (termasuk hari ini)
        $monthDate = now()->subDays(29); // 30 Hari Terakhir

        $today = $todayDate->format('Y-m-d');
        $startOfWeek = $weekDate->format('Y-m-d');
        $startOfMonth = $monthDate->format('Y-m-d');

        // Helper untuk hitung produk terlaris (Top 3 by Omset)
        $getBestSellingRevenue = function ($startDate, $endDate = null) {
            $query = \App\Models\SaleItem::selectRaw('product_id, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
                ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                    $q->whereDate('transaction_date', '>=', $startDate);
                    if ($endDate) {
                        $q->whereDate('transaction_date', '<=', $endDate);
                    }
                })
                ->groupBy('product_id')
                ->orderByDesc('total_revenue') // Order by Omset
                ->with('product:id,name,slug,unit,price,code') 
                ->limit(3)
                ->get();

            return $query->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'slug' => $item->product->slug,
                    'name' => $item->product->name ?? 'Unknown',
                    'code' => $item->product->code ?? '-',
                    'qty' => $item->total_qty,
                    'revenue' => $item->total_revenue
                ];
            });
        };

        // Helper untuk hitung produk terlaris (Top 3 by Qty)
        $getBestSellingQty = function ($startDate, $endDate = null) {
            $query = \App\Models\SaleItem::selectRaw('product_id, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
                ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                    $q->whereDate('transaction_date', '>=', $startDate);
                    if ($endDate) {
                        $q->whereDate('transaction_date', '<=', $endDate);
                    }
                })
                ->groupBy('product_id')
                ->orderByDesc('total_qty') // Order by Qty
                ->with('product:id,name,slug,unit,price,code') 
                ->limit(3)
                ->get();

            return $query->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'slug' => $item->product->slug,
                    'name' => $item->product->name ?? 'Unknown',
                    'code' => $item->product->code ?? '-',
                    'qty' => $item->total_qty,
                    'revenue' => $item->total_revenue
                ];
            });
        };

        // Helper Chart Data
        $getChartData = function ($type) use ($today, $startOfWeek, $startOfMonth) {
            $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';

            if ($type === 'today') {
                // Gunakan updated_at untuk jam karena transaction_date hanya tanggal
                // Jika update != create, ambil update (logic standar updated_at)
                $timeColumn = 'updated_at'; 
                $labelRaw = $isSqlite ? "CAST(strftime('%H', $timeColumn) AS INTEGER)" : "HOUR($timeColumn)";
                
                $data = Sale::selectRaw("$labelRaw as label, SUM(total_revenue) as value")
                    ->whereDate('transaction_date', $today)
                    ->groupBy('label')
                    ->pluck('value', 'label')
                    ->toArray();
                
                // Fill 00-23
                $result = [];
                for ($i = 0; $i <= 23; $i++) {
                    $result[] = (int)($data[$i] ?? 0);
                }
                return ['labels' => range(0, 23), 'values' => $result];
            }
            
            if ($type === 'week') {
                $labelRaw = $isSqlite ? "date(transaction_date)" : "DATE(transaction_date)";

                $data = Sale::selectRaw("$labelRaw as label, SUM(total_revenue) as value")
                    ->where('transaction_date', '>=', $startOfWeek)
                    ->groupBy('label')
                    ->pluck('value', 'label')
                    ->toArray();
                
                // Fill Last 7 Days
                $labels = [];
                $values = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $labels[] = now()->subDays($i)->format('d/m'); // Format tgl
                    $values[] = (int)($data[$date] ?? 0);
                }
                return ['labels' => $labels, 'values' => $values];
            }

            if ($type === 'month') {
                // Group by Week (Week Number)
                // SQLite %W: week of year (00-53)
                $labelRaw = $isSqlite ? "strftime('%W', transaction_date)" : "WEEK(transaction_date, 1)";
                $dateRaw = $isSqlite ? "date(transaction_date)" : "DATE(transaction_date)";

                $data = Sale::selectRaw("$labelRaw as label, MIN($dateRaw) as start_date, SUM(total_revenue) as value")
                    ->where('transaction_date', '>=', $startOfMonth)
                    ->groupBy('label')
                    ->orderBy('start_date')
                    ->get();
                
                return [
                    'labels' => $data->map(fn($item) => 'Minggu ' . date('W', strtotime($item->start_date)))->toArray(),
                    'values' => $data->pluck('value')->toArray()
                ];
            }
        };

        $summary = [
            'sales_today' => Sale::whereDate('transaction_date', $today)->sum('total_revenue'),
            'sales_week' => Sale::where('transaction_date', '>=', $startOfWeek)->sum('total_revenue'),
            'sales_month' => Sale::where('transaction_date', '>=', $startOfMonth)->sum('total_revenue'),
            'count_today' => Sale::whereDate('transaction_date', $today)->count(),
            
            'period_today' => $todayDate->translatedFormat('d M Y'),
            'period_week' => $weekDate->translatedFormat('d M') . ' - ' . $todayDate->translatedFormat('d M Y'),
            'period_month' => $monthDate->translatedFormat('d M') . ' - ' . $todayDate->translatedFormat('d M Y'),

            // Data Tambahan untuk "Produk Terlaris" per periode
            'best_selling_revenue_today' => $getBestSellingRevenue($today, $today),
            'best_selling_revenue_week' => $getBestSellingRevenue($startOfWeek),
            'best_selling_revenue_month' => $getBestSellingRevenue($startOfMonth),

            'best_selling_qty_today' => $getBestSellingQty($today, $today),
            'best_selling_qty_week' => $getBestSellingQty($startOfWeek),
            'best_selling_qty_month' => $getBestSellingQty($startOfMonth),

            // Charts
            'chart_today' => $getChartData('today'),
            'chart_week' => $getChartData('week'),
            'chart_month' => $getChartData('month'),
        ];

        return Inertia::render('Sale/index', [
            'sales' => $sales,
            'summary' => $summary,
            'filters' => $request->only(['search', 'min_date', 'max_date', 'min_revenue', 'max_revenue']),
        ]);
    }

    public function posIndex(Request $request)
    {
        return Inertia::render('Sale/Pos/index', [
            'categories' => $this->categoryService->getAll(),
            'customers' => Customer::select('id', 'name', 'member_code', 'phone')->get(),
            'brands' => Brand::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Sale/form', [
            'products' => Product::query()
                ->select([
                    'id',
                    'code',
                    'name',
                    'product_type_id',
                    'category_id',
                    'brand_id',
                    'selling_price', // atau price
                    'purchase_price', // Penting untuk hitung profit di frontend
                    'stock',
                    'image_path', // string pendek
                    'unit_id',
                    'size_id',
                ])
                ->withSum('saleItems as total_sold', 'quantity')
                ->with(['unit:id,name,is_decimal', 'brand:id,name', 'size:id,name'])
                ->orderBy('name')
                ->get(),
            'categories' => $this->categoryService->getAll(),
            'brands' => Brand::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi Array Items
        $validated = $request->validate([
            'input_type' => ['required', Rule::in(Sale::TYPES)],
            'customer_id' => 'nullable|exists:customers,id',
            'report_date' => 'required|date|before_or_equal:today',
            'created_at' => 'nullable|date',
            'payment_method' => ['required', Rule::in(Sale::PAYMENT_METHODS)],
            'payment_amount' => 'nullable|numeric|min:0',
            'change_amount' => 'numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'discount_type' => ['nullable', Rule::in(Sale::DISCON_TYPES)],
            'discount_value' => 'nullable|numeric|min:0',
            // validasi items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.0001',
                // Custom Validation: Cek apakah unit mengizinkan desimal
                function ($attribute, $value, $fail) use ($request) {
                    // Ambil index dari items.*.qty (ex: items.0.qty -> 0)
                    $index = explode('.', $attribute)[1];
                    // FIX: Gunakan product_id, bukan id (karena payload items.*.product_id)
                    $productId = $request->input("items.{$index}.product_id");

                    $product = Product::with('unit')->find($productId);

                    if ($product && $product->unit && ! $product->unit->is_decimal) {
                        // Jika unit TIDAK boleh desimal, cek apakah nilai integer
                        if (floor($value) != $value) {
                            $fail("Produk {$product->name} (Satuan: {$product->unit->name}) tidak boleh pecahan (koma).");
                        }
                    }
                },
            ], // Support Desimal
            'items.*.selling_price' => 'required|numeric|min:0',
        ], [
            'items.min' => 'Keranjang penjualan tidak boleh kosong.',
            'items.*.quantity.min' => 'Jumlah barang harus lebih dari 0.',
            'report_date.before_or_equal' => 'Tanggal laporan tidak boleh melebihi hari ini.',
        ]);

        try {
            $sale = $this->service->storeRecap($validated);

            $printUrl = $request->print_invoice ? route('sales.print', $sale->id) : null;
            $message = $validated['input_type'] == Sale::TYPE_REALTIME ? 'Transaksi Berhasil!' : 'Rekap penjualan berhasil disimpan.';

            return redirect()->back()->with([
                'success' => $message,
                'print_url' => $printUrl, // Kirim URL struk ke frontend
                'sale_id' => $sale->id,   // Kirim ID untuk keperluan lain
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Sales Store Error: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * API untuk Search Bar di Frontend
     * Menerima parameter ?query=nama_produk
     */
    public function searchProduct(Request $request)
    {
        $search = $request->input('query');

        if (! $search) {
            return response()->json([]);
        }

        // Gunakan Scope Filter (Search Pintar) yang sudah kita buat di Model Product
        // Atau query manual sederhana:
        $products = Product::query()
            ->with(['unit', 'brand']) // Load relasi biar data lengkap
            ->where('stock', '>', 0)  // Opsional: Hanya tampilkan yg ada stok
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->limit(10) // Batasi 10 hasil biar ringan
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name, // Pakai Accessor Full Name Komposit
                    'stock' => (float) $product->stock, // Cast ke float biar gak string
                    'unit' => $product->unit->name ?? 'Pcs',
                    'price' => (float) $product->selling_price,
                    'brand' => $product->brand->name,
                    'image' => $product->image_url ?? null,
                ];
            });

        return response()->json($products);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        $sale->load(['items','items.product:id,slug', 'user']);

        return Inertia::render('Sale/Show', [
            'sale' => $sale,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale)
    {
        $sale->load([
            'items.product' => function($q) {
                $q->withTrashed();
            },
            'items.product.unit', 
            'items.product.category', 
            'items.product.brand',
            'items.product.size',
            'customer'
        ]);

        if ($sale->input_type == Sale::TYPE_REALTIME) {
            return Inertia::render('Sale/Pos/index', [
                'categories' => $this->categoryService->getAll(),
                'customers' => Customer::select('id', 'name', 'member_code', 'phone')->get(),
                'brands' => Brand::select('id', 'name')->orderBy('name')->get(),
                'sale' => $sale, // Pass existing sale for editing
                'mode' => 'edit',
            ]);
        }

        return Inertia::render('Sale/form', [
            'sale' => $sale, // Kirim data lama ke Vue
            'mode' => 'edit', // Penanda mode
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        // Validasi sama persis dengan Store
        $validated = $request->validate([
            'report_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.selling_price' => 'required|numeric|min:0',
        ]);

        try {
            $this->service->updateRecap($sale, $validated);

            return Redirect::route('sales.index')
                ->with('success', 'Rekap berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        try {
            $this->service->deleteRecap($sale);

            return Redirect::route('sales.index')
                ->with('success', "Rekap {$sale->reference_no} berhasil dihapus. Stok telah dikembalikan.");
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Gagal menghapus: ');
        }
    }

    public function print(Sale $sale)
    {
        // Load relasi item & produk
        $sale->load(['items.product', 'user', 'customer']);
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $settings['shop_logo'] = isset($settings['shop_logo']) ? Storage::disk('s3')->url($settings['shop_logo']) : null;
        // Return view blade khusus struk
        // dd($sale->reference_no);
        $dataSettings = [
            'shop_name' => $settings['shop_name'] ?? 'Toko Saya',
            'shop_address' => $settings['shop_address'] ?? 'Alamat Belum Diisi',
            'shop_phone' => $settings['shop_phone'] ?? '-',
            'receipt_footer' => $settings['receipt_footer'] ?? 'Terima Kasih',
        ];

        return view('print.receipt', [
            'sale' => $sale,
            'settings' => $dataSettings,
        ])->render();
    }

    public function getAllProductsLite(Request $request)
    {
        // 1. Ambil Parameter
        $search = $request->input('query'); // Input dari ketikan user
        $limit = $request->input('limit', 20); // Default load 20 saja biar ringan

        $query = Product::query()
            ->select([
                'id',
                'code',
                'name',
                'description',
                'category_id',
                'product_type_id',
                'brand_id',
                'selling_price',
                'stock',
                'image_path',
                'unit_id',
                'size_id',
            ])
            ->withSum(['saleItems as total_sold' => function($q) {
                $q->where('created_at', '>=', now()->subDays(90));
            }], 'quantity')
            ->with(['unit:id,name,is_decimal', 'brand:id,name', 'size:id,name', 'category:id,name','productType:id,name']);
            
        if ($request->boolean('hide_empty_stock')) {
            $query->where('stock', '>', 0);
        }

        // 2. Logic Search & Filter Server Side
        // A. Filter Search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
            $query->orderByDesc('total_sold');
        }
        // B. Filter Kategori
        if ($request->filled('category_id') && $request->input('category_id') !== 'all') {
            $query->where('category_id', $request->input('category_id'));
        }
        // C. Filter Tipe/SubKategori
        if ($request->filled('product_type_id') && $request->input('product_type_id') !== 'all') {
            $query->where('product_type_id', $request->input('product_type_id'));
        }
        // D. Filter Brand
        if ($request->filled('brand_id') && $request->input('brand_id') !== 'all') {
            $query->where('brand_id', $request->input('brand_id'));
        }
        // E. Filter Size
        if ($request->filled('size_id') && $request->input('size_id') !== 'all') {
            $query->where('size_id', $request->input('size_id'));
        }

        // 3. Sorting & Limiting
        $sort = $request->input('sort', 'default');
        
        if ($request->boolean('only_services')) {
             // LOGIC BARU: Ambil HANYA Jasa/Layanan
             $query->whereHas('category', function($q) {
                 $q->whereIn('name', ['Jasa', 'Layanan']);
             });
             $query->orderBy('name');
        } else {
             // Logic Sorting Utama
             if ($sort === 'cheapest') {
                 $query->orderBy('selling_price', 'asc');
             } elseif ($sort === 'bestseller') {
                 $query->orderByDesc('total_sold');
             } elseif ($sort === 'recommendation') {
                 // LOGIC REKOMENDASI (Weighted Scoring)
                 // Hanya aktif jika ada kategori spesifik (untuk fairness komparasi)
                 if ($request->filled('category_id') && $request->input('category_id') !== 'all') {
                     // Rumus: (Stock * 40%) + ((Margin/1000) * 40%) - (Terjual * 20%)
                     // Margin = selling_price - original_price (disini kita assume original_price ada di tabel purchase_prices atau kita pakai selling_price saja sebagai simplifikasi jika data kurang lengkap, 
                     // tapi idealnya kita butuh margin. Jika tidak join tabel purchase, kita simplifikasi pakai Selling Price tinggi saja).
                     // Catatan: Karena kolom original_price mungkin tidak ada di tabel products (biasanya di purchases), kita pakai asumsi:
                     // Prioritas = Stock Banyak + Harga Jual Tinggi (Asumsi margin sebanding) - Terjual Sedikit.
                     
                     // Revisi Rumus Database Friendly (Tanpa Join Purchase Price dulu biar cepat):
                     // Score = (Stock * 0.4) + (Selling_Price / 10000 * 0.4) - (Total_Sold * 0.2)
                     // Update: Tambahkan CAST untuk memastikan tipe data benar (terutama selling_price jika varchar).
                     // Serta naikkan bobot Margin agar lebih terasa efeknya.
                     
                     // New Formula: (Stock * 0.3) + ((SellingPrice / 1000) * 0.5) - (Sold * 0.2)
                     // Margin/Harga Jual bobot naik jadi 50% dan pembagi diperkecil (1000) agar nilai nominal lebih berpengaruh.
                     
                     $query->orderByRaw('( 
                        (CAST(stock AS DECIMAL(10,2)) * 0.3) + 
                        ((CAST(selling_price AS DECIMAL(15,2)) / 1000) * 0.5) - 
                        (COALESCE((SELECT SUM(quantity) FROM sale_items WHERE product_id = products.id), 0) * 0.2) 
                     ) DESC');
                 } else {
                    // Fallback jika user "hack" request tanpa kategori
                    $query->orderBy('name');
                 }
             } else {
                 $query->orderBy('name');
             }
             
             // LOGIC FILTER JASA: Sembunyikan Jasa/Layanan jika tidak ada filter kategori spesifik
             if (!$request->filled('category_id') || $request->input('category_id') === 'all') {
                 $query->whereDoesntHave('category', function($q) {
                     $q->whereIn('name', ['Jasa', 'Layanan']);
                 });
             }
        }
            
        // --- LOGIC DYNAMIC FILTERS (FACETS) ---
        // Requirement:
        // 1. Jika Category = All -> Tampilkan Semua Brand, Hide Size.
        // 2. Jika Category Selected -> Tampilkan Brand & Size yg available di produk kategori tsb.
        
        $availableBrands = [];
        $availableSizes = [];

        // RE-BUILD BASIC QUERY SCOPE (Search + Category + Type + Jasa Logic)
        // Note: Kita gunakan scope ini untuk mencari "Available Brands" dan "Available Sizes"
        // Kita TIDAK meng-apply filter Brand/Size di sini, karena Facet harus menunjukkan opsi lain
        
        $baseScope = Product::query();
        if ($request->boolean('hide_empty_stock')) {
            $baseScope->where('stock', '>', 0);
        }
        
        // Context 1: Current Search Context
        if ($search) {
             $baseScope->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }
        
        // Context 2: Current Category Context
        $categoryId = $request->input('category_id');
        $hasCategoryFilter = $request->filled('category_id') && $categoryId !== 'all';
        
        if ($hasCategoryFilter) {
            $baseScope->where('category_id', $categoryId);
        }
        
        if ($request->filled('product_type_id') && $request->input('product_type_id') !== 'all') {
            $baseScope->where('product_type_id', $request->input('product_type_id'));
        }
        
        // Jasa Logic
        if ($request->boolean('only_services')) {
             $baseScope->whereHas('category', function($q) { $q->whereIn('name', ['Jasa', 'Layanan']); });
        } else {
             if (!$hasCategoryFilter) {
                  // Jika All Category, exclude Jasa (default pos behavior usually) 
                  // Kecuali user cari spesifik? 
                  // Mari kita ikut logic utama:
                  $baseScope->whereDoesntHave('category', function($q) { $q->whereIn('name', ['Jasa', 'Layanan']); });
             }
        }

        // 1. Get Available Brands
        // Logic: Ambil brand dari scope produk saat ini.
        // Jika Category All (dan no search), baseScope covers almost all products (minus jasa).
        // Maka ini akan return semua brand. Ini SESUAI request "tampilkan semua brands".
        $availableBrands = Brand::whereIn('id', $baseScope->clone()->select('brand_id')->distinct())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();


        // 2. Get Available Sizes
        // Logic: "ketika all (Category) ... hide size"
        if ($hasCategoryFilter || $search) {
            // Jika ada filter kategori ATAU ada search -> baru cari Size
            $availableSizes = \App\Models\Size::whereIn('id', $baseScope->clone()->select('size_id')->distinct())
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        } else {
            // Jika Category All & No Search -> Kosongkan Size (Hide)
            $availableSizes = []; 
        }

        // --- END FACETS ---

        // --- END FACETS ---

        $products = $query->limit($limit)->get();

        // --- DYNAMIC THRESHOLD LOGIC ---
        // Hitung Rata-rata Global (Cache 10 menit agar performa terjaga)
        $globalStats = \Illuminate\Support\Facades\Cache::remember('pos_product_global_stats', 600, function () {
            $totalSold90d = \Illuminate\Support\Facades\DB::table('sale_items')
                ->where('created_at', '>=', now()->subDays(90))
                ->sum('quantity');
            
            $productCount = \App\Models\Product::count();
            $avgStock = \App\Models\Product::avg('stock');

            return [
                'avg_sold' => $productCount > 0 ? $totalSold90d / $productCount : 0,
                'avg_stock' => $avgStock ?? 0
            ];
        });

        $avgSold = $globalStats['avg_sold'];
        $avgStock = $globalStats['avg_stock'];

        // Transform data untuk menambahkan flag Badge
        $products->transform(function ($product) use ($avgSold, $avgStock) {
            $totalSold = (float) $product->total_sold;
            $stock = (float) $product->stock;

            // Logic Best Seller: Sold > 1.5x Rata-rata (Min 5 unit biar valid)
            $product->is_best_seller = $totalSold > ($avgSold * 1.5) && $totalSold > 5;

            // Logic Stok Banyak (Dead Stock): Stok > 1.5x Rata-rata AND Sold < 0.5x Rata-rata
            $product->is_dead_stock = $stock > ($avgStock * 1.5) && $totalSold < ($avgSold * 0.5);

            return $product;
        });

        return response()->json([
            'products' => $products,
            'available_brands' => $availableBrands,
            'available_sizes' => $availableSizes,
            'stats' => $globalStats // Optional: Kirim stats buat debug frontend
        ]);
    }
}
