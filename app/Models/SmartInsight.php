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

    // --- KONSTANTA TYPE (JENIS ANALISA) ---
    public const TYPE_RESTOCK = 'restock';
    public const TYPE_DEAD_STOCK = 'dead_stock';
    public const TYPE_HIGH_MARGIN = 'high_margin';
    public const TYPE_MARGIN = 'margin_alert';
    public const TYPE_TREND = 'trend';
    public const TYPE_NEW = 'new';
    public const TYPE_DAILY_STRATEGY = 'daily_strategy';
    public const TYPE_DAILY_RESTOCK = 'daily_restock_plan';
    public const TYPE_WEEKLY_DSS_DEADSTOCK = 'weekly_dss_deadstock';
    public const TYPE_WEEKLY_DSS_TRENDING = 'weekly_dss_trending';


    // --- KONSTANTA SEVERITY (TINGKAT BAHAYA) ---
    public const SEVERITY_CRITICAL = 'critical'; // Merah (Bahaya/Habis)
    public const SEVERITY_WARNING = 'warning';   // Kuning (Hati-hati)
    public const SEVERITY_INFO = 'info';         // Biru (Info positif)
    public const SEVERITY_SAFE = 'safe';         // Hijau/Internal logic

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
