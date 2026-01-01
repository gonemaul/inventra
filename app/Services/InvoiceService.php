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
    protected $imageService;
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }
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
            'invoice_image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:20480', // Max 2MB
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();

        return DB::transaction(function () use ($validatedData, $purchase) {
            // 1. Upload File
            $newPath = $this->imageService->upload(
                $validatedData['invoice_image'],
                'purchases/invoices',
            );

            // 2. Buat Record Invoice
            $invoice = $purchase->invoices()->create([
                'invoice_number' => $validatedData['invoice_number'],
                'invoice_date' => $validatedData['invoice_date'],
                'due_date' => $validatedData['due_date'],
                'total_amount' => $validatedData['total_amount'],
                'payment_status' => $validatedData['payment_status'],
                'invoice_image' => $newPath, // Simpan path
                'amount_paid' => ($validatedData['payment_status'] === PurchaseInvoice::PAYMENT_STATUS_PAID) ? $validatedData['total_amount'] : 0,
                'status' => PurchaseInvoice::STATUS_UPLOADED,
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
            'invoice_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:20480',
        ]);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validatedData = $validator->validated();

        return DB::transaction(function () use ($validatedData, $invoice) {
            // 3. Handle File Update
            if (isset($validatedData['invoice_image'])) {
                $newPath = $this->imageService->upload(
                    $validatedData['invoice_image'],
                    'purchases/invoices',
                    $invoice->invoice_image
                );
                $invoice->update(['invoice_image ' => $newPath]);
            }

            // 4. Update Record
            $invoice->update([
                'invoice_number' => $validatedData['invoice_number'],
                'invoice_date' => $validatedData['invoice_date'],
                'due_date' => $validatedData['due_date'],
                'total_amount' => $validatedData['total_amount'],
                'payment_status' => $validatedData['payment_status'],
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


    public function recalculateTotalAmount(PurchaseInvoice $invoice)
    {
        foreach ($invoice->items as $item) {
            // Ambil data pesanan
            $snapshot = $item->product_snapshot;
            $orderedQty = $snapshot['quantity'] ?? 0; // Qty Rencana
            $receivedQty = $item->quantity;      // Qty Fisik

            // TENTUKAN STATUS
            $status = PurchaseItem::STATUS_PENDING; // Default

            if ($orderedQty == 0 && $receivedQty > 0) {
                // Kasus: Barang Baru / Susulan (Tidak ada di snapshot)
                $status = PurchaseItem::STATUS_EXTRA;
            } elseif ($receivedQty == 0) {
                // Kasus: Barang tidak datang sama sekali
                $status = PurchaseItem::STATUS_NONE;
            } elseif ($receivedQty == $orderedQty) {
                // Kasus: Pas mantab
                $status = PurchaseItem::STATUS_SESUAI;
            } elseif ($receivedQty < $orderedQty) {
                // Kasus: Datang sebagian
                $status = PurchaseItem::STATUS_PARTIAL;
            } elseif ($receivedQty > $orderedQty) {
                // Kasus: Bonus / Kelebihan kirim
                $status = PurchaseItem::STATUS_OVER;
            }

            // UPDATE ITEM
            $item->update([
                'item_status' => $status, // <--- SIMPAN STATUS DISINI
            ]);
        }
    }
    public function validateInvoice(PurchaseInvoice $invoice)
    {
        $totalReceived = $invoice->items()->sum('subtotal');
        if ($totalReceived != $invoice->total_amount) {
            throw new \Exception("Total amount pada nota ({$invoice->total_amount}) tidak sesuai dengan jumlah subtotal item yang diterima ({$totalReceived}). Silakan periksa kembali item yang tertaut.");
        }

        // Jika lolos semua pengecekan
        $invoice->update(['status' => PurchaseInvoice::STATUS_VALIDATED]);
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
                    $status = PurchaseItem::STATUS_SESUAI;
                    $subtotal = $item->subtotal;
                    $newQty = $item->quantity;
                    $newPrice = $item->purchase_price;
                    if (is_numeric($payload['newQty']) && $payload['newQty'] > 0) {
                        $newQty = $payload['newQty'];
                        $status = PurchaseItem::STATUS_PENDING; // Set status ke Pending saat ditautkan
                    }
                    if (is_numeric($payload['newPrice']) && $payload['newPrice'] > 0) {
                        $newPrice = $payload['newPrice'];
                        $subtotal = $newQty * $newPrice;
                        $status = PurchaseItem::STATUS_PENDING; // Set status ke Pending saat ditautkan
                    }
                    $item->update([
                        'quantity' => $newQty,
                        'purchase_price' => $newPrice,
                        'item_status' => $status,
                        'purchase_invoice_id' => $invoice->id,
                        'subtotal' => $subtotal
                    ]);
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
                    'item_status' => PurchaseItem::STATUS_PENDING
                ]);
                $createdItemsCount++;
            } else {
                throw new \InvalidArgumentException("Tipe aksi tidak dikenali (harus 'link' atau 'create').");
            }

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
                ->where('purchase_invoice_id', $invoice->id)
                ->update([
                    'purchase_invoice_id' => null,
                    'item_status' => PurchaseItem::STATUS_PENDING,
                    'quantity' => DB::raw("product_snapshot->'$.quantity'"),
                    'purchase_price' => DB::raw("product_snapshot->'$.purchase_price'")
                ]);
            if ($unlinkedCount === 0) {
                throw new \Exception('Tidak ada item yang dilepas. Item mungkin sudah tertaut ke nota lain.');
            }

            // Catatan: Logika harga tidak diperlukan karena harga akan dihitung ulang saat di-link kembali.
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