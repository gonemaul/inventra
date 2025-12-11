<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Services\CategoryService;
use App\Services\SalesRecapService;
use Illuminate\Support\Facades\Redirect;

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
        $sales = $this->service->get($request->all());
        return Inertia::render('Sale/index', [
            'sales' => $sales,
            'filters' => $request->only(['search', 'min_date', 'max_date', 'min_revenue', 'max_revenue',]),
        ]);
    }

    public function posIndex(Request $request)
    {
        return Inertia::render('Pos/Index', [
            'products' => Product::get(),
            'categories' => $this->categoryService->getAll(),
            'customers' => Customer::orderBy('name')->get(),
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Sale/form', ['mode' => 'create']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi Array Items
        $validated = $request->validate([
            'input_type' => 'required|in:realtime,recap',
            'customer_id' => 'nullable|exists:customers,id',
            'report_date' => 'required|date|before_or_equal:today',
            'created_at' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001', // Support Desimal
            'items.*.selling_price' => 'required|numeric|min:0',
        ], [
            'items.min' => 'Keranjang penjualan tidak boleh kosong.',
            'items.*.quantity.min' => 'Jumlah barang harus lebih dari 0.',
            'report_date.before_or_equal' => 'Tanggal laporan tidak boleh melebihi hari ini.'
        ]);

        try {
            $this->service->storeRecap($validated);

            return Redirect::route('sales.index')
                ->with('success', 'Rekap penjualan berhasil disimpan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * API untuk Search Bar di Frontend
     * Menerima parameter ?query=nama_produk
     */
    public function searchProduct(Request $request)
    {
        $search = $request->input('query');

        if (!$search) {
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
                    'name' => $product->full_name, // Pakai Accessor Full Name Komposit
                    'stock' => (float) $product->stock, // Cast ke float biar gak string
                    'unit' => $product->unit->name ?? 'Pcs',
                    'price' => (float) $product->selling_price,
                    'brand' => $product->brand->name,
                    'image' => $product->image_path ? asset('storage/' . $product->image_path) : null,
                ];
            });

        return response()->json($products);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        $sale->load(['items', 'user']);
        return Inertia::render('Sale/Show', [
            'sale' => $sale
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale)
    {
        $sale->load('items.product.unit');

        return Inertia::render('Sale/form', [
            'sale' => $sale, // Kirim data lama ke Vue
            'mode' => 'edit' // Penanda mode
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
}
