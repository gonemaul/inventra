<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseInvoice extends Model
{
    use HasFactory;
    protected $appends = ['invoice_url'];
    protected $fillable = [
        'purchase_id',
        'invoice_number',
        'invoice_date',
        'invoice_image',
        'total_amount',
        'payment_status',
        'amount_paid',
        'due_date',
        'paid_at',
        'status'
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

    public function getInvoiceUrlAttribute()
    {
        // Jika kolom image kosong, return null atau gambar placeholder
        if (!$this->invoice_image) {
            return '/no-image.png';
        } else {
            return Storage::disk('s3')->url($this->invoice_image);
        }
    }
    public function getRemainingBalanceAttribute()
    {
        return $this->total_amount - $this->amount_paid;
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    // Nota ini (setelah divalidasi) memiliki BANYAK item
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class, 'purchase_invoice_id')->latest();
    }
}
