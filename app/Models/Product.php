<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $appends = ['full_name', 'market_insight'];
    protected $casts = [
        'snapshot' => 'array', // Otomatis convert JSON ke Array
    ];
    protected $fillable = [
        'category_id',
        'unit_id',
        'size_id',
        'supplier_id',
        'brand_id',
        'product_type_id',

        'name',
        'code',
        'slug',
        'description',
        'image_path',

        'stock',
        'min_stock',
        'inventory_type',

        'purchase_price',
        'selling_price',
        'target_margin_percent',

        'status',
        'snapshot'
    ];
    const STATUS_ACTIVE = 'active'; // Produk yang dijual
    const STATUS_DRAFT = 'draft';
    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DRAFT,
    ];
    const INVENTORY_TYPE_FAST = 'FAST';
    const INVENTORY_TYPE_SLOW = 'SLOW';
    const INVENTORY_TYPE_SEASONAL = 'SEASONAL';
    const INVENTORY_TYPE_DEAD = 'DEAD';
    const INVENTORIES = [
        self::INVENTORY_TYPE_FAST,
        self::INVENTORY_TYPE_SLOW,
        self::INVENTORY_TYPE_SEASONAL,
        self::INVENTORY_TYPE_DEAD
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Jika slug belum diisi atau produk baru dibuat
            if (empty($product->slug) || $product->isDirty('name')) {
                $product->slug = Str::slug($product->name);
            }

            $originalSlug = $product->slug;
            $count = 1;

            // Loop untuk cek apakah slug sudah ada di database
            while (static::where('slug', $product->slug)->exists()) {
                $product->slug = $originalSlug . '-' . $count++;
            }
        });
    }
    protected static function booted(): void
    {
        static::forceDeleting(function (Product $product) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
        });
    }
    /**
     * Accessor: Menggabungkan Atribut menjadi Nama Lengkap
     * Format: [BRAND] [NAMA PRODUK] [TIPE] [UKURAN] [UNTUK MOTOR]
     */
    public function getFullNameAttribute()
    {
        $brand = $this->brand?->name;
        $type  = $this->productType?->code;
        $size = $this->size ? $this->size->name : '';

        // LOGIKA PEMBERSIHAN (OPTIONAL TAPI PENTING)
        // Terkadang nama produk sudah mengandung tipe.
        // Contoh: Nama="Paku Payung", Tipe="Paku". Gabung="Paku Payung Paku" (Jelek).
        // Kita cek, jika Nama Produk mengandung kata Tipe, maka Tipe jangan ditampilkan lagi.
        $cleanType = '';
        if ($type && stripos($this->name, $type) === false) {
            $cleanType = $type;
        }
        return collect([
            $brand,         // Priority 1: Merk (Ex: Philips, Shell, Tekiro)
            $this->name,    // Priority 2: Nama Inti (Ex: LED Bulb, Advance, Kunci Ring)
            $cleanType,     // Priority 3: Tipe (Ex: Matic - jika belum ada di nama)
            $size,          // Priority 4: Ukuran (Ex: 10 Watt, 0.8L, 10mm)
        ])
            ->filter()          // Hapus yang null/kosong
            ->join(' ');        // Gabung dengan spasi
    }
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                // 1. Cari di Nama & Kode Produk (Paling Sering)
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")

                    // 2. Cari di Kolom Ukuran (Jika kolom text di tabel product)
                    ->orWhere('size', 'like', "%{$search}%")

                    // 3. Cari di Merk (Relasi)
                    ->orWhereHas('brand', fn($q) => $q->where('name', 'like', "%{$search}%"))

                    // 4. Cari di Tipe (Relasi)
                    ->orWhereHas('type', fn($q) => $q->where('name', 'like', "%{$search}%"))

                    // 5. Cari di Kategori (Relasi) - Optional
                    ->orWhereHas('category', fn($q) => $q->where('name', 'like', "%{$search}%"))

                    // 6. POWERFUL CONCAT SEARCH
                    // Ini agar keyword "Philips 10W" (terpisah spasi) tetap ketemu
                    // Kita gabungkan kolom virtual di SQL
                    ->orWhereRaw("CONCAT(
                IFNULL((SELECT name FROM brands WHERE id = products.brand_id), ''), ' ',
                name, ' ',
                IFNULL(size, ''), ' ',
                IFNULL((SELECT name FROM product_types WHERE id = products.product_type_id), '')
            ) LIKE ?", ["%{$search}%"]);
            });
        });
    }
    /**
     * Fungsi Sakti untuk update data sambil menyimpan history (snapshot).
     * * @param array $newData Key-Value data baru yang akan diupdate
     * @param string|null $reason Alasan update (misal: 'purchase', 'correction', 'repricing')
     */
    public function updateWithSnapshot(array $newData, string $reason = 'update')
    {
        // 1. Cek apakah ada perubahan data yang krusial?
        // Kita hanya buat snapshot jika ada perubahan pada Harga atau Stok
        $isPriceChanged =
            (isset($newData['purchase_price']) && $newData['purchase_price'] != $this->purchase_price) ||
            (isset($newData['selling_price']) && $newData['selling_price'] != $this->selling_price);

        $isStockChanged =
            (isset($newData['stock']) && $newData['stock'] != $this->stock);

        // Jika tidak ada perubahan penting, langsung update biasa
        if (!$isPriceChanged && !$isStockChanged) {
            $this->update($newData);
            return;
        }

        // 2. CAPTURE STATE (Masukan data saat ini ke dalam snapshot)
        // Data ini adalah data "SEBELUM" update
        $currentSnapshot = [
            'purchase_price' => $this->purchase_price,
            'selling_price'  => $this->selling_price,
            'stock'          => $this->stock,
            'margin_percent' => $this->current_margin_percent, // (Kita buat accessornya dibawah)
            'supplier_id'    => $this->supplier_id, // Pantau jika ganti supplier

            // Metadata tambahan
            'recorded_at'    => now()->toDateTimeString(),
            'reason'         => $reason,
        ];

        // 3. Simpan ke kolom snapshot
        $this->snapshot = $currentSnapshot;

        // 4. Update Data Baru
        $this->update($newData);
    }

    // Helper kecil untuk hitung margin saat ini (biar kodingan rapi)
    public function getCurrentMarginPercentAttribute()
    {
        if ($this->purchase_price <= 0) return 0;
        return (($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100;
    }
    public function getMarketInsightAttribute()
    {
        // A. Data SEKARANG (Current)
        $curBuy     = $this->purchase_price;
        $curSell    = $this->selling_price;
        $curMargin  = $this->current_margin_percent;
        $curStock   = $this->stock;

        // B. Data MASA LALU (Snapshot)
        $snap = $this->snapshot ?? [];

        // Fallback: Jika tidak ada snapshot (produk baru), pakai data sekarang
        $oldBuy    = $snap['purchase_price'] ?? $curBuy;
        $oldSell   = $snap['selling_price'] ?? $curSell;
        $oldMargin = $snap['margin_percent'] ?? $curMargin;

        // C. Kalkulasi Selisih
        return [
            'is_new' => empty($snap), // Penanda produk belum pernah ada update

            // 1. Analisa HPP (Modal)
            'cost' => [
                'now'    => $curBuy,
                'old'    => $oldBuy,
                'diff'   => $curBuy - $oldBuy,
                'percent' => $oldBuy > 0 ? (($curBuy - $oldBuy) / $oldBuy) * 100 : 0,
                'trend'  => $this->getTrend($curBuy, $oldBuy, 'inverse'), // Naik = Merah
            ],

            // 2. Analisa Harga Jual
            'price' => [
                'now'    => $curSell,
                'old'    => $oldSell,
                'diff'   => $curSell - $oldSell,
                'percent' => $oldSell > 0 ? (($curSell - $oldSell) / $oldSell) * 100 : 0,
                'trend'  => $this->getTrend($curSell, $oldSell, 'normal'), // Naik = Hijau
            ],

            // 3. Analisa Margin (Keuntungan)
            'margin' => [
                'now'    => round($curMargin, 1),
                'old'    => round($oldMargin, 1),
                'change' => round($curMargin - $oldMargin, 1),
                'trend'  => $this->getTrend($curMargin, $oldMargin, 'normal'),
            ],

            // 4. Metadata Tambahan (Opsional untuk UI)
            'meta' => [
                'last_update' => $snap['recorded_at'] ?? $this->updated_at,
                'reason'      => $snap['reason'] ?? 'initial',
            ]
        ];
    }

    // Helper Trend
    private function getTrend($new, $old, $mode = 'normal')
    {
        if ($new == $old) return 'stable';
        if ($mode == 'normal') return $new > $old ? 'up_good' : 'down_bad';
        return $new > $old ? 'up_bad' : 'down_good'; // Untuk HPP (HPP naik itu buruk)
    }

    /**
     * Summary of lastSale
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<StockMovement, Product>
     * Dipakai di report dead stock
     * Mengambil 1 data movement tipe 'sale' yang paling baru
     */
    public function lastSale()
    {
        return $this->hasOne(StockMovement::class)
            ->where('type', StockMovement::TYPE_SALE)
            ->latestOfMany();
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function insights()
    {
        return $this->hasMany(SmartInsight::class);
    }
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }
}
