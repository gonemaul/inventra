<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sale;
use Inertia\Inertia;
use App\Models\SmartInsight;
use Illuminate\Http\Request;
use App\Services\InsightService;

class DashboardController extends Controller
{
    public function index(InsightService $insightService)
    {
        // 1. Trigger Analisa DSS (Stok & Keuangan)
        $insightService->runAnalysis();
        $health = $insightService->calculateShopHealth();
        $cashflow = $insightService->predictCashflow();

        // 2. Data Statistik Hari Ini (Omzet & Profit)
        // Menggunakan tabel 'sales' dan kolom 'transaction_date'
        $today = Carbon::today();

        $todayStats = Sale::whereDate('transaction_date', $today)
            ->selectRaw('
                SUM(total_revenue) as revenue,
                SUM(total_profit) as profit,
                COUNT(*) as count
            ')
            ->first();

        // 3. Grafik 7 Hari Terakhir (Untuk Visual Tren)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            // Query per tanggal
            $dailyRev = Sale::whereDate('transaction_date', $date)->sum('total_price');

            $chartData[] = [
                'day' => $date->isoFormat('dd'), // Senin, Selasa
                'full_date' => $date->format('d M'),
                'value' => $dailyRev
            ];
        }

        // 4. Action Cards / Insights (DSS)
        // Prioritas: Critical -> Warning -> Info
        $insights = SmartInsight::where('is_read', false)
            ->orderByRaw("CASE
                WHEN severity = 'critical' THEN 1
                WHEN severity = 'warning' THEN 2
                WHEN severity = 'info' THEN 3
                ELSE 4 END")
            ->latest()
            ->limit(5)
            ->get();

        // 5. Transaksi Terakhir (List Table)
        $recentSales = Sale::with(['items.product']) // Eager load jika perlu
            ->latest('transaction_date')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'revenue' => $todayStats->revenue ?? 0,
                'profit'  => $todayStats->profit ?? 0,
                'transactions' => $todayStats->count ?? 0,
            ],
            'health' => $health,
            'cashflow' => $cashflow,
            'insights' => $insights,
            'chart_data' => $chartData,
            'recent_sales' => $recentSales
        ]);
    }
}
