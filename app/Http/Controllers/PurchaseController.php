<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Services\PurchaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Services\ProductService;  // Untuk dropdown
use App\Services\SupplierService; // Untuk dropdown

class PurchaseController extends Controller
{
    protected $purchaseService;
    protected $supplierService;
    protected $productService;

    public function __construct(
        PurchaseService $purchaseService,
        SupplierService $supplierService,
        ProductService $productService
    ) {
        $this->purchaseService = $purchaseService;
        $this->supplierService = $supplierService;
        $this->productService = $productService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // if ($request->ajax()) {
        //     return $purchases;
        // }
        $purchases = $this->purchaseService->get($request->all());

        return Inertia::render('Purchases/index', [
            'purchases' => $purchases,
            'dropdowns' => [
                'suppliers' => $this->supplierService->getAll(),
                'purchaseStatuses' => Purchase::STATUSES
            ],
            'filters' => $request->all(),
        ]);
        // return Inertia::render('Purchases/index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productsForAutocomplete = Product::select(
            'id',
            'name',
            'code',
            'stock',
            'min_stock',
            'purchase_price',
            'image_path',
            'unit_id',
            'size_id',
            'category_id'
        )
            ->with([
                // Muat relasi yang penting saja
                'unit:id,name',
                'size:id,name',
                'category:id,name'
            ])
            ->where('status', Product::STATUS_ACTIVE) // Hanya produk aktif
            ->get();
        return Inertia::render('Purchases/create', [
            'dropdowns' => [
                'suppliers' => $this->supplierService->getAll(),
                'statuses' => Purchase::STATUSES,
            ],
            'products' => $productsForAutocomplete,
            // 'recommendations' => $recommendationData, // (Untuk nanti)
        ]);
        // return Inertia::render('Purchases/create');
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
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        return Inertia::render('Purchases/detail', [
            'type' => 'detail',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        //
    }
}
