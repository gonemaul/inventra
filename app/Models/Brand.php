<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'name', 'description'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
