<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\OilServiceLog;
use App\Models\Product;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class VehicleController extends Controller
{
    protected $maintenanceService;

    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * Display Customer Hub with Data Mining / DSS Predictions.
     */
    public function index()
    {
        // 1. STATS UMUM (Real-time ringan)
        $stats = [
            'total_vehicles' => Vehicle::count(),
            'service_today' => tap(OilServiceLog::whereDate('created_at', Carbon::today())->count(), fn($c) => $c),
            'avg_km' => round(OilServiceLog::avg('current_km') ?? 0),
        ];

        // 2. DSS DATA MINING PINTAR (Didasarkan pada data lampau & algoritma prediksi dari MaintenanceService)
        // Caching 12 jam agar tidak "over" / membebani database (sangat cepat)
        $dssInsights = Cache::remember('dss_vehicle_predictions', 60 * 60 * 12, function () {
            $next30Days = Carbon::now()->addDays(30);
            
            // Prediksi: Berapa kendaraan yang akan jatuh tempo dalam 30 hari ke depan (termasuk overdue 14 hari)?
            $upcomingServices = OilServiceLog::with(['vehicle', 'engineOil', 'gearOil'])
                ->whereNotNull('next_engine_oil_date')
                ->where('next_engine_oil_date', '<=', $next30Days)
                ->where('next_engine_oil_date', '>=', Carbon::now()->subDays(14))
                ->whereIn('id', function($query) {
                    $query->selectRaw('MAX(id)')->from('oil_service_logs')->groupBy('vehicle_id');
                })
                ->get();

            // Hitung agregasi kebutuhan riil oli (Mesin & Gardan)
            $engineOilDemand = [];
            $gearOilDemand = [];
            foreach ($upcomingServices as $log) {
                // Mesin
                if ($log->engine_oil_id && $log->engineOil) {
                    $name = $log->engineOil->name;
                    $engineOilDemand[$name] = ($engineOilDemand[$name] ?? 0) + 1;
                }
                // Gardan (hanya jika target gardannya juga dalam 30 hari ke depan)
                if ($log->gear_oil_id && $log->gearOil && $log->next_gear_oil_date && Carbon::parse($log->next_gear_oil_date)->lte($next30Days)) {
                    $name = $log->gearOil->name;
                    $gearOilDemand[$name] = ($gearOilDemand[$name] ?? 0) + 1;
                }
            }
            
            arsort($engineOilDemand);
            arsort($gearOilDemand);

            // Daftar kendaraan yang paling mendekati waktu servis
            $upcomingList = $upcomingServices->sortBy('next_engine_oil_date')->map(function($log) {
                return [
                    'id' => $log->vehicle_id,
                    'plate_number' => $log->vehicle->plate_number ?? 'UNKNOWN',
                    'brand' => $log->vehicle->brand ?? '',
                    'model' => $log->vehicle->model ?? '',
                    'next_engine_date' => Carbon::parse($log->next_engine_oil_date)->format('Y-m-d'),
                    'engine_oil_name' => $log->engineOil->name ?? 'Belum terdata',
                ];
            })->values()->take(6); // Ambil 6 teratas

            return [
                'due_next_30_days' => $upcomingServices->count(),
                'engine_oil_predictions' => $engineOilDemand,
                'gear_oil_predictions' => $gearOilDemand,
                'upcoming_vehicles' => $upcomingList,
            ];
        });

        return Inertia::render('CustomerHub', [
            // Gunakan Pagination agar tidak memory leak (Performa)
            'vehicles' => Vehicle::latest()->paginate(12),
            'stats' => $stats,
            'dss' => $dssInsights
        ]);
    }

    /**
     * Search vehicles by plate number.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        if (!$query) return response()->json([]);

        // Smart search: strip spaces, case-insensitive
        $cleanQuery = strtolower(str_replace(' ', '', $query));

        $vehicles = Vehicle::whereRaw("LOWER(REPLACE(plate_number, ' ', '')) LIKE ?", ["%{$cleanQuery}%"])
            ->limit(10)
            ->get();

        return response()->json($vehicles);
    }

    /**
     * Get vehicle with last service info.
     */
    public function getVehicleInfo(Request $request)
    {
        $plateNumber = $request->input('plate_number');
        if (!$plateNumber) return response()->json(['status' => 'error', 'message' => 'No plate number provided']);

        $vehicle = Vehicle::where('plate_number', $plateNumber)->first();
        if (!$vehicle) return response()->json(['status' => 'not_found', 'message' => 'Vehicle not found']);

        $lastService = OilServiceLog::where('vehicle_id', $vehicle->id)
            ->with(['engineOil:id,name', 'gearOil:id,name'])
            ->latest()
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'vehicle' => $vehicle,
                'last_service' => $lastService,
            ]
        ]);
    }

    /**
     * Store a new vehicle.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plate_number' => 'required|unique:vehicles,plate_number',
            'brand' => 'required|string',
            'model' => 'required|string',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'engine_type' => 'required|in:matic,manual',
            'engine_interval_km' => 'required|integer|min:0',
            'engine_interval_days' => 'required|integer|min:0',
            'gear_interval_km' => 'required|integer|min:0',
            'gear_interval_days' => 'required|integer|min:0',
        ]);

        $vehicle = Vehicle::create($validated);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Vehicle registered successfully'
        ]);
    }

    /**
     * Update the specified vehicle.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'plate_number' => 'required|unique:vehicles,plate_number,' . $vehicle->id,
            'brand' => 'required|string',
            'model' => 'required|string',
            'description' => 'nullable|string',
            'color' => 'nullable|string',
            'engine_type' => 'required|in:matic,manual',
            'engine_interval_km' => 'required|integer|min:0',
            'engine_interval_days' => 'required|integer|min:0',
            'gear_interval_km' => 'required|integer|min:0',
            'gear_interval_days' => 'required|integer|min:0',
            'security_code' => 'nullable|string|max:50', // Allow manual update
        ]);

        $vehicle->update($validated);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Vehicle data updated successfully'
        ]);
    }

    /**
     * Remove the specified vehicle.
     */
    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Vehicle deleted successfully'
        ]);
    }

    /**
     * Get service history for a vehicle.
     */
    public function history(Vehicle $vehicle)
    {
        $history = OilServiceLog::where('vehicle_id', $vehicle->id)
        ->with(['engineOil:id,name', 'gearOil:id,name'])
            ->latest()
            ->get();

        return response()->json($history);
    }
}
