<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_no',
        'transaction_date',
        'total_revenue',
        'total_profit',
        'user_id',
        'notes'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_revenue' => 'decimal:2',
        'total_profit' => 'decimal:2',
    ];

    // Relasi ke Item Penjualan
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    // Relasi ke User (Kasir)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
