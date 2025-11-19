<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'purchase_id',
        'purchase_invoice_id',
        'product_id',
        'product_snapshot',
        'quantity',
        'purchase_price',
        'subtotal',
    ];
    // Beri tahu Laravel untuk otomatis cast 'product_snapshot' ke array/objek
    protected $casts = [
        'product_snapshot' => 'array',
    ];

    // Item ini milik SATU transaksi
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    // Item ini (mungkin) milik SATU nota
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    // Item ini (mungkin) milik SATU produk
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
