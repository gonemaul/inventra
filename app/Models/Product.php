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
        'stock',
        'min_stock',
        'purchase_price',
        'selling_price',
        'description',
        'image_path',
        'status',
    ];
    const STATUS_ACTIVE = 'active'; // Produk yang dijual
    const STATUS_DRAFT = 'draft';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_DRAFT,
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
        return $this->belongsTo(ProductType::class);
    }
}
