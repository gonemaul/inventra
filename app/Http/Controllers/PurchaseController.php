<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Models\PurchaseInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Services\InvoiceService;
use App\Services\PurchaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\UpdatePurchaseRequest;
use Illuminate\Validation\ValidationException;
use App\Services\ProductService;  // Untuk dropdown
use App\Services\SupplierService; // Untuk dropdown

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
        return Inertia::render('Purchases/index', [
            'purchases' => $purchases,
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
        return Inertia::render('Purchases/create', [
            'dropdowns' => [
                'suppliers' => $this->supplierService->getAll(),
                'statuses' => Purchase::STATUSES,
                'purchase' => null
            ],
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

    /**
     *
     */
    public function getCatalog($supplierId, Request $request)
    {
        $search = $request->input('search');
        $productsForAutocomplete = Product::query()
            ->where('status', 'active')
            ->where('supplier_id', $supplierId)
            ->select('id', 'name', 'code', 'stock', 'min_stock', 'purchase_price', 'image_url', 'image_path', 'unit_id', 'size_id', 'category_id', 'brand_id', 'product_type_id', 'supplier_id')
            ->with(['unit:id,name', 'size:id,name', 'category:id,name', 'brand:id,name', 'productType:id,name', 'insights'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            })
            ->paginate(10);

        return response()->json($productsForAutocomplete);
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
            'invoices.items:id,purchase_id,purchase_invoice_id'
        ]);
        $purchase->loadSum('invoices', 'total_amount');
        $invoice = $purchase->invoices->first() ?? new PurchaseInvoice();
        return Inertia::render('Purchases/PurchaseDetail', [
            'purchase' => $purchase,
            'invoice' => $invoice,
            'paymentStatuses' => PurchaseInvoice::PAYMENT_STATUSES,

            // Flag untuk frontend (FE) agar tahu apakah tombol aksi harus ditampilkan
            'isCheckingMode' => in_array($purchase->status, [
                Purchase::STATUS_RECEIVED,
                Purchase::STATUS_CHECKING
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
            return redirect()->back()->with('error', 'Gagal menyimpan data nota: ' . $e->getMessage());
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
            return redirect()->back()->with('error',  $e->getMessage());
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
            return redirect()->back()->with('error', 'Gagal melepas item: ' . $e->getMessage());
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
            return redirect()->back()->with('error', 'Gagal menyimpan koreksi: ' . $e->getMessage());
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
            return redirect()->back()->with('error', 'Gagal memvalidasi invoice: ' . $e->getMessage());
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
            'notes' => 'nullable'
        ]);

        try {
            $this->purchaseService->finalizeTransaction($purchase, $validated);

            return redirect()->route('purchases.show', $purchase)
                ->with('success', 'Transaksi Selesai! Stok telah bertambah dan HPP diperbarui.');
        } catch (\Exception $e) {
            // Tangkap error validasi logika dari service
            return redirect()->back()->with('error', 'Gagal menyelesaikan transaksi: ' . $e->getMessage());
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
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
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
        if (!in_array($purchase->status, [Purchase::STATUS_DRAFT, Purchase::STATUS_ORDERED])) {
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
            'items.product.size'  // Relasi ke Size produk
        ])->findOrFail($id);

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        // 2. Data Toko (Hardcode dulu atau ambil dari Setting jika ada)
        $storeProfile = [
            'name' => $settings['shop_name'] ?? 'INVENTRA CORP',
            'address' => $settings['shop_address'] ?? 'Alamat Belum Diisi',
            'phone' => $settings['shop_phone'] ?? '-',
            'email' => ''
        ];

        // 3. Load View PDF
        $pdf = Pdf::loadView('exports.purchase_order', [
            'po' => $purchase,
            'store' => $storeProfile
        ]);

        $safeRef = str_replace(['/', '\\'], '-', $purchase->reference_no);
        $filename = $safeRef . '.pdf';

        // 4. Set Kertas & Stream (Tampil di browser)
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream($filename);
    }
}
