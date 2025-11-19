<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class PurchaseService
{
    /**
     * Mengambil data transaksi pembelian untuk halaman index.
     *
     * @param array $params
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function get(array $params)
    {
        // Eager load relasi yang penting
        $query = Purchase::with(['supplier', 'user', 'items'])->withSum('items', 'subtotal');;

        // --- FILTER ---

        // Filter by Reference No
        if (isset($params['search']) && $params['search']) {
            $query->where('reference_no', 'like', '%' . $params['search'] . '%');
        }

        // Filter by Status Operasional (dipesan, dikirim, dll)
        if (isset($params['status']) && $params['status']) {
            $query->where('status', $params['status']);
        }

        // Filter by Supplier
        if (isset($params['supplier_id']) && $params['supplier_id']) {
            $query->where('supplier_id', $params['supplier_id']);
        }

        // 4. Filter Rentang Tanggal (Transaction Date)
        if (isset($params['start_date']) && $params['start_date']) {
            $query->whereDate('transaction_date', '>=', $params['start_date']);
        }
        if (isset($params['end_date']) && $params['end_date']) {
            $query->whereDate('transaction_date', '<=', $params['end_date']);
        }

        // 5. Filter Rentang Total (HAVING)
        // Karena 'items_sum_subtotal' adalah hasil kalkulasi (aggregate),
        // kita harus menggunakan 'having', bukan 'where'.
        if (isset($params['min_total']) && is_numeric($params['min_total'])) {
            $query->having('items_sum_subtotal', '>=', $params['min_total']);
        }
        if (isset($params['max_total']) && is_numeric($params['max_total'])) {
            $query->having('items_sum_subtotal', '<=', $params['max_total']);
        }

        // Filter by Payment Status (dari tabel 'purchase_invoices')
        if (isset($params['payment_status']) && $params['payment_status']) {
            // Cek jika ADA nota yang statusnya cocok
            $query->whereHas('invoices', function ($q) use ($params) {
                $q->where('payment_status', $params['payment_status']);
            });
        }

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
     * Membuat Transaksi Induk (Purchase) DAN Item-nya (PurchaseItem)
     * Ini adalah inti dari alur 'create' Anda.
     *
     * @param array $data (Data dari form, termasuk array 'items')
     * @return Purchase
     * @throws ValidationException
     */
    public function create(array $data)
    {
        // 1. Validasi data
        // Kita validasi data induk DAN data 'items' (nested array)
        $validator = Validator::make($data, [
            'supplier_id' => 'nullable|exists:suppliers,id',
            'user_id' => 'required|exists:users,id', // Pastikan user_id dikirim
            'transaction_date' => 'required|date',
            'status' => ['required', Rule::in(Purchase::STATUSES)],
            'notes' => 'nullable|string',

            // Validasi nested array 'items'
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

        // 2. Gunakan DB Transaction (SANGAT PENTING)
        // Jika salah satu item gagal disimpan, seluruh transaksi akan dibatalkan.
        return DB::transaction(function () use ($validatedData, $purchaseItemsData) {

            // 3. Buat Transaksi Induk (Purchase)
            $purchase = Purchase::create([
                'supplier_id' => $validatedData['supplier_id'] ?? null,
                'user_id' => $validatedData['user_id'],
                'reference_no' => $this->generateReferenceNo(), // (Kita perlu buat helper ini)
                'transaction_date' => $validatedData['transaction_date'],
                'status' => $validatedData['status'],
                'notes' => $validatedData['notes'] ?? null,
            ]);

            // 4. Loop dan buat setiap PurchaseItem
            foreach ($purchaseItemsData as $itemData) {
                $product = Product::with(['unit', 'size', 'category'])->find($itemData['product_id']);

                // Buat 'product_snapshot' (permintaan Anda)
                $snapshot = [
                    'name' => $product->name,
                    'code' => $product->code,
                    'category' => $product->category->name ?? null,
                    'unit' => $product->unit->name ?? null,
                    'size' => $product->size->name ?? null,
                    'purchase_price' => $product->purchase_price ?? 0,
                    'selling_price' => $product->selling_price,
                    'stock' => $product->stock,
                ];

                $subtotal = $itemData['quantity'] * $itemData['purchase_price'];

                // Buat item, tautkan ke induk
                $purchase->items()->create([
                    // purchase_id diisi otomatis oleh relasi
                    'purchase_invoice_id' => null, // Sesuai alur Anda, ini NULL dulu
                    'product_id' => $itemData['product_id'],
                    'product_snapshot' => $snapshot,
                    'quantity' => $itemData['quantity'],
                    'purchase_price' => $itemData['purchase_price'],
                    'subtotal' => $subtotal,
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Helper untuk membuat Nomor Referensi unik.
     */
    private function generateReferenceNo(): string
    {
        // Contoh sederhana: "TRX-TAHUNBULAN-ANGKAUNIK"
        $prefix = 'TRX-' . date('Ym');
        $lastPurchase = Purchase::where('reference_no', 'like', $prefix . '%')
            ->orderBy('reference_no', 'desc')
            ->first();

        $number = 1;
        if ($lastPurchase) {
            $number = (int)substr($lastPurchase->reference_no, -4) + 1;
        }

        return $prefix . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // --- (Method lain seperti addInvoice, matchItem, completePurchase akan kita buat nanti) ---
}
