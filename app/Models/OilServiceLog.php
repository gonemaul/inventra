<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OilServiceLog extends Model
{
    protected $fillable = [
        'sale_id',
        'vehicle_id',
        'current_km',
        'engine_oil_id',
        'gear_oil_id',
        'next_service_date',
        'next_service_km',
        'notes'
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
