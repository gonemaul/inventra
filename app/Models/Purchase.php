<?php

namespace App\Models;

use App\Models\User;
use App\Models\Supplier;
use App\Models\PurchaseItem;
use App\Models\PurchaseInvoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;
    protected $fillable = [
        'supplier_id',
        'user_id',
        'reference_no',
        'transaction_date',
        'status',
        'notes',
    ];

    const STATUS_ORDERED = 'dipesan';    // User 'Pesan'
    const STATUS_SHIPPED = 'dikirim';    // User tandai 'Diantar'
    const STATUS_RECEIVED = 'diterima';  // User tandai 'Sampai', siap 'Dicek'
    const STATUS_COMPLETED = 'selesai';  // User tandai 'Valid, update data'
    const STATUS_CANCELLED = 'dibatalkan'; // (Status tambahan)
    const STATUSES = [
        self::STATUS_ORDERED,
        self::STATUS_SHIPPED,
        self::STATUS_RECEIVED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    // Transaksi ini memiliki BANYAK item
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Transaksi ini milik SATU supplier
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    // Transaksi ini milik SATU user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
