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
    protected $appends = ['image_url'];
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
                $product->slug = Str::slug($product->name . ' ' . $product->code);
            }

            $originalSlug = $product->slug;
            $count = 1;

            // Loop untuk cek apakah slug sudah ada di database
            while (static::withTrashed()->where('slug', $product->slug)->exists()) {
                $product->slug = $originalSlug . '-' . $count++;
            }
        });
    }
    protected static function booted(): void
    {
        static::forceDeleting(function (Product $product) {
            if ($product->image_path) {
                Storage::disk('s3')->delete($product->image_path);
            }
        });
    }
    public function getImageUrlAttribute()
    {
        // Jika kolom image kosong, return null atau gambar placeholder
        if ($this->image_path) {
            // return '/no-image.png';
            return Storage::disk('s3')->url($this->image_path);
        } else {
            return null;
        }
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

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }
}
