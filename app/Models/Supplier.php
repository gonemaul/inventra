<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'phone', 'address', 'status', 'type', 'description'];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    const TYPE_ONLINE = 'type_online';

    const TYPE_OFFLINE = 'type_offline';

    const TYPES = [
        self::TYPE_ONLINE,
        self::TYPE_OFFLINE,
    ];
}
