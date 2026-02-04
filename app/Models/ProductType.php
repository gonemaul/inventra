<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductType extends Model
{
    use SoftDeletes;

    protected $fillable = ['category_id', 'code', 'name', 'description'];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    // Tipe ini milik satu kategori (Hirarki)
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Tipe ini memiliki banyak produk
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
