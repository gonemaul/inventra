<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use App\Models\SmartInsight;
use Illuminate\Http\Request;
use App\Services\SizeService;
use App\Services\TypeService;
use App\Services\UnitService;
use App\Services\BrandService;
use App\Services\InsightService;
use App\Services\ProductService;
use App\Services\CategoryService;
use App\Services\SupplierService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class ProductController extends Controller
{
    protected $productService;
    protected $categoryService;
    protected $unitService;
    protected $sizeService;
    protected $supplierService;
    protected $brandService;
    protected $typeService;
    protected $insightService;

    // 3. Inject semua service di constructor
    public function __construct(
        ProductService $productService,
        CategoryService $categoryService,
        UnitService $unitService,
        SizeService $sizeService,
        SupplierService $supplierService,
        BrandService $brandService,
        TypeService $typeService,
        InsightService $insightService
    ) {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->unitService = $unitService;
        $this->sizeService = $sizeService;
        $this->supplierService = $supplierService;
        $this->brandService = $brandService;
        $this->typeService = $typeService;
        $this->insightService = $insightService;
    }
    private function getDropdownData(): array
    {
        return [
            'categories' => $this->categoryService->getAll(),
            'units' => $this->unitService->getAll(),
            'sizes' => $this->sizeService->getAll(),
            'suppliers' => $this->supplierService->getAll(),
            'productStatuses' => Product::STATUSES,
            'brand' => $this->brandService->getAll(),
            'type' => $this->typeService->getAll(),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Products/index', [
            'products' => $this->productService->get($request->all()),
            'filters' => $request->all(),
            'dropdowns' => $this->getDropdownData()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Products/create', [
            'dropdowns' => $this->getDropdownData()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->productService->create($request->all());

        return Redirect::route('products.index')
            ->with('success', 'Produk berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        // 1. Jalankan Analisa DSS (Agar data fresh saat dibuka)
        $this->insightService->runAnalysis();

        // 2. Ambil Semua Data Detail dari Service
        $data = $this->productService->getProductDetails($product->id);

        // 3. Kirim ke Vue
        return Inertia::render('Products/detail', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return Inertia::render('Products/edit', [
            'product' => $product, // Kirim data produk yang ada
            'dropdowns' => $this->getDropdownData() // Kirim data dropdown
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $this->productService->update($product->id, $request->all());

        return Redirect::route('products.index')
            ->with('success', 'Produk berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $isPermanent = $request->input('permanen', false);
        $this->productService->delete($id, $request->all());

        $message = $isPermanent
            ? 'Produk berhasil dihapus permanen!'
            : 'Produk berhasil dipindahkan ke sampah!';

        // Redirect kembali ke halaman index
        return Redirect::back()
            ->with('success', $message);
    }
    public function restoreProduct($id)
    {
        $this->productService->restore($id);

        return Redirect::back()
            ->with('success', 'Produk berhasil dipulihkan!');
    }
}
