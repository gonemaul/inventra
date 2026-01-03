<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PurchasePayment extends Model
{
    protected $appends = ['proof_image_url'];
    protected $fillable = [
        'purchase_invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'proof_image',
        'notes',
        'created_by'
    ];

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_CREDIT_CARD = 'credit_card';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHODS = [
        self::PAYMENT_METHOD_CASH,
        self::PAYMENT_METHOD_CREDIT_CARD,
        self::PAYMENT_METHOD_BANK_TRANSFER,
    ];
    public function getProofImageUrlAttribute()
    {
        // Jika kolom image kosong, return null atau gambar placeholder
        if (!$this->proof_image) {
            return '/no-image.png';
        } else {
            return Storage::disk('s3')->url($this->proof_image);
        }
    }
    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }
}
