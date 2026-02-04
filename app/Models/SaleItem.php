<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $timestamps = false; // Detail tidak butuh created_at

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'selling_price',
        'capital_price', // HPP Terkunci
        'subtotal',
        'profit',
        'product_snapshot',
    ];

    protected $casts = [
        'product_snapshot' => 'array', // Otomatis jadi Array/JSON
        'quantity' => 'integer',
        'selling_price' => 'decimal:2',
        'capital_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    // Relasi Balik ke Header
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    // Relasi ke Master Produk
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
