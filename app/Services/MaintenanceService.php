<?php

namespace App\Services;

use App\Models\OilServiceLog;
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
            $lastService = OilServiceLog::where('vehicle_id', $vehicle->id)
                ->latest()
                ->first();

            if ($lastService && $currentKm !== null && $currentKm < $lastService->current_km) {
                throw new \Exception("Kilometer tidak boleh lebih rendah dari servis terakhir (Terakhir: " . number_format($lastService->current_km, 0, ',', '.') . " KM)");
            }
            
            // 2. Hitung estimasi servis berikutnya
            $isChangingGearOil = !empty($serviceData['gear_oil_id']);
            $calc = $this->calculateNextService($vehicle, (int)$currentKm, $isChangingGearOil);
            
            // 3. Buat Service Log
            $sale->oilServiceLog()->create([
                'vehicle_id' => $vehicle->id,
                'service_date' => Carbon::today(),
                'current_km' => $currentKm,
                'engine_oil_id' => $serviceData['engine_oil_id'] ?? null,
                'gear_oil_id' => $serviceData['gear_oil_id'] ?? null,
                'next_engine_oil_date' => $calc['next_engine_oil_date'] ?? null,
                'next_engine_oil_km' => $calc['next_engine_oil_km'] ?? null,
                'next_gear_oil_date' => $calc['next_gear_oil_date'] ?? null,
                'next_gear_oil_km' => $calc['next_gear_oil_km'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return $sale;
        });
    }

    /**
     * Menghitung estimasi ganti oli berikutnya secara cerdas.
     * * @param Vehicle $vehicle Model kendaraan
     * @param int|null $currentKm KM saat ini (opsional jika spidometer mati)
     * @param bool $isChangingGearOil Apakah hari ini ganti oli gardan juga?
     * @return array
     */
    public function calculateNextService(Vehicle $vehicle, ?int $currentKm, bool $isChangingGearOil = false): array
    {
        $now = Carbon::now();

        // 1. Dapatkan log servis terakhir yang memiliki KM valid (untuk hitung rata-rata)
        $lastService = OilServiceLog::where('vehicle_id', $vehicle->id)
            ->whereNotNull('current_km')
            ->latest('service_date')
            ->first();

        // 2. TENTUKAN DEFAULT (Fallback jika spidometer rusak atau motor baru pertama kali servis)
        $nextEngineDate = $now->copy()->addDays($vehicle->engine_interval_days);
        $nextEngineKm = $currentKm ? $currentKm + $vehicle->engine_interval_km : null;

        $nextGearDate = $now->copy()->addDays($vehicle->gear_interval_days);
        $nextGearKm = $currentKm ? $currentKm + $vehicle->gear_interval_km : null;

        // 3. ALGORITMA SMART AVERAGE (Berjalan hanya jika ada histori & spidometer nyala)
        if ($lastService && $currentKm) {
            $daysDiff = $now->diffInDays($lastService->service_date);
            $kmDiff = $currentKm - $lastService->current_km;

            // Mencegah pembagian nol atau error jika tanggal sama / KM diinput mundur
            if ($daysDiff > 0 && $kmDiff > 0) {
                $dailyKm = $kmDiff / $daysDiff;

                // SMART CLAMP: Batasi ekstrem. Minimal 5 km/hari (mangkrak), Maksimal 100 km/hari (touring)
                $dailyKm = max(5, min(100, $dailyKm));

                // Prediksi Mesin: "Whichever comes first"
                $estDaysEngine = $vehicle->engine_interval_km / $dailyKm;
                if ($estDaysEngine < $vehicle->engine_interval_days) {
                    // Jika prediksi hari lebih cepat dari default (sering dipakai), majukan tanggalnya
                    $nextEngineDate = $now->copy()->addDays(round($estDaysEngine));
                }

                // Prediksi Gardan: "Whichever comes first"
                $estDaysGear = $vehicle->gear_interval_km / $dailyKm;
                if ($estDaysGear < $vehicle->gear_interval_days) {
                    $nextGearDate = $now->copy()->addDays(round($estDaysGear));
                }
            }
        }

        // 4. LOGIKA BAWAAN OLI GARDAN
        // Jika hari ini TIDAK ganti oli gardan, kita tidak boleh me-reset target gardannya.
        // Kita harus tarik target gardan dari riwayat sebelumnya.
        if (!$isChangingGearOil && $vehicle->engine_type === 'matic') {
            $lastGearService = OilServiceLog::where('vehicle_id', $vehicle->id)
                ->whereNotNull('gear_oil_id')
                ->latest('service_date')
                ->first();

            if ($lastGearService) {
                $nextGearDate = Carbon::parse($lastGearService->next_gear_oil_date);
                $nextGearKm = $lastGearService->next_gear_oil_km;
            }
        }

        // Return format yang siap di-insert ke database
        return [
            'next_engine_oil_date' => $nextEngineDate->format('Y-m-d'),
            'next_engine_oil_km' => $nextEngineKm,
            'next_gear_oil_date' => $vehicle->engine_type === 'matic' && $nextGearDate ? $nextGearDate->format('Y-m-d') : null,
            'next_gear_oil_km' => $vehicle->engine_type === 'matic' ? $nextGearKm : null,
        ];
    }
}
