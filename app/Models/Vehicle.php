<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'plate_number',
        'brand',
        'model',
        'color',
        'description',
        'security_code',
        'engine_interval_km',
        'engine_interval_days',
        'gear_interval_km',
        'gear_interval_days',
        'engine_type',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($vehicle) {
            if (empty($vehicle->security_code) && !empty($vehicle->plate_number)) {
                $vehicle->security_code = strrev($vehicle->plate_number);
            }
        });
    }

    public function oilServiceLogs()
    {
        return $this->hasMany(OilServiceLog::class);
    }
}
