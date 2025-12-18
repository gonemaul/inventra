<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Str;
use App\Models\PurchaseItem;
use App\Models\SmartInsight;
use App\Models\StockMovement;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\PurchaseInvoice; // Diperlukan jika ada logika invoice di service

class PurchaseService
{
    protected $stockService;
    protected $telegramService;
    public function __construct(StockService $stockService, TelegramService $telegramService)
    {
        $this->stockService = $stockService;
        $this->telegramService = $telegramService;
    }
    /**
     * Mengambil data transaksi pembelian untuk halaman index (Index.vue).
     */
    public function get(array $params)
    {
        $query = Purchase::query()
            ->with(['supplier', 'user', 'invoices']);

        // 1. Filter Trashed (Sampah)
        $query->when($params['trashed'] ?? false, function ($q) {
            $q->onlyTrashed();
        });

        // 2. Filter Search (PENTING: Dibungkus closure agar tidak merusak filter lain)
        $query->when($params['search'] ?? null, function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', "%{$search}%");
                    });
            });
        });
        // 3. Filter Status & Supplier (Simple Where)
        $query->when($params['status'] ?? null, fn($q, $status) => $q->where('status', $status));
        $query->when($params['supplier_id'] ?? null, fn($q, $id) => $q->where('supplier_id', $id));
        $query->when($params['user_id'] ?? null, fn($q, $id) => $q->where('user_id', $id));

        // 4. Filter Tanggal (Date Range)
        $query->when($params['min_date'] ?? null, fn($q, $date) => $q->where('transaction_date', '>=', $date))
            ->when($params['max_date'] ?? null, fn($q, $date) => $q->where('transaction_date', '<=', $date));

        // 5. Filter Rentang Total (Having Aggregates)
        $query->when($params['min_total'] ?? null, fn($q, $total) => $q->where('grand_total', '>=', $total))
            ->when($params['max_total'] ?? null, fn($q, $total) => $q->where('grand_total', '<=', $total));


        // --- SORTING & PAGINASI ---
        $sortBy = $params['sort'] ?? 'transaction_date';
        $sortDirection = $params['order'] ?? 'desc';
        $perPage = $params['per_page'] ?? 10;

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Membuat Transaksi Induk (Purchase) DAN Item-nya (PurchaseItem).
     *
     * @param array $data (Data Header + array 'items' nested)
     * @return Purchase
     * @throws ValidationException
     */
    public function create(array $data)
    {
        // 1. Validasi Input
        $validator = Validator::make($data, [
            'supplier_id' => 'nullable|exists:suppliers,id',
            'user_id' => 'required|exists:users,id',
            'transaction_date' => 'required|date',
            'status' => ['required', Rule::in(Purchase::STATUSES)],
            'notes' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();
        $purchaseItemsData = $validatedData['items'];
        $grandTotal = 0;

        // 2. DB TRANSACTION (Menjamin Atomicity)
        return DB::transaction(function () use ($validatedData, $purchaseItemsData, &$grandTotal) {
            // 3. Buat Transaksi Induk (Purchase)
            $purchase = Purchase::create([
                'supplier_id' => $validatedData['supplier_id'] ?? null,
                'user_id' => $validatedData['user_id'],
                'reference_no' => $this->generateReferenceNo($validatedData['supplier_id']),
                'transaction_date' => $validatedData['transaction_date'],
                'status' => Purchase::STATUS_DRAFT,
                'grand_total' => 0, // Akan dihitung ulang setelah item dibuat
            ]);
            // 4. Loop dan buat PurchaseItem
            foreach ($purchaseItemsData as $itemData) {
                $subtotal = $itemData['quantity'] * $itemData['purchase_price'];
                $grandTotal += $subtotal;
                $this->createItems($purchase, $itemData, $subtotal);
            }

            $purchase->update(['total_item_price' => $grandTotal, 'grand_total' => $grandTotal]);
            return $purchase;
        });
    }

    /**
     * MAIN UPDATE FUNCTION
     * Ini adalah gerbang utama yang menentukan logika mana yang dipakai.
     */
    public function updatePurchase(Purchase $purchase, array $data)
    {
        // CASE 1: Status DRAFT (Bebas Edit)
        if ($purchase->status === Purchase::STATUS_DRAFT) {
            return $this->handleFullUpdate($purchase, $data);
        }

        // CASE 2: Status ORDERED (Hanya Boleh Nambah)
        if ($purchase->status === Purchase::STATUS_ORDERED) {
            return $this->handleAddOnlyUpdate($purchase, $data);
        }

        // CASE 3: Status LAIN (Received/Paid) -> Tolak
        throw ValidationException::withMessages([
            'status' => 'Transaksi yang sudah Diterima/Lunas tidak dapat diedit.'
        ]);
    }

    /**
     * LOGIKA 1: FULL EDIT (Khusus Draft)
     * Bisa tambah, ubah qty/harga, dan hapus item.
     */
    private function handleFullUpdate(Purchase $purchase, array $data)
    {
        return DB::transaction(function () use ($purchase, $data) {
            if ($purchase->supplier_id != $data['supplier_id']) {
                throw ValidationException::withMessages(['supplier_id' => 'Tidak bisa ganti supplier untuk pesanan yang sudah dikirim.']);
            }
            // 1. Update Header
            $purchase->update([
                'transaction_date' => $data['transaction_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            // 2. Persiapan Data
            $inputItems = collect($data['items']);
            $inputIds = $inputItems->pluck('id')->filter()->toArray();
            $existingItems = $purchase->items;

            // 3. Handle DELETE (Item yang ada di DB tapi tidak ada di Input)
            foreach ($existingItems as $existingItem) {
                if (!in_array($existingItem->id, $inputIds)) {
                    $existingItem->delete();
                }
            }

            // 4. Handle UPSERT (Update Existing & Insert New)
            $grandTotal = 0;

            foreach ($inputItems as $item) {
                $subtotal = $item['quantity'] * $item['purchase_price'];
                $grandTotal += $subtotal;

                if (isset($item['id']) && $item['id']) {
                    // Update Item Lama
                    $existingItem = $existingItems->firstWhere('id', $item['id']);
                    $existingItem->update([
                        'quantity' => $item['quantity'],
                        'purchase_price' => $item['purchase_price'],
                        'subtotal' => $subtotal
                    ]);
                } else {
                    $this->createItems($purchase, $item, $subtotal);
                }
            }
            $purchase->update(['total_item_price' => $grandTotal, 'grand_total' => $grandTotal]);
            return $purchase;
        });
    }

    /**
     * LOGIKA 2: ADD ONLY (Khusus Ordered)
     * Validasi ketat: Tidak boleh hapus, tidak boleh ubah item lama.
     */
    private function handleAddOnlyUpdate(Purchase $purchase, array $data)
    {
        return DB::transaction(function () use ($purchase, $data) {
            $existingItems = $purchase->items;
            $inputItems = collect($data['items']);

            // --- VALIDASI KEAMANAN (SECURITY CHECK) ---
            // 1. Cek Header: Biasanya supplier tidak boleh berubah kalau sudah dipesan
            if ($purchase->supplier_id != $data['supplier_id']) {
                throw ValidationException::withMessages(['supplier_id' => 'Tidak bisa ganti supplier untuk pesanan yang sudah dikirim.']);
            }

            // 2. Cek Deleted Items: Pastikan semua item lama MASIH ADA di input
            $inputIds = $inputItems->pluck('id')->filter()->toArray();
            foreach ($existingItems as $existing) {
                if (!in_array($existing->id, $inputIds)) {
                    throw ValidationException::withMessages(['items' => "Item '{$existing->product->name}' tidak boleh dihapus karena sudah dipesan."]);
                }
            }

            // 3. Cek Modified Items: Pastikan item lama NILAINYA TIDAK BERUBAH
            foreach ($inputItems as $item) {
                if (isset($item['id']) && $item['id']) {
                    $existing = $existingItems->firstWhere('id', $item['id']);

                    // Jika user mencoba mengutak-atik qty atau harga item lama
                    if ($existing->quantity != $item['quantity'] || $existing->purchase_price != $item['purchase_price']) {
                        throw ValidationException::withMessages(['items' => "Item lama tidak boleh diedit (Qty/Harga terkunci). Hanya boleh tambah item baru."]);
                    }
                }
            }

            // --- EKSEKUSI PENAMBAHAN ---
            $additionalTotal = 0;
            foreach ($inputItems as $item) {
                // HANYA PROSES YANG ID-NYA NULL (ITEM BARU)
                if (!isset($item['id']) || is_null($item['id'])) {
                    $subtotal = $item['quantity'] * $item['purchase_price'];
                    $additionalTotal += $subtotal;
                    $this->createItems($purchase, $item, $subtotal);
                }
            }
            // Update Total Invoice (Total Lama + Total Tambahan)
            $grandTotal = $purchase->grand_total + $additionalTotal;
            $purchase->update(['total_item_price' => $grandTotal, 'grand_total' => $grandTotal]);

            return $purchase;
        });
    }

    private function createItems($purchase, $item, $subtotal)
    {
        return DB::transaction(function () use ($purchase, $item, $subtotal) {
            $product = Product::with(['unit:id,name', 'size:id,name', 'brand:id,name', 'category:id,name', 'productType:id,name'])->find($item['product_id']);
            $snapshot = [
                'name' => $product->name,
                'code' => $product->code,
                'category' => $product->category->name ?? null,
                'productType' => $product->productType->name ?? null,
                'unit' => $product->unit->name ?? null,
                'size' => $product->size->name ?? null,
                'brand' => $product->brand->name ?? null,
                'purchase_price' => $product->purchase_price,
                'selling_price' => $product->selling_price,
                'stock' => $product->stock,
                'quantity' => $item['quantity'],
                'image_url' => $product->image_url
            ];
            $purchase->items()->create([
                'product_id' => $item['product_id'],
                'product_snapshot' => $snapshot,
                'quantity' => $item['quantity'],
                'purchase_price' => $item['purchase_price'],
                'subtotal' => $subtotal,
                'item_status' => PurchaseItem::STATUS_PENDING,
            ]);
        });
    }


    /**
     * Update Status Transaksi Operasional (Digunakan di Index/Aksi Cepat).
     */
    public function updateStatus(int $id, string $newStatus)
    {
        $purchase = Purchase::findOrFail($id);
        $oldStatus = $purchase->status;

        // 1. Validasi Transisi Status (Guard)
        if (!in_array($newStatus, Purchase::STATUSES)) {
            throw new \InvalidArgumentException('Status tidak valid.');
        }

        // 2. GUARD 2: Peta Jalan Transisi yang Diizinkan (Kunci Alur)
        $allowedTransitions = [
            // [Awal] -> [Tujuan yang Diizinkan, Termasuk CANCEL]
            Purchase::STATUS_DRAFT => [Purchase::STATUS_ORDERED, Purchase::STATUS_CANCELLED],
            Purchase::STATUS_ORDERED => [Purchase::STATUS_SHIPPED, Purchase::STATUS_CANCELLED],
            Purchase::STATUS_SHIPPED => [Purchase::STATUS_RECEIVED, Purchase::STATUS_CANCELLED],
            Purchase::STATUS_RECEIVED => [Purchase::STATUS_CHECKING, Purchase::STATUS_SHIPPED, Purchase::STATUS_CANCELLED],
            Purchase::STATUS_CHECKING => [Purchase::STATUS_COMPLETED, Purchase::STATUS_CANCELLED],

            // Status FINAL (Tidak boleh berubah lagi)
            Purchase::STATUS_COMPLETED => [],
            Purchase::STATUS_CANCELLED => [],
        ];
        if (!isset($allowedTransitions[$oldStatus]) || !in_array($newStatus, $allowedTransitions[$oldStatus])) {
            if ($oldStatus === $newStatus) return $purchase; // Lewati jika status sama
            throw new \Exception("Transisi status dari '{$oldStatus}' ke '{$newStatus}' tidak diizinkan.");
        }

        // 3. LOGIC KHUSUS (Timestamping & Side Effects)

        // [TIMESTAMPING] Catat tanggal barang tiba
        if ($newStatus === Purchase::STATUS_RECEIVED && is_null($purchase->received_at)) {
            $purchase->received_at = now();
        }

        // [FINAL ACTION] Logika Penambahan Stok (Completed)
        if ($newStatus === Purchase::STATUS_COMPLETED) {
            // Kita akan buat method service terpisah nanti:
            // $this->updateProductStockAndHpp($purchase);

            // Untuk saat ini, kita tambahkan note sebagai placeholder
            $purchase->notes = ($purchase->notes ?? '') . "\n[SYSTEM] Transaction completed at " . now();
        }

        // 4. Update Status dan Simpan
        $purchase->status = $newStatus;
        $purchase->save();

        return $purchase;
    }

    public function getRecomendations($supplierId)
    {
        // --- STRATEGI BARU: GUNAKAN DATA DSS ---

        // 1. Ambil ID Produk yang punya Insight 'Restock'
        $insightProductIds = SmartInsight::where('type', SmartInsight::TYPE_RESTOCK)
            // ->where('severity', 'critical') // Ambil yang kritis dulu
            ->pluck('product_id')
            ->toArray();

        // 2. Query Utama: Gabungkan Produk Kritis DSS + Produk di Bawah Min Stock
        $query = Product::query()
            ->select('id', 'name', 'code', 'stock', 'min_stock', 'purchase_price', 'image_url', 'unit_id', 'size_id', 'category_id', 'brand_id', 'supplier_id')
            ->with(['unit:id,name', 'size:id,name', 'category:id,name', 'brand:id,name', 'insights']) // Eager load insights
            ->where('status', 'active');

        // Filter Supplier
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        // Logic Gabungan: (Ada di Insight Restock) ATAU (Stok <= Min Stock)
        $query->where(function ($q) use ($insightProductIds) {
            $q->whereIn('id', $insightProductIds);
        });

        // 3. Eksekusi & Sorting
        // Kita sort manual nanti di collection agar yang ada Insight-nya di paling atas
        $products = $query->limit(50)->get();

        // 4. Transformasi Data + Penambahan Logic Rekomendasi Qty
        $formattedRecommendations = $products->map(function ($item) {

            // Cek apakah produk ini punya insight restock?
            $restockInsight = $item->insights->where('type', SmartInsight::TYPE_RESTOCK)->first();

            return [
                'product_id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'current_stock' => $item->stock,
                'min_stock' => $item->min_stock,
                'purchase_price' => $item->purchase_price,

                // Hasil Hitungan Cerdas
                'quantity' => (int) $restockInsight->payload['suggested_qty'],
                'reason' => $restockInsight->payload['restock_reason'], // Info tambahan untuk ditampilkan di modal (misal badge)
                'is_critical' => (bool) $restockInsight, // Flag untuk highlight warna merah

                // Snapshot Data
                'unit' => $item->unit->name ?? '-',
                'size' => $item->size->name ?? '-',
                'category' => $item->category->name ?? '-',
                'brand' => $item->brand->name ?? '-',
                'image_path' => $item->image_path
            ];
        });

        // Sortir: Yang ada Insight Critical taruh paling atas
        return $formattedRecommendations->sortByDesc('is_critical')->values();
    }

    /**
     * Generate No Transaksi: PO/YYMM/S-ID/XXX
     * Contoh: PO/2411/S-013/001
     */
    private function generateReferenceNo(int $supplierId): string
    {
        // 1. Ambil Variabel Waktu (Format 2 digit tahun + 2 digit bulan: 2411)
        $dateCode = date('ym');

        // 2. Format Supplier ID (Padding 3 digit: ID 13 jadi '013')
        $supplierCode = 'S-' . str_pad((string)$supplierId, 3, '0', STR_PAD_LEFT);

        // 3. Susun Prefix Dasar: "PO/2411/S-013/"
        // Kita mencari urutan KHUSUS untuk Supplier ini di Bulan ini.
        $prefix = "PO/{$dateCode}/{$supplierCode}/";

        // 4. Cari Transaksi Terakhir (Termasuk yang dihapus/soft delete)
        $lastRecord = Purchase::query()
            ->select('reference_no')
            ->where('reference_no', 'like', $prefix . '%')
            ->when(method_exists(Purchase::class, 'bootSoftDeletes'), function ($q) {
                $q->withTrashed(); // PENTING: Cek tong sampah agar urutan tidak bentrok
            })
            ->orderByDesc('id') // Ambil yang paling baru dibuat
            ->first();

        // 5. Tentukan Nomor Urut
        $sequence = 1;

        if ($lastRecord) {
            // Format: PO/2411/S-013/005
            // Pecah string berdasarkan garis miring '/'
            $parts = explode('/', $lastRecord->reference_no);
            $lastSeq = end($parts); // Ambil bagian paling belakang (005)

            if (is_numeric($lastSeq)) {
                $sequence = (int)$lastSeq + 1;
            }
        }

        // 6. Gabungkan Hasil Akhir
        // Hasil: PO/2411/S-013/001 (Padding urutan 3 digit)
        return $prefix . str_pad((string)$sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Menyelesaikan transaksi pembelian, update stok, dan hitung HPP Final.
     * @param Purchase $purchase
     * @param array $extraCosts ['shipping_cost' => 0, 'other_costs' => 0]
     */
    public function finalizeTransaction(Purchase $purchase, array $extraCosts)
    {
        // --- LAYER 1: VALIDASI DATA (CRITICAL) ---

        // 1. Cek Status
        if ($purchase->status !== Purchase::STATUS_CHECKING) {
            throw new \Exception("Transaksi hanya bisa diselesaikan dari status 'Checking'.");
        }

        // 2. Cek Keberadaan Invoice
        if ($purchase->invoices()->count() === 0) {
            throw new \Exception("Transaksi harus memiliki minimal 1 nota (invoice) yang terupload.");
        }

        // 3. Cek Linkage (Apakah ada item yang menggantung?)
        // Semua item yang punya Qty > 0 HARUS sudah tertaut ke invoice.
        // Item dengan Qty 0 (Barang Kosong) boleh tidak tertaut.
        $unlinkedItemsCount = $purchase->items()
            ->where('quantity', '>', 0) // Hanya item yang ada fisiknya
            ->whereNull('purchase_invoice_id')
            ->count();

        if ($unlinkedItemsCount > 0) {
            throw new \Exception("Masih ada {$unlinkedItemsCount} item fisik yang belum ditautkan ke nota. Harap tautkan atau nol-kan quantity jika barang tidak ada.");
        }

        // 4. Cek Total Qty
        $totalQtyReceived = $purchase->items()->sum('quantity');
        if ($totalQtyReceived <= 0) {
            throw new \Exception("Tidak ada barang yang diterima (Total Qty 0). Transaksi tidak dapat diproses sebagai pembelian stok.");
        }

        // --- LAYER 2: KALKULASI BIAYA ---

        $shippingCost = $extraCosts['shipping_cost'] ?? 0;
        $otherCosts = $extraCosts['other_costs'] ?? 0;
        $totalExtraCost = $shippingCost + $otherCosts;

        // Hitung alokasi biaya per unit (Uniform Allocation)
        // Rumus: (Total Ongkir + Lainnya) / Total Qty Barang Diterima
        $costPerUnitAllocation = $totalExtraCost / $totalQtyReceived;


        // --- LAYER 3: EKSEKUSI DATABASE (TRANSACTION) ---

        return DB::transaction(function () use ($extraCosts, $purchase, $shippingCost, $otherCosts, $costPerUnitAllocation) {

            // A. Update Header Transaksi
            $purchase->update([
                'status' => Purchase::STATUS_COMPLETED,
                'shipping_cost' => $shippingCost,
                'other_costs' => $otherCosts,
                'notes' => $extraCosts['notes'],
                // Opsional: set tanggal selesai
                // 'completed_at' => now(),
            ]);

            $marginAlerts = [];
            // B. Proses Setiap Item
            foreach ($purchase->items as $item) {
                // Skip barang kosong
                if ($item->quantity <= 0) continue;

                $product = Product::find($item->product_id);
                if (!$product) continue;

                // 1. Hitung HPP Final Item Ini
                // HPP = Harga Beli di Nota + Alokasi Biaya Tambahan
                $finalHpp = $item->purchase_price + $costPerUnitAllocation;
                $item->subtotal = $item->quantity * $finalHpp;
                $item->save();

                $newCost = $finalHpp; // Harga Beli Baru
                $currentSelling = $product->selling_price;

                // Panggil fungsi sakti tadi
                // Kita kirim array data yang ingin diubah
                $product->updateWithSnapshot([
                    'purchase_price' => $finalHpp,
                    // 'stock'          => $product->stock + $item->quantity, // Tambah stok
                    // 'supplier_id' => $purchase->supplier_id // (Opsional: update supplier default produk)
                ], 'purchase'); // Reason: 'purchase'
                $this->stockService->record(
                    productId: $item->product_id,
                    qty: $item->quantity, // Positif = Masuk
                    type: StockMovement::TYPE_PURCHASE,
                    ref: $purchase->reference_no, // Referensi PO / Invoice Supplier
                    desc: 'Penerimaan Barang Pembelian'
                );

                // hitung margin baru
                $marginRp = $currentSelling - $newCost;
                $marginPercent = $currentSelling > 0 ? ($marginRp / $currentSelling) * 100 : 0;

                // Jika Margin < 10% (Bahaya) atau Negatif (Rugi)
                if ($marginPercent < 10) {
                    $marginAlerts[] = "⚠️ <b>{$product->name}</b>\nBeli: " . number_format($newCost) . "\nJual: " . number_format($currentSelling) . "\nMargin: <b>" . round($marginPercent, 1) . "%</b> (Tipis!)";
                }
            }
            if (!empty($marginAlerts)) {
                $msg = "🚨 <b>MARGIN GUARDIAN ALERT!</b>\n";
                $msg .= "Ada kenaikan harga modal di PO #{$purchase->reference_no}, segera update harga jual!\n\n";
                $msg .= implode("\n\n", $marginAlerts);

                $this->telegramService->send($msg);
            }

            return true;
        });
    }
}
