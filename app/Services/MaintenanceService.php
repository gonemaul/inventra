<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceService
{
    protected $salesRecapService;

    public function __construct(\App\Services\SalesRecapService $salesRecapService)
    {
        $this->salesRecapService = $salesRecapService;
    }

    /**
     * Store a maintenance transaction.
     *
     * @param array $data
     * @return \App\Models\Sale
     */
    public function storeMaintenance(array $data): \App\Models\Sale
    {
        return DB::transaction(function () use ($data) {
            // 1. Simpan Transaksi Penjualan (Retail/Jasa)
            $sale = $this->salesRecapService->storeRecap($data);

            $serviceData = $data['service_data'] ?? null;
            if (!$serviceData) return $sale;

            $vehicleId = $serviceData['vehicle']['id'] ?? null;
            
            // Jika tidak ada kendaraan (Anonim), kita tidak mencatat log atau mengecek KM
            if (!$vehicleId) return $sale;

            $currentKm = $serviceData['current_km'] ?? null;
            $vehicle = Vehicle::findOrFail($vehicleId);
            
            // --- VALIDASI KILOMETER MUNDUR (Critical) ---
            // Ambil log servis terakhir untuk kendaraan ini
            $lastService = \App\Models\OilServiceLog::where('vehicle_id', $vehicle->id)
                ->latest()
                ->first();

            if ($lastService && $currentKm !== null && $currentKm < $lastService->current_km) {
                throw new \Exception("Kilometer tidak boleh lebih rendah dari servis terakhir (Terakhir: " . number_format($lastService->current_km, 0, ',', '.') . " KM)");
            }
            
            // 2. Hitung estimasi servis berikutnya
            $calc = $this->calculateNextService($vehicle, (int)$currentKm);
            
            // 3. Buat Service Log
            $sale->oilServiceLog()->create([
                'vehicle_id' => $vehicle->id,
                'current_km' => $currentKm,
                'engine_oil_id' => $serviceData['engine_oil_id'] ?? null,
                'gear_oil_id' => $serviceData['gear_oil_id'] ?? null,
                'next_service_date' => $calc['data']['estimated_next_service_date'],
                'next_service_km' => $calc['data']['estimated_next_service_km'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $sale;
        });
    }

    /**
     * Calculate the estimated next service for a vehicle.
     *
     * @param Vehicle $vehicle
     * @param int|null $currentKm
     * @return array
     */
    public function calculateNextService(Vehicle $vehicle, ?int $currentKm = null): array
    {
        $nextServiceKm = null;
        if ($currentKm !== null) {
            $nextServiceKm = $currentKm + ($vehicle->service_interval_km ?? 0);
        }

        $nextServiceDate = Carbon::now()->addDays($vehicle->service_interval_days ?? 0);

        return [
            'status' => 'success',
            'data' => [
                'vehicle_id' => $vehicle->id,
                'current_km' => $currentKm,
                'estimated_next_service_km' => $nextServiceKm,
                'estimated_next_service_date' => $nextServiceDate->toDateString(),
                'service_interval_km' => $vehicle->service_interval_km,
                'service_interval_days' => $vehicle->service_interval_days,
            ],
            'meta' => [
                'calculated_at' => Carbon::now()->toDateTimeString(),
            ]
        ];
    }
}
