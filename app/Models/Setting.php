<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $hidden = ['id', 'value', 'created_at', 'updated_at'];
}
