<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\PurchaseInvoice; // Diperlukan jika ada logika invoice di service
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class PurchaseService
{
    /**
     * Mengambil data transaksi pembelian untuk halaman index (Index.vue).
     */
    public function get(array $params)
    {
        $query = Purchase::query()
            ->with(['supplier', 'user', 'invoices'])
            ->withSum('items', 'subtotal');

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
        $query->when($params['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($params['supplier_id'] ?? null, fn($q, $id) => $q->where('supplier_id', $id));

        // 4. Filter Tanggal (Date Range)
        $query->when($params['min_date'] ?? null, fn($q, $date) => $q->where('transaction_date', '>=', $date))
            ->when($params['max_date'] ?? null, fn($q, $date) => $q->where('transaction_date', '<=', $date));

        // 5. Filter Rentang Total (Having Aggregates)
        // Kita cek is_numeric di dalam closure untuk keamanan
        $query->when($params['min_total'] ?? null, function ($q, $min) {
            $cleanMin = trim($min);
            if (is_numeric($min)) $q->whereRaw('(SELECT SUM(subtotal) FROM purchase_items WHERE purchase_items.purchase_id = purchases.id) >= ?', (float)$cleanMin);
        });

        $query->when($params['max_total'] ?? null, function ($q, $max) {
            $cleanMax = trim($max);
            if (is_numeric($max)) $q->whereRaw('(SELECT SUM(subtotal) FROM purchase_items WHERE purchase_items.purchase_id = purchases.id) <= ?', (float)$cleanMax);
        });

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
                'notes' => $validatedData['notes'] ?? null,
                'shipping_cost' => $validatedData['shipping_cost'] ?? 0,
                'other_costs' => $validatedData['other_costs'] ?? 0,
            ]);

            // 4. Loop dan buat PurchaseItem
            foreach ($purchaseItemsData as $itemData) {
                // Ambil data produk master untuk snapshot
                $product = Product::with(['unit:id,name', 'size:id,name', 'brand:id,name', 'category:id,name', 'productType:id,name'])->find($itemData['product_id']);

                // Cek jika product master data valid sebelum membuat snapshot
                if (!$product) continue;

                $subtotal = $itemData['quantity'] * $itemData['purchase_price'];
                $grandTotal += $subtotal;

                // Buat SNAPSHOT data produk (melindungi histori)
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
                    'inventory_type' => $product->inventory_type,
                    'quantity' => $itemData['quantity']
                ];

                // Simpan Item
                $purchase->items()->create([
                    'product_id' => $itemData['product_id'],
                    'product_snapshot' => $snapshot,
                    'quantity' => $itemData['quantity'],
                    'purchase_price' => $itemData['purchase_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            // 5. [OPSIONAL] Update grand_total di sini jika diperlukan,
            //    tapi karena kita hapus kolomnya, kita lewati.

            return $purchase;
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

    public function getRecomendations($request)
    {
        $supplierId = $request->input('supplier_id');
        $buffer = 5; // Buffer Qty Tambahan untuk berjaga-jaga
        // 2. Bangun Query Cerdas
        $recommendations = Product::query()
            ->select('id', 'name', 'code', 'stock', 'min_stock', 'purchase_price', 'image_path', 'unit_id', 'size_id', 'category_id', 'brand_id')
            ->with(['unit:id,name', 'size:id,name', 'category:id,name', 'brand:id,name'])
            ->where('status', 'active')

            // Filter Supplier (Jika supplier dipilih)
            ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))

            // Logika Kritis: Hanya ambil produk yang stoknya di bawah batas aman
            ->whereColumn('stock', '<=', 'min_stock')

            // Urutkan dari yang paling kritis (Stok Paling Rendah)
            ->orderByRaw('min_stock - stock DESC')
            ->limit(25) // Batasi hasilnya
            ->get();

        // 3. Transformasi Data untuk Frontend
        $formattedRecommendations = $recommendations->map(function ($item) use ($buffer) {
            $neededQty = $item->min_stock - $item->stock;

            return [
                'product_id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'current_stock' => $item->stock,
                'min_stock' => $item->min_stock,
                'purchase_price' => $item->purchase_price,

                // Perhitungan Saran Kuantitas: (Kekurangan Stok + Buffer)
                'quantity' => (int)max(1, $neededQty + $buffer),

                // Data Snapshot Sederhana
                'unit' => $item->unit->name ?? '-',
                'size' => $item->size->name ?? '-',
                'category' => $item->category->name ?? '-',
                'brand' => $item->brand->name ?? '-',
            ];
        });

        return $formattedRecommendations;
    }

    /**
     * Helper untuk membuat Nomor Referensi unik (TRX-YYYYMM-XXXX).
     */
    // private function generateReferenceNo(): string
    // {
    //     $prefix = 'TRX-' . date('Ym');
    //     // $supplierCode = str_pad((string)$supplierId, 3, '0', STR_PAD_LEFT);
    //     $lastPurchase = Purchase::where('reference_no', 'like', $prefix . '%')
    //         ->when(method_exists(Purchase::class, 'bootSoftDeletes'), function ($q) {
    //             $q->withTrashed(); // Sertakan data yang sudah dihapus agar urutan terus maju
    //         })
    //         ->orderByDesc('id')
    //         ->first();

    //     $number = 1;
    //     if ($lastPurchase) {
    //         $number = (int)substr($lastPurchase->reference_no, -4) + 1;
    //     }

    //     return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    // }
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

            // B. Proses Setiap Item
            foreach ($purchase->items as $item) {
                // Skip barang kosong
                if ($item->quantity <= 0) continue;

                $product = Product::find($item->product_id);
                if (!$product) continue;

                // 1. Hitung HPP Final Item Ini
                // HPP = Harga Beli di Nota + Alokasi Biaya Tambahan
                $finalHpp = $item->purchase_price + $costPerUnitAllocation;

                // 2. Update Catatan Item (Simpan HPP Final untuk histori)
                // Asumsi: Anda punya kolom 'final_hpp' di purchase_items, atau overwrite purchase_price
                // Disarankan overwrite purchase_price agar konsisten, atau simpan di kolom baru.
                // Disini kita simpan kalkulasi subtotal final.
                $item->subtotal = $item->quantity * $finalHpp;
                $item->save();

                // Panggil fungsi sakti tadi
                // Kita kirim array data yang ingin diubah
                $product->updateWithSnapshot([
                    'purchase_price' => $finalHpp,
                    'stock'          => $product->stock + $item->quantity, // Tambah stok
                    // 'supplier_id' => $purchase->supplier_id // (Opsional: update supplier default produk)
                ], 'purchase'); // Reason: 'purchase'
            }

            return true;
        });
    }
}
