<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Mencatat Perubahan Stok
     * * @param int $productId
     * @param int $qty (Positif = Masuk, Negatif = Keluar)
     * @param string $type ('sale', 'purchase', 'adjustment', etc)
     * @param string|null $ref (No Referensi Dokumen)
     * @param string|null $desc (Catatan Tambahan)
     */
    public function record($productId, $qty, $type, $ref = null, $desc = null)
    {
        // Pastikan update stok dan catat log terjadi bersamaan (Atomic)
        // Gunakan try-catch di controller pemanggil untuk handle error

        $product = Product::findOrFail($productId);

        $stockBefore = $product->stock;
        $stockAfter = $stockBefore + $qty;

        // 1. Update Master Stok di Produk
        if ($type != StockMovement::TYPE_INITIAL) {
            // tambah stok
            if (in_array($type, [StockMovement::TYPE_ADJUSTMENT_IN, StockMovement::TYPE_PURCHASE, StockMovement::TYPE_RETURN_IN])) {
                $stockAfter = $stockBefore + $qty;
            }
            // rubah stok(timpa)
            else if (in_array($type, [StockMovement::TYPE_ADJUSTMENT_OPNAME])) {
                $stockAfter = $qty;
                $qty = $qty > $stockBefore ? $qty - $stockBefore : $stockBefore - $qty;
            }
            // kurangi stock
            else if (in_array($type, [StockMovement::TYPE_ADJUSTMENT_OUT, StockMovement::TYPE_SALE, StockMovement::TYPE_RETURN_OUT])) {
                $stockAfter = $stockBefore - $qty;
            }
            $product->stock = $stockAfter;
        }
        $product->save();

        // 2. Catat di Buku Sejarah (Stock Movement)
        StockMovement::create([
            'product_id' => $productId,
            'user_id' => Auth::id() ?? 'system', // Handle jika dijalankan seeder/system
            'type' => $type,
            'reference_number' => $ref,
            'quantity' => $qty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'cost_price' => $product->purchase_price, // Kita simpan HPP saat kejadian ini
            'description' => $desc,
        ]);

        return $stockAfter;
    }
}
