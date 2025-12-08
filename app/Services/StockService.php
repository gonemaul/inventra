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
            $product->stock = $stockAfter;
        }
        // Jika ini pembelian (qty > 0), biasanya harga beli diupdate di controller Purchase
        // Jadi disini kita hanya update qty stok saja.
        $product->save();

        // 2. Catat di Buku Sejarah (Stock Movement)
        StockMovement::create([
            'product_id' => $productId,
            'user_id' => Auth::id() ?? null, // Handle jika dijalankan seeder/system
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
