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

        // 2. Filter Search (PENTING: Dibungkus closure agar tidak merusak filter lain)
        $query->when($params['search'] ?? null, function ($q, $search) {
            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('reference_no', 'like', "%{$search}%");
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
            // $transactionDate = ($data['input_type'] === Sale::TYPE_REKAP && $data['created_at'])
            //     ? $data['created_at']
            //     : now();
            // 1. Buat Header Sales
            $sale = Sale::create([
                'reference_no' => $this->generateReferenceNo($data['report_date'], $data['input_type']),
                'transaction_date' => $data['report_date'],
                'user_id' => Auth::id(),
                'customer_id' => $data['customer_id'] ?? null, // Simpan ID Member
                'input_type' => $data['input_type'],   // Simpan Tipe Input
                'notes' => $data['notes'] ?? null,

                'payment_method' => $data['payment_method'] ?? Sale::PAYMENT_METHOD_CASH,
                'total_revenue' => 0, // Nanti diupdate
                'total_profit' => 0,  // Nanti diupdate
                'financial_summary' => [],
                // 'created_at' => $transactionDate,
            ]);

            $totalRevenue = 0;
            $totalProfit = 0;
            $itemsCount = 0;
            $totalQty = 0;

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

                $analysis = $this->inventoryAnalyzer->calculateInventoryHealth($product);

                if (in_array($analysis['status'], [SmartInsight::SEVERITY_CRITICAL, SmartInsight::SEVERITY_WARNING])) {
                    $this->stockService->sendLowStock($product, $analysis);
                }
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

            return $sale;
        });
    }

    /**
     * Update Rekap dengan teknik Safe Reset
     */
    public function updateRecap(Sale $sale, array $data)
    {
        return DB::transaction(function () use ($sale, $data) {

            // --- LANGKAH 1: KEMBALIKAN SEMUA STOK LAMA (REFUND) ---
            foreach ($sale->items as $oldItem) {
                // GUNAKAN STOCK SERVICE AGAR TERCATAT
                $this->stockService->record(
                    productId: $oldItem->product_id,
                    qty: $oldItem->quantity, // Positif = Masuk (Refund)
                    type: StockMovement::TYPE_RETURN_IN,
                    ref: $sale->reference_no,
                    desc: 'Koreksi Stok (Edit Transaksi)'
                );
            }

            // --- LANGKAH 2: HAPUS SEMUA ITEM LAMA ---
            // Kita hapus fisik baris item lama agar tidak pusing mikirin update/insert satu-satu
            $sale->items()->delete();
            // (Note: Pastikan sale_items pakai SoftDeletes atau ForceDelete sesuai kebutuhan.
            // Kalau di migrasi pakai cascadeOnDelete, aman).

            // $totalRevenue = 0;
            // $totalProfit = 0;
            // --- LANGKAH 3: UPDATE HEADER ---
            $sale->update([
                'transaction_date' => $data['report_date'],
                'notes' => $data['notes'] ?? null,
                // Reset total, nanti dihitung ulang di bawah
                'total_revenue' => 0,
                'total_profit' => 0,
            ]);

            // --- LANGKAH 4: INSERT ITEM BARU (REPLAY) ---
            // Logika ini COPY-PASTE dari method storeRecap,
            // atau sebaiknya dibuat private method biar reusable.

            $totalRevenue = 0;
            $totalProfit = 0;
            $itemsCount = 0;
            $totalQty = 0;

            foreach ($data['items'] as $itemData) {
                $product = Product::with(['brand', 'category', 'unit'])
                    ->lockForUpdate()
                    ->findOrFail($itemData['product_id']);

                $inputQty = $itemData['quantity'];
                $sellingPrice = $itemData['selling_price'];

                // Cek Stok (Stok di DB sekarang adalah: Stok Awal + Pengembalian Langkah 1)
                // Jadi aman untuk dikurangi lagi.
                if ($product->stock < $inputQty) {
                    throw new Exception("Stok tidak cukup (setelah revisi) untuk: {$product->name}.");
                }

                // Ambil HPP (Profit Locking Baru)
                // Jika mau pakai HPP lama, harus ambil dari $oldItem, tapi itu rumit.
                // Asumsi: Edit transaksi berarti mengikuti harga modal saat ini (atau logic lain).
                // Untuk simplifikasi, kita ambil modal master saat ini.
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

                // $product->decrement('stock', $inputQty);
                $this->stockService->record(
                    productId: $product->id,
                    qty: $inputQty, // Positif, StockService akan kurangi otomatis jika tipe SALE
                    type: StockMovement::TYPE_SALE,
                    ref: $sale->reference_no, // No Nota Kasir
                    desc: 'Update Penjualan Kasir'
                );

                $totalRevenue += $subtotal;
                $totalProfit += $rowProfit;
                $itemsCount++;
                $totalQty += $inputQty;
            }

            // --- HITUNG DISKON DI EDIT (Logic Tambahan) ---
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
                'financial_summary' => ['item_count' => $itemsCount, 'total_qty' => $totalQty],
            ]);

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

    // Generator No: POS/YYMMDD/001
    private function generateReferenceNo($date, $type)
    {
        $dateCode = date('ymd', strtotime($date));
        if ($type === Sale::TYPE_REALTIME) {
            $prefix = 'POS/'.$dateCode.'/';
        } elseif ($type === Sale::TYPE_REKAP) {
            $prefix = 'REKAP/'.$dateCode.'/';
        } else {
            $prefix = 'unknown';
        }

        // Cari nomor terakhir hari itu (termasuk yang sudah dihapus agar sequence tidak bentrok)
        $lastSale = Sale::withTrashed()
            ->where('reference_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($lastSale) {
            $parts = explode('/', $lastSale->reference_no);
            $seq = (int) end($parts) + 1;
        }

        return $prefix.str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
