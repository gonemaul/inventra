<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'member_code',
        'name',
        'phone',
        'address',
        'points',
        'is_active',
    ];
}
