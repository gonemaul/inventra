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
