<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'reference_number',
        'quantity',
        'stock_before',
        'stock_after',
        'cost_price',
        'description'
    ];

    const TYPE_INITIAL = 'initial'; //Inisialisasi / Stok Awal
    const TYPE_PURCHASE = 'purchase'; // Masuk dari Supplier
    const TYPE_SALE = 'sale'; // Keluar ke Pelanggan
    const TYPE_ADJUSTMENT_IN = 'adjustment_in';   // Koreksi Tambah (Opname)
    const TYPE_ADJUSTMENT_OUT = 'adjustment_out'; // Koreksi Kurang (Opname/Hilang/Rusak)
    const TYPE_RETURN_IN = 'return_in'; // Retur dari Customer (Masuk Gudang)
    const TYPE_RETURN_OUT = 'return_out'; // Retur ke Supplier (Keluar Gudang)

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
