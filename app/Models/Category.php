<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['slug', 'code', 'name', 'description'];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            // Jika slug belum diisi atau produk baru dibuat
            if (empty($category->slug) || $category->isDirty('name')) {
                $category->slug = Str::slug($category->name);
            }

            $originalSlug = $category->slug;
            $count = 1;

            // Loop untuk cek apakah slug sudah ada di database
            while (static::where('slug', $category->slug)->exists()) {
                $category->slug = $originalSlug.'-'.$count++;
            }
        });
    }

    public function productTypes()
    {
        return $this->hasMany(ProductType::class, 'category_id');
    }
}
