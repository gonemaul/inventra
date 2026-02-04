<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductUpdateRequest;
use App\Models\Product;
use App\Services\BrandService;
use App\Services\CategoryService;
use App\Services\InsightService;
use App\Services\ProductService;
use App\Services\SizeService;
use App\Services\SupplierService;
use App\Services\TypeService;
use App\Services\UnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

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
            'brands' => $this->brandService->getAll(),
            'units' => $this->unitService->getAll(),
            'sizes' => $this->sizeService->getAll(),
            'suppliers' => $this->supplierService->getAll(),
            'types' => $this->typeService->getAll(),
            'productStatuses' => Product::STATUSES,
        ];
    }

    public function searchProducts(Request $request)
    {
        $keyword = $request->input('q'); // misal frontend kirim parameter ?q=nama_produk
        $limit = $request->input('limit', 20); // Default ambil 20 item saja agar ringan

        $query = Product::query()
            ->with('category:id,name', 'unit:id,name', 'size:id,name', 'supplier:id,name', 'brand:id,name', 'productType:id,name', 'insight', 'movements');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('code', 'LIKE', "%{$keyword}%")
                    ->orWhere('slug', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('productType', fn ($s) => $s->where('name', 'LIKE', "%{$keyword}%"))
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'LIKE', "%{$keyword}%"))
                    ->orWhereHas('brand', fn ($s) => $s->where('name', 'LIKE', "%{$keyword}%"))
                    ->orWhereHas('size', fn ($s) => $s->where('name', 'LIKE', "%{$keyword}%"));
            });
        }

        $products = $query->limit($limit)->get();
        $products->makeHidden([
            'created_at',
            'updated_at',
            'deleted_at',
            'category_id',
            'unit_id',
            'size_id',
            'supplier_id',
            'brand_id',
            'product_type_id',
            'inventory_type',
        ]);

        return response()->json($products);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Products/index', [
            'products' => $this->productService->get($request->all()),
            'filters' => $request->all(),
            'dropdowns' => Inertia::defer(
                fn () => $this->getDropdownData()
            ),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Products/create', [
            // 'dropdowns' => $this->getDropdownData()
            'dropdowns' => Inertia::defer(fn () => $this->getDropdownData()),
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
        $product->load(['category:id,name', 'unit:id,name', 'size:id,name', 'supplier:id,name', 'brand:id,name', 'productType:id,name'])
            ->withTrashed(); // Handle jika produk soft deleted

        return Inertia::render('Products/detail', [
            'detail' => Inertia::defer(fn () => $this->productService->getProductDetails($product)),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return Inertia::render('Products/edit', [
            'product' => Inertia::defer(
                fn () => $product
            ), // Kirim data produk yang ada
            'dropdowns' => Inertia::defer(
                fn () => $this->getDropdownData()
            ), // Kirim data dropdown
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        // dd($request);
        $data = $request->validated();

        $this->productService->update($product, $data);

        return Redirect::route('products.index')
            ->with('success', 'Produk berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Product $product)
    {
        $isPermanent = $request->input('permanen', false);
        $this->productService->delete($product->id, $request->all());

        $message = $isPermanent
            ? 'Produk berhasil dihapus permanen!'
            : 'Produk berhasil dipindahkan ke sampah!';

        // Redirect kembali ke halaman index
        return Redirect::back()
            ->with('success', $message);
    }

    public function restoreProduct(Product $product)
    {
        $this->productService->restore($product->id);

        return Redirect::back()
            ->with('success', 'Produk berhasil dipulihkan!');
    }
}
