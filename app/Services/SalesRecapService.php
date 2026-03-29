<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SmartInsight;
use App\Models\StockMovement;
use App\Services\Analysis\Product\InventoryAnalyzer;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;       // Revisi: Kita pakai nama model 'Sale' sesuai migrasi terakhir

// Pastikan Model bernama Sale (sesuai migrasi terakhir 'sales')

class SalesRecapService
{
    protected $stockService;

    protected $inventoryAnalyzer;

    public function __construct(StockService $stockService, InventoryAnalyzer $inventoryAnalyzer)
    {
        $this->stockService = $stockService;
        $this->inventoryAnalyzer = $inventoryAnalyzer;
    }

    /**
     * Mengambil data transaksi pembelian untuk halaman index (Index.vue).
     */
    public function get(array $params)
    {
        $query = Sale::query();

        // 1. Filter Trashed (Sampah)
        // Jika param 'show_deleted' true -> Tampilkan semua (termasuk yg dihapus)
        // Jika param 'trashed' true -> Hanya tampilkan yg dihapus
        if (!empty($params['show_deleted']) && $params['show_deleted'] == 'true') {
            $query->withTrashed();
        } elseif (!empty($params['trashed'])) {
            $query->onlyTrashed();
        }

        // 2. Filter Search (Smart Search: Cari No Ref, Nama Pelanggan, ATAU Nama Produk di History)
        $query->when($params['search'] ?? null, function ($q, $search) {
             $searchTerms = array_filter(explode(' ', $search));
             
             $q->where(function ($subQuery) use ($search, $searchTerms) {
                // Exact Match
                $subQuery->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%");
                    })
                    // Smart Search: Multi-keyword matching di JSON / Relasi (Produk)
                    ->orWhereHas('items', function ($itemsQuery) use ($searchTerms) {
                         $itemsQuery->where(function ($itemsSubQuery) use ($searchTerms) {
                             foreach ($searchTerms as $term) {
                                  // SQLite/MySQL compatible broad search inside JSON text
                                  $itemsSubQuery->whereRaw('LOWER(product_snapshot) LIKE ?', ["%".strtolower($term)."%"]);
                             }
                         });
                    });
            });
        });

        // 4. Filter Tanggal (Date Range) - FIX: Pakai whereDate agar time component diabaikan (full day)
        $query->when($params['min_date'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '>=', $date))
            ->when($params['max_date'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '<=', $date));

        // 4. Filter Revenue (Nominal Range)
        $query->when($params['min_revenue'] ?? null, fn ($q, $revenue) => $q->where('total_revenue', '>=', $revenue))
            ->when($params['max_revenue'] ?? null, fn ($q, $revenue) => $q->where('total_revenue', '<=', $revenue));

        // --- SORTING & PAGINASI ---
        $sortBy = $params['sort'] ?? 'transaction_date';
        $sortDirection = $params['order'] ?? 'desc';
        $perPage = $params['per_page'] ?? 10;

        return $query
            ->with(['items.product.category:id,name']) // FIX: Jasa Indicator (eager loading)
            ->withCount('items')
            ->withSum('items', 'quantity')
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Menyimpan data rekap, mengurangi stok, dan hitung profit
     */
    public function storeRecap(array $data)
    {
        return DB::transaction(function () use ($data) {
            // FIX BUG CACHE TANGGAL:
            // Realtime (POS) SELALU pakai server time. Mencegah bug dari tab POS
            // yang dibiarkan terbuka berjam-jam lalu checkout dengan tanggal basi.
            // Rekap (Input Manual Malam) boleh pakai tanggal dari frontend.
            $transactionDate = ($data['input_type'] === Sale::TYPE_REKAP)
                ? ($data['transaction_date'] ?? now())
                : now(); // ← STRICT: Abaikan tanggal frontend untuk POS

            $saleType = $data['type'] ?? Sale::TYPE_RETAIL;

            // 1. Buat Header Sales
            $sale = Sale::create([
                'reference_no' => $this->generateReferenceNo($transactionDate, $data['input_type'], $saleType),
                'transaction_date' => $transactionDate,
                'type' => $saleType,
                'user_id' => Auth::id(),
                'customer_id' => $data['customer_id'] ?? null, // Simpan ID Member
                'input_type' => $data['input_type'],   // Simpan Tipe Input
                'notes' => $data['notes'] ?? null,

                'payment_method' => $data['payment_method'] ?? Sale::PAYMENT_METHOD_CASH,
                'total_revenue' => 0, // Nanti diupdate
                'total_profit' => 0,  // Nanti diupdate
                'financial_summary' => [],
            ]);

            $totalRevenue = 0;
            $totalProfit = 0;
            $itemsCount = 0;
            $totalQty = 0;
            $soldProductIds = [];

            // 2. Loop Items dari Frontend
            foreach ($data['items'] as $itemData) {
                // Ambil Master Produk Terbaru
                $product = Product::with(['brand', 'category', 'unit'])->lockForUpdate()->findOrFail($itemData['product_id']);

                $inputQty = (float) $itemData['quantity'];
                $sellingPrice = $itemData['selling_price'];

                // Validasi Stok (Opsional, jika tidak boleh minus)
                // SKIP jika kategori Jasa/Layanan
                $isService = in_array(strtolower($product->category->name ?? ''), ['jasa', 'layanan']);
                
                if (!$isService && $product->stock < $inputQty) {
                    throw new Exception("Stok tidak cukup untuk produk: {$product->name}. Sisa: {$product->stock}");
                }

                // $unitName = strtolower($product->unit->name ?? '');
                // $isDecimalAllowed = in_array($unitName, $this->decimalUnits);

                // // Cek apakah inputQty mengandung koma (misal 1.5)
                // // fmod(1.5, 1) hasilnya 0.5. fmod(1.0, 1) hasilnya 0.
                // if (!$isDecimalAllowed && fmod($inputQty, 1) !== 0.0) {
                //     throw new Exception("Produk {$product->name} dengan satuan '{$product->unit->name}' tidak boleh desimal (0.xx).");
                // }

                // Jika harga jual < harga modal, sistem bisa menolak atau membiarkan
                // if ($itemData['selling_price'] < $product->purchase_price) {
                //     throw new Exception("Harga jual {$product->name} di bawah modal! Cek harga.");
                // }

                // PENTING: Ambil HPP saat ini dari Master Product
                $capitalPrice = $product->purchase_price;

                // Hitungan Baris
                $subtotal = $inputQty * $sellingPrice;
                $rowCost = $inputQty * $capitalPrice;
                $rowProfit = $subtotal - $rowCost;

                // --- LOGIC SNAPSHOT (HISTORY AMAN) ---
                $snapshot = [
                    'name' => $product->name,
                    'code' => $product->code,
                    'brand' => $product->brand->name ?? '-',
                    'category' => $product->category->name ?? '-',
                    'unit' => $product->unit->name ?? 'Pcs',
                    'original_price' => $product->selling_price, // Harga jual normal (sebelum diedit kasir)
                ];

                // Simpan ke sale_items
                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $inputQty,
                    'selling_price' => $sellingPrice,
                    'capital_price' => $capitalPrice, // Terkunci selamanya
                    'subtotal' => $subtotal,
                    'profit' => $rowProfit,
                    'product_snapshot' => $snapshot,
                ]);

                $this->stockService->record(
                    productId: $product->id,
                    qty: $inputQty, // PASTIKAN NEGATIF (Keluar)
                    type: StockMovement::TYPE_SALE,
                    ref: $sale->reference_no, // No Nota Kasir
                    desc: 'Penjualan Kasir'
                );

                // Akumulasi Header
                $totalRevenue += $subtotal;
                $totalProfit += $rowProfit;
                $itemsCount++;
                $totalQty += $inputQty;
                
                $soldProductIds[] = $product->id;
            }

            if (! empty($data['discount_value']) && $data['discount_value'] > 0) {
                $type = $data['discount_type'] ?? null;
                if ($type === Sale::DISCON_PERCENT) {
                    $discountTotal = ($totalRevenue * $data['discount_value']) / 100;
                } else {
                    $discountTotal = $data['discount_value'];
                }
            } else {
                // Opsional: Set 0 jika tidak ada diskon
                $discountTotal = 0;
            }

            if ($discountTotal > $totalRevenue) {
                $discountTotal = $totalRevenue;
            }

            $grandTotal = $totalRevenue - $discountTotal;
            
            // FIX: Kurangi Profit dengan Diskon
            // Profit kotor (Row Loop) dikurangi Diskon Transaksi
            $finalProfit = $totalProfit - $discountTotal;

            // 3. Update Header dengan Total Final
            $sale->update(attributes: [
                'total_revenue' => $grandTotal,
                'total_profit' => $finalProfit, // Use adjusted profit
                'discount_type' => $data['discount_type'] ?? Sale::DISCON_FIXED,
                'discount_value' => $data['discount_value'] ?? 0,
                'discount_total' => $discountTotal, // Rupiah potongannya
                'financial_summary' => [
                    'item_count' => $itemsCount,
                    'total_qty' => $totalQty,
                    'payment_amount' => $data['payment_amount'] ?? 0,      // Uang diterima
                    'change_amount' => $data['change_amount'] ?? 0,       // Kembalian
                ],
            ]);

            // Dispatch asynchronous stock analysis to prevent checkout lag
            if (!empty($soldProductIds)) {
                \App\Jobs\AnalyzePostSaleJob::dispatch($soldProductIds);
            }

            return $sale;
        });
    }

    /**
     * Update Rekap/Transaksi dengan teknik Safe Revert & Re-deduct.
     *
     * Alur Atomik:
     *   1. Kembalikan stok semua item lama (REFUND)
     *   2. Hapus baris item lama
     *   3. Update header (notes, customer, payment, type)
     *   4. Insert item baru + potong stok baru (REPLAY)
     *   5. Hitung diskon & update total
     *
     * Seluruh proses di dalam DB::transaction() — gagal di tengah = rollback semua.
     */
    public function updateRecap(Sale $sale, array $data)
    {
        return DB::transaction(function () use ($sale, $data) {

            // --- LANGKAH 1: KEMBALIKAN SEMUA STOK LAMA (REFUND) ---
            foreach ($sale->items as $oldItem) {
                $this->stockService->record(
                    productId: $oldItem->product_id,
                    qty: $oldItem->quantity, // Positif = Masuk (Refund)
                    type: StockMovement::TYPE_RETURN_IN,
                    ref: $sale->reference_no,
                    desc: 'Koreksi Stok (Edit Transaksi)'
                );
            }

            // --- LANGKAH 2: HAPUS SEMUA ITEM LAMA ---
            $sale->items()->delete();

            // --- LANGKAH 3: UPDATE HEADER ---
            $sale->update([
                'notes' => $data['notes'] ?? null,
                'customer_id' => $data['customer_id'] ?? $sale->customer_id,
                'payment_method' => $data['payment_method'] ?? $sale->payment_method,
                'type' => $data['type'] ?? $sale->type,
                // Reset total, dihitung ulang di bawah
                'total_revenue' => 0,
                'total_profit' => 0,
            ]);

            // --- LANGKAH 4: INSERT ITEM BARU (REPLAY) ---
            $totalRevenue = 0;
            $totalProfit = 0;
            $itemsCount = 0;
            $totalQty = 0;
            $soldProductIds = [];

            foreach ($data['items'] as $itemData) {
                $product = Product::with(['brand', 'category', 'unit'])
                    ->lockForUpdate()
                    ->findOrFail($itemData['product_id']);

                $inputQty = (float) $itemData['quantity'];
                $sellingPrice = $itemData['selling_price'];

                // Skip validasi stok untuk Jasa/Layanan (konsisten dengan storeRecap)
                $isService = in_array(strtolower($product->category->name ?? ''), ['jasa', 'layanan']);

                if (!$isService && $product->stock < $inputQty) {
                    throw new Exception("Stok tidak cukup (setelah revisi) untuk: {$product->name}. Sisa: {$product->stock}");
                }

                $capitalPrice = $product->purchase_price;
                $subtotal = $inputQty * $sellingPrice;
                $rowCost = $inputQty * $capitalPrice;
                $rowProfit = $subtotal - $rowCost;

                $snapshot = [
                    'name' => $product->name,
                    'code' => $product->code,
                    'brand' => $product->brand->name ?? '-',
                    'category' => $product->category->name ?? '-',
                    'unit' => $product->unit->name ?? 'Pcs',
                    'original_price' => $product->selling_price,
                ];

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $inputQty,
                    'selling_price' => $sellingPrice,
                    'capital_price' => $capitalPrice,
                    'subtotal' => $subtotal,
                    'profit' => $rowProfit,
                    'product_snapshot' => $snapshot,
                ]);

                $this->stockService->record(
                    productId: $product->id,
                    qty: $inputQty,
                    type: StockMovement::TYPE_SALE,
                    ref: $sale->reference_no,
                    desc: 'Update Penjualan Kasir'
                );

                $totalRevenue += $subtotal;
                $totalProfit += $rowProfit;
                $itemsCount++;
                $totalQty += $inputQty;
                $soldProductIds[] = $product->id;
            }

            // --- LANGKAH 5: HITUNG DISKON & UPDATE TOTAL ---
            $discountTotal = 0;
            if (! empty($data['discount_value']) && $data['discount_value'] > 0) {
                $type = $data['discount_type'] ?? null;
                if ($type === Sale::DISCON_PERCENT) {
                    $discountTotal = ($totalRevenue * $data['discount_value']) / 100;
                } else {
                    $discountTotal = $data['discount_value'];
                }
            }

            if ($discountTotal > $totalRevenue) {
                $discountTotal = $totalRevenue;
            }

            $grandTotal = $totalRevenue - $discountTotal;
            $finalProfit = $totalProfit - $discountTotal;

            $sale->update([
                'total_revenue' => $grandTotal,
                'total_profit' => $finalProfit,
                'discount_type' => $data['discount_type'] ?? Sale::DISCON_FIXED,
                'discount_value' => $data['discount_value'] ?? 0,
                'discount_total' => $discountTotal,
                'financial_summary' => [
                    'item_count' => $itemsCount,
                    'total_qty' => $totalQty,
                    'payment_amount' => $data['payment_amount'] ?? 0,
                    'change_amount' => $data['change_amount'] ?? 0,
                ],
            ]);

            // Dispatch asynchronous stock analysis
            if (!empty($soldProductIds)) {
                \App\Jobs\AnalyzePostSaleJob::dispatch($soldProductIds);
            }

            return $sale;
        });
    }

    /**
     * Menghapus Rekap & Mengembalikan Stok
     */
    public function deleteRecap(Sale $sale)
    {
        return DB::transaction(function () use ($sale) {

            // 1. Loop semua item untuk kembalikan stok
            foreach ($sale->items as $item) {
                // GUNAKAN STOCK SERVICE - LOGGING
                 $this->stockService->record(
                    productId: $item->product_id,
                    qty: $item->quantity, // Positif = Masuk (Refund)
                    type: StockMovement::TYPE_RETURN_IN,
                    ref: $sale->reference_no,
                    desc: 'Void / Hapus Transaksi'
                );
            }

            // 2. Hapus Item & Header (Soft Delete)
            $sale->items()->delete(); // Soft delete items (optional jika cascade)

            return $sale->delete();          // Soft delete header
        });
    }

    /**
     * Generator Kode Transaksi Terpadu.
     *
     * Format:
     *   - Rekap:   REKAP/YYMMDD/XXX
     *   - Retail:  POS/YYMMDD/XXX
     *   - Bengkel: POS/B/YYMMDD/XXX
     *
     * PENTING: Nomor urut (XXX) bersifat UNIFIED — di-share lintas semua tipe
     * untuk satu hari yang sama, agar tidak ada duplikasi sequence.
     *
     * @param mixed  $date      Tanggal transaksi (Carbon|string)
     * @param string $inputType 'realtime' atau 'recap'
     * @param string $saleType  'retail' atau 'bengkel'
     * @return string
     */
    private function generateReferenceNo($date, string $inputType, string $saleType = Sale::TYPE_RETAIL): string
    {
        $dateCode = date('ymd', strtotime($date));

        // 1. Tentukan PREFIX berdasarkan kombinasi input_type & sale_type
        if ($inputType === Sale::TYPE_REKAP) {
            $prefix = "REKAP/{$dateCode}/";
        } elseif ($saleType === Sale::TYPE_BENGKEL) {
            $prefix = "POS/B/{$dateCode}/";
        } else {
            $prefix = "POS/{$dateCode}/";
        }

        // 2. UNIFIED COUNTER: Cari nomor urut terbesar dari SEMUA tipe di hari yang sama.
        //    Ini memastikan ketiga format (REKAP, POS, POS/B) tidak pernah bentrok nomornya.
        //    lockForUpdate() mencegah 2 kasir mendapat nomor yang sama (race condition).
        $allPrefixes = [
            "POS/{$dateCode}/",
            "POS/B/{$dateCode}/",
            "REKAP/{$dateCode}/",
        ];

        $maxSeq = 0;
        foreach ($allPrefixes as $searchPrefix) {
            $lastSale = Sale::withTrashed()
                ->where('reference_no', 'like', $searchPrefix . '%')
                ->lockForUpdate() // Pesimistik Lock: Block transaksi lain sampai commit
                ->orderByDesc('id')
                ->first();

            if ($lastSale) {
                $parts = explode('/', $lastSale->reference_no);
                $seq = (int) end($parts);
                $maxSeq = max($maxSeq, $seq);
            }
        }

        return $prefix . str_pad($maxSeq + 1, 3, '0', STR_PAD_LEFT);
    }
}
