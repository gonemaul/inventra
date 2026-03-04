<?php

namespace App\Http\Controllers;

use App\Models\SmartInsight;
use App\Services\InsightService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmartInsightController extends Controller
{
    /**
     * Display the DSS Smart Insights Dashboard.
     */
    public function index(Request $request)
    {
        $query = SmartInsight::with('product:id,name,slug,image_path');

        // Filter by Severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by Type (Strict match)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by Product Name or Message
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting Logic
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'date_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'severity':
                    $query->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 WHEN 'info' THEN 3 ELSE 4 END")
                          ->orderBy('created_at', 'desc');
                    break;
                case 'date_desc':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc'); // Default
        }

        $insights = $query->paginate(20)->withQueryString();

        // Calculate summary metrics for the dashboard header
        $metrics = [
            'critical' => SmartInsight::where('severity', 'critical')->count(),
            'warning' => SmartInsight::where('severity', 'warning')->count(),
            'info' => SmartInsight::where('severity', 'info')->count(),
            'total' => SmartInsight::count(),
        ];

        return Inertia::render('Reports/SmartInsights', [
            'insights' => $insights,
            'metrics' => $metrics,
            'filters' => $request->only(['severity', 'type', 'search', 'sort']),
        ]);
    }

    /**
     * Trigger manual calculation of the Decision Support System.
     */
    public function analyze(InsightService $service)
    {
        try {
            // Run the scheduled analysis manually
            $service->runScheduledAnalysis();

            return redirect()->back()->with('success', 'Analisa DSS berhasil dijalankan. Data insight terbaru telah diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menjalankan analisa DSS: ' . $e->getMessage());
        }
    }

    /**
     * Export insights to CSV format.
     */
    public function export(Request $request)
    {
        $query = SmartInsight::with('product:id,name,slug,image_path');

        // Apply filters identical to index
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting Logic
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'date_asc':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'severity':
                    $query->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 WHEN 'info' THEN 3 ELSE 4 END")
                          ->orderBy('created_at', 'desc');
                    break;
                case 'date_desc':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            $query->orderBy('created_at', 'desc'); // Default
        }

        $insights = $query->get();

        $filename = "DSS_SmartInsights_" . date('Ymd_His') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($insights) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'ID', 'Tanggal', 'Tingkat Urgensi', 'Tipe', 'Produk', 'Judul Insight', 'Pesan Rekomendasi'
            ]);

            foreach ($insights as $row) {
                fputcsv($file, [
                    $row->id,
                    $row->created_at->format('Y-m-d H:i:s'),
                    strtoupper($row->severity),
                    strtoupper(str_replace('_', ' ', $row->type)),
                    $row->product ? $row->product->name : 'N/A',
                    $row->title,
                    $row->message,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
