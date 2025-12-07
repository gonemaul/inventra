<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductType extends Model
{
    use SoftDeletes;
    protected $fillable = ['category_id', 'code', 'name', 'description'];
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
