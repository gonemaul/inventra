<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmartInsight extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'severity',
        'title',
        'message',
        'payload',
        'action_url',
        'is_read',
        'is_notified',
    ];
    // PENTING: Agar kolom payload otomatis berubah dari JSON string ke Array PHP
    protected $casts = [
        'payload' => 'array',
        'is_read' => 'boolean',
        'is_notified' => 'boolean',
    ];

    // Relasi ke Produk
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scope Helper: Ambil hanya yang belum dibaca/aktif
    public function scopeActive($query)
    {
        return $query->where('is_read', false);
    }

    // Scope Helper: Ambil yang Critical saja
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }
}
