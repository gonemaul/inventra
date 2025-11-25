<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    /**
     * Menyimpan dan Mengunggah Nota Baru.
     */
    public function store(array $data, Purchase $purchase)
    {
        $validator = Validator::make($data, [
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'total_amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:' . implode(',', PurchaseInvoice::PAYMENT_STATUSES),
            'invoice_image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();

        return DB::transaction(function () use ($validatedData, $purchase) {
            // 1. Upload File
            $path = $validatedData['invoice_image']->store('purchases/invoices', 'public');

            // 2. Buat Record Invoice
            $invoice = $purchase->invoices()->create([
                'invoice_number' => $validatedData['invoice_number'],
                'invoice_date' => $validatedData['invoice_date'],
                'due_date' => $validatedData['due_date'],
                'total_amount' => $validatedData['total_amount'],
                'payment_status' => $validatedData['payment_status'],
                'invoice_image' => $path, // Simpan path
                'amount_paid' => ($validatedData['payment_status'] === 'paid') ? $validatedData['total_amount'] : 0,
            ]);

            return $invoice;
        });
    }

    public function update(array $data, PurchaseInvoice $invoice)
    {
        // 1. GUARD: Cek status parent (Tidak boleh diubah jika sudah Selesai)
        if (in_array($invoice->purchase->status, [Purchase::STATUS_COMPLETED, Purchase::STATUS_CANCELLED])) {
            throw new \Exception('Nota tidak dapat diubah (diedit) karena transaksi sudah selesai atau dibatalkan.');
        }

        // 2. Validasi (mirip store, tapi image tidak required)
        $validator = Validator::make($data, [
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'total_amount' => 'required|numeric|min:1',
            'payment_status' => 'required|in:' . implode(',', PurchaseInvoice::PAYMENT_STATUSES),
            'invoice_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();

        return DB::transaction(function () use ($validatedData, $invoice) {
            $path = $invoice->invoice_image;

            // 3. Handle File Update
            if (isset($validatedData['invoice_image'])) {
                // Hapus file lama jika ada
                if ($path) {
                    Storage::disk('public')->delete($path);
                }
                // Upload file baru
                $path = $validatedData['invoice_image']->store('purchases/invoices', 'public');
            }

            // 4. Update Record
            $invoice->update([
                'invoice_number' => $validatedData['invoice_number'],
                'invoice_date' => $validatedData['invoice_date'],
                'due_date' => $validatedData['due_date'],
                'total_amount' => $validatedData['total_amount'],
                'payment_status' => $validatedData['payment_status'],
                'invoice_image' => $path,
                'amount_paid' => ($validatedData['payment_status'] === PurchaseInvoice::PAYMENT_STATUS_PAID) ? $validatedData['total_amount'] : $invoice->amount_paid,
            ]);

            return $invoice;
        });
    }
    /**
     * Menghapus Nota Keuangan dari transaksi.
     */
    public function destroy(PurchaseInvoice $invoice)
    {
        // 1. GUARD: Cek status parent
        if (in_array($invoice->purchase->status, [Purchase::STATUS_COMPLETED, Purchase::STATUS_CANCELLED])) {
            throw new \Exception('Nota tidak dapat dihapus karena transaksi sudah selesai atau dibatalkan.');
        }
        if ($invoice->items()->count() > 0) {
            throw new \Exception('Nota tidak dapat dihapus karena masih memiliki item produk yang tertaut. Harap lepaskan (unlink) item terlebih dahulu.');
        }

        return DB::transaction(function () use ($invoice) {
            // 2. Hapus file fisik
            if ($invoice->invoice_image) {
                Storage::disk('public')->delete($invoice->invoice_image);
            }

            // 3. Hapus Record
            $invoice->delete();

            // 4. Cek dan Update Status Parent (Jika ini nota terakhir dan statusnya CHECKING, harus mundur)
            if ($invoice->purchase->invoices()->count() === 0 && $invoice->purchase->status === Purchase::STATUS_CHECKING) {
                // Jika tidak ada nota lagi, kembalikan status ke RECEIVED
                $invoice->purchase->update(['status' => Purchase::STATUS_RECEIVED]);
            }

            return true;
        });
    }

    // public function linkItemsAndOverwritePrices(PurchaseInvoice $invoice, array $itemIds)
    // {
    //     // 1. Guard: Pastikan Nota sudah ada nominalnya
    //     if ($invoice->total_amount <= 0) {
    //         throw new \Exception('Nominal total nota tidak valid untuk perhitungan harga.');
    //     }

    //     // 2. Ambil semua item yang akan ditautkan
    //     $itemsToLink = $invoice->purchase->items()
    //         ->whereIn('id', $itemIds)
    //         // Hanya izinkan item yang belum memiliki invoice (item tidak boleh ditautkan 2x)
    //         ->whereNull('purchase_invoice_id')
    //         ->get();

    //     // Kuantitas total dari item yang akan ditautkan
    //     $totalQuantity = $itemsToLink->sum('quantity');

    //     if ($totalQuantity === 0) {
    //         throw new \Exception('Item yang dipilih tidak memiliki kuantitas atau sudah ditautkan ke nota lain.');
    //     }

    //     // 3. Logika Overwrite Harga (Landed Cost Sederhana)
    //     // [KRITIS] New HPP = Total Nominal Nota / Total Qty Item yang ditautkan
    //     $newPricePerUnit = $invoice->total_amount / $totalQuantity;
    //     $newPricePerUnit = round($newPricePerUnit, 4); // Bulatkan 4 desimal untuk presisi

    //     // 4. DB TRANSACTION: Tautkan item dan update harganya
    //     return DB::transaction(function () use ($invoice, $itemsToLink, $newPricePerUnit) {

    //         foreach ($itemsToLink as $item) {
    //             // Tautkan item ke Invoice
    //             $item->purchase_invoice_id = $invoice->id;

    //             // Update HPP dan Subtotal (Overwrite)
    //             $item->purchase_price = $newPricePerUnit;
    //             $item->subtotal = $item->quantity * $newPricePerUnit;

    //             $item->save();
    //         }
    //         return true;
    //     });
    // }
    public function recalculateTotalAmount(PurchaseInvoice $invoice)
    {
        // 1. [PERBAIKAN SINTAKS] Hitung total subtotal dari relasi items()
        // Pastikan item() adalah relasi HasMany ke PurchaseItem
        $newTotal = $invoice->items()->sum('subtotal');

        // 2. Gunakan DB Transaction untuk memastikan atomicity
        DB::transaction(function () use ($invoice, $newTotal) {

            // 3. Perbarui total_amount di Nota
            $invoice->update(['total_amount' => $newTotal]);

            // 4. [PENTING] Update amount_paid jika statusnya sudah LUNAS (Paid)
            if ($invoice->payment_status === 'paid') {
                $invoice->update(['amount_paid' => $newTotal]);
            }
        });
    }
    /**
     * Memproses array product_id untuk menautkan item PO yang ada atau membuat item baru.
     * Digunakan untuk integrasi search/autocomplete.
     */
    public function smartLinkProductsByProductId(PurchaseInvoice $invoice, array $payload)
    {
        $type = $payload['type'] ?? null;
        // Guard: Cek apakah transaksi masih dalam fase yang bisa diubah
        if (!in_array($invoice->purchase->status, [Purchase::STATUS_RECEIVED, Purchase::STATUS_CHECKING])) {
            throw new \Exception('Perubahan data hanya diizinkan saat status Checking atau Received.');
        }

        return DB::transaction(function () use ($invoice, $payload, $type) {
            $createdItemsCount = 0;
            $linkedItemsCount = 0;

            if ($type === 'link') {
                $ids = $payload['ids'] ?? [];
                if (empty($ids)) {
                    throw new \InvalidArgumentException("Harap pilih minimal satu item dari daftar untuk ditautkan.");
                }
                $unlinkedPoItems = PurchaseItem::whereIn('id', $ids)
                    ->whereNull('purchase_invoice_id')
                    ->get();

                foreach ($unlinkedPoItems as $item) {
                    if ($item->purchase_id !== $invoice->purchase_id) {
                        throw new \Exception("Keamanan: Item tidak valid untuk transaksi ini.");
                    }
                    if ($item->purchase_invoice_id && $item->purchase_invoice_id !== $invoice->id) {
                        throw new \Exception("Item '{$item->product_snapshot['name']}' sudah tertaut di nota lain. Unlink dulu jika ingin memindahkan.");
                    }
                    $item->purchase_invoice_id = $invoice->id;
                    $item->save();
                    $linkedItemsCount++;
                }
            } else if ($type === 'create') {
                $productId = $payload['product_id'] ?? null;
                if (!$productId) {
                    throw new \InvalidArgumentException("Produk harus dipilih untuk ditambahkan.");
                }
                $isDuplicate = PurchaseItem::where('purchase_id', $invoice->purchase_id)
                    ->where('product_id', $productId)
                    ->exists();
                if ($isDuplicate) {
                    throw new \Exception("Gagal! Produk ini SUDAH ADA di daftar pesanan (PO). Mohon jangan buat baru. Silakan cari item tersebut di daftar kanan (Belum Tertaut) dan tautkan.");
                }
                $product = Product::with(['unit:id,name', 'size:id,name', 'brand:id,name', 'category:id,name'])->findOrFail($productId);

                $snapshot = [
                    'name' => $product->name,
                    'code' => $product->code,
                    'category' => $product->category?->name ?? null,
                    'productType' => $product->productType?->name ?? null,
                    'unit' => $product->unit?->name ?? null,
                    'size' => $product->size?->name ?? null,
                    'brand' => $product->brand?->name ?? null,
                    'purchase_price' => $product->purchase_price,
                    'selling_price' => $product->selling_price,
                    'stock' => $product->stock ?? 0,
                    'inventory_type' => $product->inventory_type ?? '-',
                    'quantity' => 0
                ];

                $invoice->purchase->items()->create([
                    'purchase_invoice_id' => $invoice->id, // Auto-taut
                    'product_id' => $product->id,
                    'product_snapshot' => $snapshot,
                    'quantity' => 1, // Default Qty
                    'purchase_price' => $product->purchase_price, // Default Harga Master
                    'subtotal' => $product->purchase_price,
                ]);
                $createdItemsCount++;
            } else {
                throw new \InvalidArgumentException("Tipe aksi tidak dikenali (harus 'link' atau 'create').");
            }

            $this->recalculateTotalAmount($invoice);
            return [
                'linked' => $linkedItemsCount,
                'created' => $createdItemsCount
            ];
        });
    }
    /**
     * Melepaskan tautan item-item dari Nota.
     */
    public function unlinkItems(PurchaseInvoice $invoice, array $itemIds)
    {
        // Guard: Hanya izinkan unlinking jika status masih dalam proses validasi
        if (!in_array($invoice->purchase->status, [Purchase::STATUS_RECEIVED, Purchase::STATUS_CHECKING])) {
            throw new \Exception('Item hanya dapat dilepas pada fase validasi (Received atau Checking).');
        }

        return DB::transaction(function () use ($invoice, $itemIds) {
            // Update: Set purchase_invoice_id menjadi NULL
            $unlinkedCount = $invoice->purchase->items()
                ->whereIn('id', $itemIds)
                ->where('purchase_invoice_id', $invoice->id) // Hanya item yang tertaut ke nota ini
                ->update(['purchase_invoice_id' => null]);

            if ($unlinkedCount === 0) {
                throw new \Exception('Tidak ada item yang dilepas. Item mungkin sudah tertaut ke nota lain.');
            }

            // Catatan: Logika harga tidak diperlukan karena harga akan dihitung ulang saat di-link kembali.
            $this->recalculateTotalAmount($invoice);
            return $unlinkedCount;
        });
    }


    /**
     * Update Qty dan Harga Beli item yang sudah tertaut secara massal.
     */
    public function updateItemDetails(array $itemsData, PurchaseInvoice $invoice)
    {
        // Validasi array item
        $validator = Validator::make(['items' => $itemsData], [
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_items,id',
            'items.*.quantity' => 'required|integer|min:0', // Qty 0 untuk kasus item diganti
            'items.*.purchase_price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return DB::transaction(function () use ($invoice, $itemsData) {
            $updatedItems = [];

            foreach ($itemsData as $data) {
                $item = PurchaseItem::find($data['id']);
                if ($item) {
                    // Kalkulasi ulang subtotal
                    $subtotal = $data['quantity'] * $data['purchase_price'];

                    $item->update([
                        'quantity' => $data['quantity'],
                        'purchase_price' => $data['purchase_price'],
                        'subtotal' => $subtotal,
                    ]);
                    $updatedItems[] = $item;
                }
            }
            $this->recalculateTotalAmount($invoice);
            return $updatedItems;
        });
    }
}
