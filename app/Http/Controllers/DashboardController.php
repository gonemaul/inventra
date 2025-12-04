<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Sale;
use Inertia\Inertia;
use App\Models\Purchase;
use App\Models\SmartInsight;
use Illuminate\Http\Request;
use App\Models\PurchaseInvoice;
use App\Services\InsightService;
use Illuminate\Support\Facades\DB;

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
        $yesterday = Carbon::yesterday();
        $todayStats = Sale::whereDate('transaction_date', $today)
            ->selectRaw('
                SUM(total_revenue) as revenue,
                SUM(total_profit) as profit,
                COUNT(*) as count
            ')
            ->first();
        // / Data Kemarin (Untuk Perbandingan)
        $yesterdayStats = Sale::whereDate('transaction_date', $yesterday)
            ->selectRaw('SUM(total_profit) as profit')
            ->first();
        // Hitung Kenaikan/Penurunan Profit
        $todayProfit = $todayStats->profit ?? 0;
        $yesterdayProfit = $yesterdayStats->profit ?? 0;

        $profitTrend = [
            'diff' => $todayProfit - $yesterdayProfit, // Selisih Rupiah
            'percent' => $yesterdayProfit > 0
                ? round((($todayProfit - $yesterdayProfit) / $yesterdayProfit) * 100, 1)
                : 100, // Persentase
            'direction' => ($todayProfit >= $yesterdayProfit) ? 'up' : 'down'
        ];

        $dailyMargin = ($todayStats->revenue > 0)
            ? round(($todayProfit / $todayStats->revenue) * 100, 1)
            : 0;
        // 3. Grafik 7 Hari Terakhir (Untuk Visual Tren)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            // Query per tanggal
            $dailyRev = Sale::whereDate('transaction_date', $date)->sum('total_revenue');

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

        // Data Pembelian dan Keuangan untuk Widget
        $startOfMonth = Carbon::now()->startOfMonth();
        $purchases = [
            'total_spend_month' => Purchase::whereDate('transaction_date', '>=', $startOfMonth)
                ->sum('grand_total'), // Asumsi nama kolom total di tabel purchase adalah 'grand_total' atau 'total_amount'
            'count_pending' => Purchase::whereNotIn('status', [Purchase::STATUS_RECEIVED, Purchase::STATUS_COMPLETED, Purchase::STATUS_CANCELLED])
                ->count(),
            'recent' => Purchase::with('supplier:id,name') // Ambil nama supplier saja biar ringan
                ->latest()
                ->limit(3)
                ->get()
                ->map(function ($po) {
                    return [
                        'id' => $po->id,
                        'reference_no' => $po->reference_no,
                        'supplier' => $po->supplier,
                        'total' => $po->grand_total, // Sesuaikan kolom
                        'status' => $po->status
                    ];
                })
        ];
        $finance = [
            // Total Sisa Hutang (Global)
            'total_debt' => PurchaseInvoice::where('payment_status', '!=', PurchaseInvoice::PAYMENT_STATUS_PAID)
                ->sum(DB::raw('total_amount - amount_paid')),

            // Jumlah Nota Jatuh Tempo dalam 7 Hari
            'due_soon_count' => PurchaseInvoice::where('payment_status', '!=', PurchaseInvoice::PAYMENT_STATUS_PAID)
                ->whereBetween('due_date', [now(), now()->addDays(7)])
                ->count(),

            // 3 Tagihan Mendesak (Urut berdasarkan Jatuh Tempo terdekat)
            'recent_bills' => PurchaseInvoice::with('purchase.supplier:id,name')
                ->where('payment_status', '!=', PurchaseInvoice::PAYMENT_STATUS_PAID)
                ->orderBy('due_date', 'asc') // Prioritas yang mau jatuh tempo
                ->limit(3)
                ->get()
        ];
        return Inertia::render('Dashboard/Dashboard', [
            'stats' => [
                'revenue' => $todayStats->revenue ?? 0,
                'profit'  => $todayStats->profit ?? 0,
                'transactions' => $todayStats->count ?? 0,
                'profit_trend' => $profitTrend,
                'daily_margin' => $dailyMargin
            ],
            'health' => $health,
            'cashflow' => $cashflow,
            'insights' => $insights,
            'chart_data' => $chartData,
            'recent_sales' => $recentSales,
            'purchases' => $purchases,
            'finance' => $finance,
        ]);
    }
}
