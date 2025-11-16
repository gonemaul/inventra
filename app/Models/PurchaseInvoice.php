<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory;

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
