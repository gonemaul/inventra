<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OilServiceLog extends Model
{
    protected $fillable = [
        'sale_id',
        'service_date',
        'vehicle_id',
        'current_km',
        'engine_oil_id',
        'gear_oil_id',
        'next_engine_oil_date',
        'next_engine_oil_km',
        'next_gear_oil_date',
        'next_gear_oil_km',
        'notes'
    ];

    protected $casts = [
        'service_date' => 'date',
        'next_engine_oil_date' => 'date',
        'next_gear_oil_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function engineOil()
    {
        return $this->belongsTo(Product::class, 'engine_oil_id');
    }

    public function gearOil()
    {
        return $this->belongsTo(Product::class, 'gear_oil_id');
    }
}
