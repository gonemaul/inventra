<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'purchase_id',
        'invoice_number',
        'invoice_date',
        'invoice_image',
        'total_amount',
        'status',
        'payment_status',
        'amount_paid',
    ];
    const STATUS_UPLOADED = 'uploaded'; // Nota baru di-upload, belum divalidasi
    const STATUS_VALIDATED = 'validated'; // Item di nota sudah dicocokkan

    const STATUSES = [
        self::STATUS_UPLOADED,
        self::STATUS_VALIDATED,
    ];
    const PAYMENT_STATUS_UNPAID = 'unpaid'; // Belum dibayar
    const PAYMENT_STATUS_PARTIAL = 'partial'; // Dicicil
    const PAYMENT_STATUS_PAID = 'paid'; // Lunas

    const PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_UNPAID,
        self::PAYMENT_STATUS_PARTIAL,
        self::PAYMENT_STATUS_PAID,
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    // Nota ini (setelah divalidasi) memiliki BANYAK item
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_invoice_id');
    }
}
