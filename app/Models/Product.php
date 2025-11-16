<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'category_id',
        'unit_id',
        'size_id',
        'supplier_id',
        'name',
        'code',
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
}
