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
        // 1. Trigger Analisa DSS (Service Logic - diasumsikan sudah optimal)
        $health = $insightService->calculateShopHealth();
        $cashflow = $insightService->predictCashflow();

        // Setup Tanggal
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $sevenDaysAgo = Carbon::today()->subDays(6);
        $startOfMonth = Carbon::now()->startOfMonth();

        // ==========================================
        // 2. OPTIMASI: STATISTIK PENJUALAN (1 Query)
        // ==========================================
        // Mengambil data Hari Ini & Kemarin dalam satu tarikan database
        $salesStats = Sale::toBase()
            ->whereBetween('transaction_date', [$yesterday->startOfDay(), $today->endOfDay()])
            ->selectRaw("
            -- Data Hari Ini
            SUM(CASE WHEN DATE(transaction_date) = ? THEN total_revenue ELSE 0 END) as today_revenue,
            SUM(CASE WHEN DATE(transaction_date) = ? THEN total_profit ELSE 0 END) as today_profit,
            COUNT(CASE WHEN DATE(transaction_date) = ? THEN 1 END) as today_count,

            -- Data Kemarin (Hanya butuh profit untuk perbandingan)
            SUM(CASE WHEN DATE(transaction_date) = ? THEN total_profit ELSE 0 END) as yesterday_profit
        ", [$today->toDateString(), $today->toDateString(), $today->toDateString(), $yesterday->toDateString()])
            ->first();

        // Mapping Variable agar logic perhitungan di bawah tidak berubah
        $todayRevenue = $salesStats->today_revenue ?? 0;
        $todayProfit = $salesStats->today_profit ?? 0;
        $todayCount = $salesStats->today_count ?? 0;
        $yesterdayProfit = $salesStats->yesterday_profit ?? 0;

        // Logic Hitungan (Tidak berubah)
        $profitTrend = [
            'diff' => $todayProfit - $yesterdayProfit,
            'percent' => $yesterdayProfit > 0
                ? round((($todayProfit - $yesterdayProfit) / $yesterdayProfit) * 100, 1)
                : 100,
            'direction' => ($todayProfit >= $yesterdayProfit) ? 'up' : 'down'
        ];

        $dailyMargin = ($todayRevenue > 0)
            ? round(($todayProfit / $todayRevenue) * 100, 1)
            : 0;

        // ==========================================
        // 3. OPTIMASI: CHART 7 HARI (1 Query)
        // ==========================================
        // Mencegah N+1 Query dalam for loop
        $rawChartData = Sale::toBase()
            ->whereDate('transaction_date', '>=', $sevenDaysAgo)
            ->groupByRaw('DATE(transaction_date)')
            ->selectRaw('DATE(transaction_date) as date, SUM(total_revenue) as total')
            ->pluck('total', 'date'); // Hasil: ['2023-10-01' => 500000, ...]

        // Mapping ulang di PHP (Cepat & Ringan)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateString = $date->toDateString();

            $chartData[] = [
                'day' => $date->isoFormat('dd'),
                'full_date' => $date->format('d M'),
                'value' => $rawChartData[$dateString] ?? 0 // Ambil dari hasil query atau 0
            ];
        }

        // ==========================================
        // 4. INSIGHTS & RECENT SALES
        // ==========================================
        // Action Cards
        $insights = SmartInsight::where('is_read', false)
            ->orderByRaw("CASE
            WHEN severity = 'critical' THEN 1
            WHEN severity = 'warning' THEN 2
            WHEN severity = 'info' THEN 3
            ELSE 4 END")
            ->latest()
            ->limit(5)
            ->get(); // Tetap get() karena mungkin butuh akses accessor di frontend

        // Recent Sales
        $recentSales = Sale::with(['items.product:id,name,code']) // Optimasi: Select kolom spesifik produk
            ->latest('transaction_date')
            ->limit(5)
            ->get();

        // ==========================================
        // 5. DATA PURCHASES (Widget)
        // ==========================================
        $purchases = [
            // Gunakan toBase() untuk agregat sederhana
            'total_spend_month' => Purchase::toBase()
                ->whereDate('transaction_date', '>=', $startOfMonth)
                ->sum('grand_total'),

            'count_pending' => Purchase::toBase()
                ->whereNotIn('status', [Purchase::STATUS_RECEIVED, Purchase::STATUS_COMPLETED, Purchase::STATUS_CANCELLED])
                ->count(),

            'recent' => Purchase::with('supplier:id,name')
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn($po) => [
                    'id' => $po->id,
                    'reference_no' => $po->reference_no,
                    'supplier' => $po->supplier,
                    'total' => $po->grand_total,
                    'status' => $po->status
                ])
        ];

        // ==========================================
        // 6. FINANCE (Sudah Optimal)
        // ==========================================
        $statsFinance = PurchaseInvoice::toBase()
            ->join('purchases', 'purchase_invoices.purchase_id', '=', 'purchases.id')
            ->where('purchases.status', Purchase::STATUS_COMPLETED)
            ->where('purchase_invoices.payment_status', '!=', PurchaseInvoice::PAYMENT_STATUS_PAID)
            ->selectRaw("
            SUM(purchase_invoices.total_amount - purchase_invoices.amount_paid) as total_debt,
            COUNT(CASE WHEN purchase_invoices.due_date BETWEEN ? AND ? THEN 1 END) as due_soon_count
        ", [now(), now()->addDays(7)])
            ->first();

        $recentBills = PurchaseInvoice::with('purchase.supplier:id,name')
            ->whereRelation('purchase', 'status', Purchase::STATUS_COMPLETED)
            ->where('payment_status', '!=', PurchaseInvoice::PAYMENT_STATUS_PAID)
            ->orderBy('due_date', 'asc')
            ->limit(3)
            ->get();

        $finance = [
            'total_debt'     => $statsFinance->total_debt ?? 0,
            'due_soon_count' => $statsFinance->due_soon_count ?? 0,
            'recent_bills'   => $recentBills
        ];

        // ==========================================
        // RETURN
        // ==========================================
        return Inertia::render('Dashboard/Dashboard', [
            'stats' => [
                'revenue' => $todayRevenue,
                'profit'  => $todayProfit,
                'transactions' => $todayCount,
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
