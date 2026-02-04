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
        'rejected_quantity',
        'rejection_note',
        'item_status',
    ];

    // Beri tahu Laravel untuk otomatis cast 'product_snapshot' ke array/objek
    protected $casts = [
        'product_snapshot' => 'array',
    ];

    const STATUS_PENDING = 'pending'; // Menunggu (Belum diproses checking).

    const STATUS_SESUAI = 'fulfilled'; // Sesuai (Barang datang pas).

    const STATUS_PARTIAL = 'partial'; // Sebagian (Pesan 10, datang 5. Masih hutang barang).

    const STATUS_NONE = 'canceled'; // Batal/Kosong (Supplier kehabisan stok).

    const STATUS_EXTRA = 'extra'; // Susulan (Barang tidak dipesan, tapi dikirim/ditambahkan manual).

    const STATUS_OVER = 'over'; // Berlebih (Pesan 10, dikasih bonus jadi 12). Opsional

    const STATUS_REJECTED = 'rejected'; // Ditolak (Barang rusak/tidak sesuai saat diterima).

    const STATUS_PRICE_CORRECTED = 'price_corrected'; // Harga dikoreksi (Harga beli di nota berbeda dengan kesepakatan awal).

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
