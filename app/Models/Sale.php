<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'input_type',
        'reference_no',
        'transaction_date',
        'total_revenue',
        'total_profit',
        'user_id',
        'customer_id',
        'notes',
        'payment_method',
        'financial_summary'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_revenue' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'financial_summary' => 'array'
    ];

    const TYPE_REKAP = 'recap';
    const TYPE_POS = 'pos';

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_EWALLET = 'e-wallet';
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
