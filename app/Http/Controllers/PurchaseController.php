<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\ProductService;
use App\Services\PurchaseService;
use App\Services\SupplierService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;  // Untuk dropdown
use Inertia\Inertia; // Untuk dropdown

class PurchaseController extends Controller
{
    protected $purchaseService;

    protected $supplierService;

    protected $productService;

    protected $invoiceService;

    public function __construct(
        PurchaseService $purchaseService,
        SupplierService $supplierService,
        ProductService $productService,
        InvoiceService $invoiceService
    ) {
        $this->purchaseService = $purchaseService;
        $this->supplierService = $supplierService;
        $this->productService = $productService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $purchases = $this->purchaseService->get($request->all());

        // --- Summary Statistics (Dashboard) ---
        $now = now();
        
        // 1. Belanja Bulan Ini vs Bulan Lalu
        $spendThisMonth = Purchase::whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->sum('grand_total');
            
        $lastMonthDate = $now->copy()->subMonth();
        $spendLastMonth = Purchase::whereYear('transaction_date', $lastMonthDate->year)
            ->whereMonth('transaction_date', $lastMonthDate->month)
            ->sum('grand_total');

        // 2. Belanja Tahun Ini
        $spendThisYear = Purchase::whereYear('transaction_date', $now->year)->sum('grand_total');
        $spendLastYear = Purchase::whereYear('transaction_date', $now->copy()->subYear()->year)->sum('grand_total');

        // 3. Top Supplier Bulan Ini
        $topSupplier = Purchase::selectRaw('supplier_id, SUM(grand_total) as total')
            ->whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->whereNotNull('supplier_id')
            ->groupBy('supplier_id')
            ->orderByDesc('total')
            ->with('supplier:id,name')
            ->first();

        // 4. Active Plan (Draft/Proses)
        $activeOrderCount = Purchase::whereIn('status', [
            Purchase::STATUS_DRAFT, 
            Purchase::STATUS_ORDERED, 
            Purchase::STATUS_SHIPPED,
            Purchase::STATUS_CHECKING
        ])->count();

        // 5. Chart 12 Bulan Terakhir
        $chartLabels = [];
        $chartValues = [];
        for ($i = 11; $i >= 0; $i--) {
             $d = $now->copy()->subMonths($i);
             $chartLabels[] = $d->translatedFormat('M y'); // Jan 24
             
             $val = Purchase::whereYear('transaction_date', $d->year)
                    ->whereMonth('transaction_date', $d->month)
                    ->sum('grand_total');
             $chartValues[] = (int) $val;
        }

        $summary = [
            'spend_this_month' => $spendThisMonth,
            'spend_growth_month' => $spendLastMonth > 0 ? round((($spendThisMonth - $spendLastMonth) / $spendLastMonth) * 100, 1) : 0,
            
            'spend_this_year' => $spendThisYear,
            'spend_growth_year' => $spendLastYear > 0 ? round((($spendThisYear - $spendLastYear) / $spendLastYear) * 100, 1) : 0,

            'top_supplier_name' => $topSupplier->supplier->name ?? '-',
            'top_supplier_amount' => $topSupplier->total ?? 0,
            
            'active_orders' => $activeOrderCount,

            'chart' => [
                'labels' => $chartLabels,
                'values' => $chartValues
            ]
        ];

        return Inertia::render('Purchases/index', [
            'purchases' => $purchases,
            'summary' => $summary, // Pass Summary
            'dropdowns' => [
                'suppliers' => $this->supplierService->getAll(),
                'purchaseStatuses' => Purchase::STATUSES,
                'users' => User::select('id', 'name')->get(),
            ],
            'filters' => $request->all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Ambil data untuk Filter Katalog
        $categories = \App\Models\Category::with('productTypes')->select('id', 'name')->get();
        $brands = \App\Models\Brand::select('id', 'name')->get();

        return Inertia::render('Purchases/create', [
            'dropdowns' => [
                'suppliers' => $this->supplierService->getAll(),
                'statuses' => Purchase::STATUSES,
                'purchase' => null,
            ],
            // Data Filter Tambahan
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = array_merge($request->all(), ['user_id' => Auth::id()]);

        // Service 'create' akan menangani validasi & penyimpanan
        $this->purchaseService->create($data);

        return Redirect::route('purchases.index')
            ->with('success', 'Transaksi pembelian berhasil dibuat.');
    }

    /**
     * Mengambil daftar produk yang direkomendasikan untuk dibeli.
     * Kriteria: Stock saat ini <= Minimum Stock, dan Filter Supplier.
     */
    public function getRecommendations($supplierId, Request $request)
    {
        if ($request->ajax()) {
            $res = $this->purchaseService->getRecomendations($supplierId);

            return response()->json($res);
        }
    }

    public function getCatalog($supplierId, Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $subCategoryId = $request->input('product_type_id'); // Sub Kategori
        $brandId = $request->input('brand_id');
        $sizeId = $request->input('size_id');
        $stockStatus = $request->input('stock_status'); // 'empty', 'low', 'safe'

        // Base Query (Tanpa Filter Facet)
        $baseQuery = Product::query()
            ->where('status', 'active')
            ->where('supplier_id', $supplierId);

        // --- 1. UTAMA: Query Produk (Apply Semua Filter) ---
        $productQuery = $baseQuery->clone()
            ->select('id', 'name', 'code', 'stock', 'min_stock', 'purchase_price', 'image_url', 'image_path', 'unit_id', 'size_id', 'category_id', 'brand_id', 'product_type_id', 'supplier_id')
            ->with(['unit:id,name', 'size:id,name', 'category:id,name', 'brand:id,name', 'productType:id,name', 'insights']);

        // Apply Search (Global - SMART SEARCH LOGIC)
        if ($search) {
             $searchTerms = array_filter(explode(' ', $search));
             
             $productQuery->where(function ($q) use ($search, $searchTerms) {
                // Exact Match Keras (Super Relevan)
                $q->where('code', $search)
                  ->orWhere('name', 'like', $search . '%');
                  
                // Multi-Word AND Matching (Cari Tiap Kata)
                $q->orWhere(function ($subQuery) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $subQuery->where('name', 'like', "%{$term}%");
                    }
                });
                
                // Cross-Column Relational Search
                $q->orWhereHas('category', function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('brand', function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%");
                })->orWhereHas('size', function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        // Add Sales Stats for Badges
        $productQuery->withSum(['saleItems as sold_last_90_days' => function ($query) {
            $query->where('created_at', '>=', now()->subDays(90));
        }], 'quantity');

        if ($categoryId && $categoryId !== 'all') $productQuery->where('category_id', $categoryId);
        if ($subCategoryId && $subCategoryId !== 'all') $productQuery->where('product_type_id', $subCategoryId);
        if ($brandId && $brandId !== 'all') $productQuery->where('brand_id', $brandId);
        if ($sizeId && $sizeId !== 'all') $productQuery->where('size_id', $sizeId);
        
        if ($stockStatus) {
            if ($stockStatus === 'empty') {
                $productQuery->where('stock', '<=', 0);
            } elseif ($stockStatus === 'low') {
                $productQuery->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0);
            } elseif ($stockStatus === 'safe') {
                $productQuery->whereColumn('stock', '>', 'min_stock');
            }
        }

        // --- 2. FACETED DATA (Smart Filters) ---
        
        // Helper untuk clone dan filter (kecuali key tertentu)
        $getScope = function($excludeKeys = []) use ($baseQuery, $request, $search) {
            $q = $baseQuery->clone();
            if ($search) {
                 $searchTerms = array_filter(explode(' ', $search));
                 $q->where(function ($sq) use ($search, $searchTerms) {
                    // Exact Match Keras (Super Relevan)
                    $sq->where('code', $search)
                      ->orWhere('name', 'like', $search . '%');
                      
                    // Multi-Word AND Matching (Cari Tiap Kata)
                    $sq->orWhere(function ($subQuery) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $subQuery->where('name', 'like', "%{$term}%");
                        }
                    });
                     // Cross-Column Relational Search
                    $sq->orWhereHas('category', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%");
                    })->orWhereHas('brand', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%");
                    })->orWhereHas('size', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%");
                    });
                });
            }
            // Apply filter jika TIDAK di-exclude
            if (!in_array('category', $excludeKeys) && $request->filled('category_id') && $request->input('category_id') !== 'all') 
                $q->where('category_id', $request->input('category_id'));
            
            if (!in_array('brand', $excludeKeys) && $request->filled('brand_id') && $request->input('brand_id') !== 'all')
                $q->where('brand_id', $request->input('brand_id'));
                
            if (!in_array('type', $excludeKeys) && $request->filled('product_type_id') && $request->input('product_type_id') !== 'all')
                $q->where('product_type_id', $request->input('product_type_id'));

            // Size biasanya paling bawah, jarang mempengaruhi filter atas, tapi bisa jadi
            if (!in_array('size', $excludeKeys) && $request->filled('size_id') && $request->input('size_id') !== 'all')
                $q->where('size_id', $request->input('size_id'));

            return $q;
        };

        // A. Available Categories
        // Logic: Filter by Brand/Type/Size (Ignore Category)
        $availableCategories = \App\Models\Category::whereIn('id', $getScope(['category'])->select('category_id')->distinct())
            ->with('productTypes') // Eager load sub categories (product types) for mapping later if needed
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // B. Available Brands
        // Logic: Filter by Category/Type/Size (Ignore Brand)
        $availableBrands = \App\Models\Brand::whereIn('id', $getScope(['brand'])->select('brand_id')->distinct())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        // C. Available Types (Sub Category)
        // Logic: Filter by Category/Brand/Size (Ignore Type)
        // Note: User request "Tipe produk hanya aktif jika kategori aktif" -> Front end logic hiding, but backend sends valid data.
        $availableTypes = \App\Models\ProductType::whereIn('id', $getScope(['type'])->select('product_type_id')->distinct())
            ->select('id', 'name', 'category_id')
            ->orderBy('name')
            ->get();
        
        // D. Available Sizes
        // Logic: Filter by Category/Brand/Type (Ignore Size)
        // Muncul jika ada filter aktif (Category/Brand)
        $hasActiveFilter = ($categoryId && $categoryId !== 'all') || ($brandId && $brandId !== 'all') || $search;
        $availableSizes = [];
        if ($hasActiveFilter) {
            $availableSizes = \App\Models\Size::whereIn('id', $getScope(['size'])->select('size_id')->distinct())
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }

        // 3. SORTING DEFAULT
        if ($search) {
            // RELEVANCE SORTING PADA SMART SEARCH
            // 1. Exact Code (Paling Atas)
            // 2. Dimulai dengan nama persis
            // 3. Mengandung nama utuh
            // 4. Default relasi lain
            $productQuery->orderByRaw("
                CASE 
                    WHEN code = ? THEN 1
                    WHEN name LIKE ? THEN 2
                    WHEN name LIKE ? THEN 3
                    ELSE 4
                END ASC
            ", [$search, $search . '%', '%' . $search . '%']);
            // Sebagai secondary sort
            $productQuery->orderBy('stock', 'asc')->orderBy('name', 'asc');
        } else {
             $productQuery->orderBy('stock', 'asc') 
                          ->orderBy('name', 'asc');
        }
        
        $productsForAutocomplete = $productQuery->paginate(20); 

        // Return Data + Dynamic Filters
        return response()->json([
            'products' => $productsForAutocomplete,
            'available_categories' => $availableCategories,
            'available_brands' => $availableBrands,
            'available_types' => $availableTypes,
            'available_sizes' => $availableSizes,
        ]);
    }

    /**
     * Mengubah status transaksi cepat (dari Index/Aksi Cepat).
     */
    public function updateStatus(Request $request, Purchase $purchase)
    {
        $request->validate(['status' => ['required', Rule::in(Purchase::STATUSES)]]);

        // Memanggil Service untuk update status
        $this->purchaseService->updateStatus($purchase->id, $request->input('status'));

        return Redirect::back()->with('success', 'Status transaksi berhasil diperbarui.');
    }

    /**
     * Display the specified resource.
     */
    public function checking(Purchase $purchase)
    {
        $purchase->load([
            // 1. Supplier: Hanya ambil data identitas, abaikan timestamps/meta data
            'supplier:id,name,code,phone,address',

            // 2. User: PENTING! Hanya ambil Nama/ID. Jangan load email/password/token (Security Risk)
            'user:id,name',

            // 3. Purchase Items: Ambil kolom data transaksi saja
            // Wajib bawa 'product_id' agar relasi ke product jalan
            // Wajib bawa 'purchase_invoice_id' agar relasi ke invoice jalan
            'items:id,purchase_id,product_id,purchase_invoice_id,quantity,purchase_price,subtotal,item_status,product_snapshot',

            // 4. Product (Induk): Ambil data display saja + Foreign Key untuk relasi bawahnya
            // Wajib bawa: brand_id, product_type_id, unit_id
            'items.product:id,name,code,image_path,brand_id,product_type_id,unit_id',

            // 5. Relasi Nested (Brand, Type, Unit) - Sudah Anda optimalkan, ini oke.
            'items.product.brand:id,name',
            'items.product.productType:id,name',
            'items.product.unit:id,name',

            // 6. Invoice per Item: Cukup ID dan Nomor/Status Nota
            'items.invoice:id,invoice_number,payment_status,total_amount',

            // 7. Daftar Invoice (Header): Ambil ringkasan keuangan saja
            'invoices:id,purchase_id,invoice_number,invoice_date,due_date,total_amount,payment_status,status,invoice_image',

            // 8. Item di dalam Invoice: Biasanya cuma butuh snapshot/qty untuk display
            'invoices.items:id,purchase_id,purchase_invoice_id',
        ]);
        $purchase->loadSum('invoices', 'total_amount');
        $invoice = $purchase->invoices->first() ?? new PurchaseInvoice;

        return Inertia::render('Purchases/PurchaseDetail', [
            'purchase' => $purchase,
            'invoice' => $invoice,
            'paymentStatuses' => PurchaseInvoice::PAYMENT_STATUSES,

            // Flag untuk frontend (FE) agar tahu apakah tombol aksi harus ditampilkan
            'isCheckingMode' => in_array($purchase->status, [
                Purchase::STATUS_RECEIVED,
                Purchase::STATUS_CHECKING,
            ]),
        ]);
    }

    // MENAMBAHKAN INVOICE KE TRANSAKSI
    public function storeInvoice(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== Purchase::STATUS_RECEIVED && $purchase->status !== Purchase::STATUS_CHECKING) {
            return redirect()->back()->with('error', 'Invoice hanya bisa diunggah pada status Diterima atau sedang Divalidasi. status saat ini');
        }
        try {
            $this->invoiceService->store($request->all(), $purchase);
            if (Purchase::STATUS_RECEIVED) {
                $this->purchaseService->updateStatus($purchase->id, Purchase::STATUS_CHECKING);
            }

            return redirect()->route('purchases.checking', $purchase)
                ->with('success', 'Nota berhasil diunggah. Silakan lakukan validasi item.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan data nota: '.$e->getMessage());
        }
    }

    /**
     * Memperbarui Nota (Action Edit dari Modal).
     * Rute: PUT purchases/{purchase}/invoices/{invoice}
     */
    public function updateInvoice(Request $request, Purchase $purchase, PurchaseInvoice $invoice)
    {
        // Panggil Service untuk update
        $this->invoiceService->update($request->all(), $invoice);

        return redirect()->back()->with('success', 'Nota berhasil diperbarui.');
    }

    /**
     * Menghapus Nota (Action Delete).
     * Rute: DELETE purchases/{purchase}/invoices/{invoice}
     */
    public function destroyInvoice(Purchase $purchase, PurchaseInvoice $invoice)
    {
        // Panggil Service untuk menghapus nota dan file
        try {
            $this->invoiceService->destroy($invoice);

            return redirect()->back()->with('success', 'Nota berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Detail Nota (Action Detail).
     * Rute: Detail purchases/{purchase}/invoices/{invoice}
     */
    public function linkItemsView(Purchase $purchase, PurchaseInvoice $invoice)
    {
        $unlinkedItems = null;
        if ($invoice->status !== PurchaseInvoice::STATUS_VALIDATED) {
            $unlinkedItems = $purchase->items()
                ->whereNull('purchase_invoice_id')
                ->with('product:id,name,code,stock,image_path') // Load info produk master
                ->get();
        }

        // 3. Ambil Item yang SUDAH Tertaut ke Invoice ini (untuk detail di FE)
        $linkedItems = $invoice->items()->with('product:id,name,code')->get();
        $products = Product::select('id', 'name', 'code', 'purchase_price', 'image_path', 'unit_id', 'size_id', 'category_id', 'brand_id', 'product_type_id', 'supplier_id')
            ->with(['unit:id,name', 'size:id,name', 'category:id,name', 'brand:id,name', 'productType:id,name'])
            ->where('status', 'active')
            ->where('supplier_id', $purchase->supplier->id)
            ->get();

        $purchase->load(['supplier:id,name,phone,address']);

        // 4. Kirim data ke Frontend
        return Inertia::render('Purchases/InvoiceLinkagePage', [
            'purchase' => $purchase->only(['id', 'reference_no', 'status',  'supplier']),
            'invoice' => $invoice,
            'unlinkedItems' => $unlinkedItems,
            'linkedItems' => $linkedItems,
            'products' => $products,
        ]);
    }

    // MENAUTKAN PRODUK KE INVOICE
    public function linkItems(Request $request, Purchase $purchase, PurchaseInvoice $invoice)
    {
        // 1. Validasi Input Item Ids
        $request->validate([
            'type' => 'required|in:link,create',
            'newQty' => 'nullable|min:0|numeric',
            'newPrice' => 'nullable|min:0|numeric',
            // Rule 1: ids wajib array & required HANYA JIKA type == link
            'ids' => 'required_if:type,link|array',
            'ids.*' => 'exists:purchase_items,id',
            // Rule 2: product_id wajib required HANYA JIKA type == create
            'product_id' => 'required_if:type,create|exists:products,id',
        ], [
            'ids.required_if' => 'Harap pilih item yang akan ditautkan.',
            'product_id.required_if' => 'Harap pilih produk master untuk ditambahkan.',
        ]);
        try {
            // 2. Panggil Service Logika Penautan & Perhitungan Harga
            $this->invoiceService->smartLinkProductsByProductId($invoice, $request->all());

            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Melepaskan item dari Nota (Dipanggil dari InvoiceLinkagePage).
     */
    public function unlinkItems(Request $request, Purchase $purchase, PurchaseInvoice $invoice)
    {
        $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'exists:purchase_items,id',
        ]);

        try {
            $count = $this->invoiceService->unlinkItems($invoice, $request->input('item_ids'));

            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal melepas item: '.$e->getMessage());
        }
    }

    /**
     * Menerima array item yang Qty/Harganya sudah dikoreksi dari InvoiceLinkagePage.
     * MEMPERBARUI DATA QTY DAN HARGA PRODUK TRANSAKSI
     */
    public function updateLinkedItemDetails(Request $request, Purchase $purchase, PurchaseInvoice $invoice)
    {
        // Guard: Pastikan transaksi masih dalam tahap CHECKING
        if ($purchase->status !== Purchase::STATUS_CHECKING) {
            return redirect()->back()->with('error', 'Koreksi hanya dapat disimpan saat status Checking.');
        }

        try {
            // Panggil Service untuk update massal
            $this->invoiceService->updateItemDetails($request->input('items'), $invoice);

            return redirect()->back();
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan koreksi: '.$e->getMessage());
        }
    }

    /**
     * Memvalidasi Nota (Action Validate dari InvoiceLinkagePage).
     */
    public function validateInvoice(Purchase $purchase, PurchaseInvoice $invoice)
    {
        // Guard: Pastikan transaksi masih dalam tahap CHECKING
        if ($purchase->status !== Purchase::STATUS_CHECKING) {
            return redirect()->back()->with('error', 'Invoice hanya dapat divalidasi saat status Checking.');
        }
        if ($invoice->status === PurchaseInvoice::STATUS_VALIDATED) {
            return redirect()->back()->with('error', 'Invoice sudah divalidasi sebelumnya.');
        }
        if ($invoice->items()->count() === 0) {
            return redirect()->back()->with('error', 'Tidak dapat memvalidasi invoice tanpa item yang ditautkan.');
        }

        try {
            // Panggil Service untuk validasi invoice
            $this->invoiceService->validateInvoice($invoice);

            return redirect()->back()->with('success', 'Invoice berhasil divalidasi.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memvalidasi invoice: '.$e->getMessage());
        }
    }

    /**
     * Menyelesaikan transaksi (Action Final).
     */
    public function finalize(Request $request, Purchase $purchase)
    {
        // Validasi Input Biaya Tambahan (Boleh 0)
        $validated = $request->validate([
            'shipping_cost' => 'required|numeric|min:0',
            'other_costs' => 'required|numeric|min:0',
            'notes' => 'nullable',
        ]);

        try {
            $this->purchaseService->finalizeTransaction($purchase, $validated);

            return redirect()->route('purchases.show', $purchase)
                ->with('success', 'Transaksi Selesai! Stok telah bertambah dan HPP diperbarui.');
        } catch (\Exception $e) {
            // Tangkap error validasi logika dari service
            return redirect()->back()->with('error', 'Gagal menyelesaikan transaksi: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        $purchase->load(['items.product.unit', 'supplier']);
        // --- GATE CHECK: Redirect jika status sudah 'paid' atau 'received' ---
        // Karena status ini biasanya sudah final dan masuk akuntansi
        if (in_array($purchase->status, [Purchase::STATUS_RECEIVED])) { // Sesuaikan dengan logika bisnis Anda
            return redirect()->route('purchases.show', $purchase->id)
                ->with('error', 'Transaksi sudah diterima, tidak bisa diedit.');
        }

        return Inertia::render('Purchases/create', [
            'purchase' => $purchase,
            'dropdowns' => [
                'suppliers' => $this->supplierService->getAll(),
                'statuses' => Purchase::STATUSES,
            ],
            // Data Filter
            'categories' => \App\Models\Category::with('productTypes')->select('id', 'name')->get(),
            'brands' => \App\Models\Brand::select('id', 'name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        try {
            // Panggil Service yang pintar tadi
            // Service akan otomatis mendeteksi Draft vs Ordered
            // dd($request);
            $this->purchaseService->updatePurchase($purchase, $request->validated());

            return redirect()->route('purchases.index')
                ->with('success', 'Transaksi berhasil diperbarui.');
        } catch (ValidationException $e) {
            // Tangkap error validasi bisnis (misal: coba hapus item di status ordered)
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            // Error umum sistem
            return back()->with('error', 'Terjadi kesalahan sistem: '.$e->getMessage());
        }
    }

    public function show(Purchase $purchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        // SECURITY GUARD: Hanya boleh hapus jika transaksi belum berjalan
        if (! in_array($purchase->status, [Purchase::STATUS_DRAFT, Purchase::STATUS_ORDERED])) {
            return Redirect::back()
                ->with('error', 'Transaksi yang sudah dikirim atau diterima tidak bisa dihapus. Harap batalkan.');
        }

        // Logika hapus (kita asumsikan tidak ada soft delete di Purchase)
        $purchase->items()->delete(); // Hapus semua item terkait
        $purchase->delete(); // Hapus header

        return Redirect::back()->with('success', 'Transaksi berhasil dihapus.');
    }

    public function print($id)
    {
        // 1. Ambil Data Lengkap
        $purchase = Purchase::with([
            'supplier',
            'user',
            'items.product.unit', // Relasi ke Unit produk
            'items.product.size',  // Relasi ke Size produk
        ])->findOrFail($id);

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        // 2. Data Toko (Hardcode dulu atau ambil dari Setting jika ada)
        $storeProfile = [
            'name' => $settings['shop_name'] ?? 'INVENTRA CORP',
            'address' => $settings['shop_address'] ?? 'Alamat Belum Diisi',
            'phone' => $settings['shop_phone'] ?? '-',
            'email' => '',
        ];

        // 3. Load View PDF
        $pdf = Pdf::loadView('exports.purchase_order', [
            'po' => $purchase,
            'store' => $storeProfile,
        ]);

        $safeRef = str_replace(['/', '\\'], '-', $purchase->reference_no);
        $filename = $safeRef.'.pdf';

        // 4. Set Kertas & Stream (Tampil di browser)
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }
}
