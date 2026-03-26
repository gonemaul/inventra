<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\OilServiceLog;
use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleController extends Controller
{
    protected $maintenanceService;

    public function __construct(MaintenanceService $maintenanceService)
    {
        $this->maintenanceService = $maintenanceService;
    }

    /**
     * Display Customer Hub.
     */
    public function index()
    {
        return Inertia::render('CustomerHub', [
            'vehicles' => Vehicle::latest()->get()
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
            'engine_type' => 'required|in:matic,manual',
            'service_interval_km' => 'required|integer|min:0',
            'service_interval_days' => 'required|integer|min:0',
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
            'engine_type' => 'required|in:matic,manual',
            'service_interval_km' => 'required|integer|min:0',
            'service_interval_days' => 'required|integer|min:0',
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
