<?php

namespace App\Services\Analysis;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\SaleItem;
use App\Services\Analysis\Product\FinancialAnalyzer;
use App\Services\Analysis\Product\InventoryAnalyzer;

class ProductDSSCalculator
{
    protected $inventoryAnalyzer;
    protected $financialAnalyzer;
    public function __construct(InventoryAnalyzer $inventoryAnalyzer, FinancialAnalyzer $financialAnalyzer)
    {
        $this->inventoryAnalyzer = $inventoryAnalyzer;
        $this->financialAnalyzer = $financialAnalyzer;
    }
    /**
     * =========================================================================
     * PUBLIC API: PANGGIL INI DARI CONTROLLER / SERVICE LAIN
     * =========================================================================
     */

    /**
     * Mengambil SEMUA analisa (Inventory & Financial) dalam satu paket.
     */
    public function getFullAnalysis(Product $product): array
    {
        return [
            'inventory' => $this->calculateInventoryHealth($product),
            'financial' => $this->calculateFinancialHealth($product),
        ];
    }
    /**
     * LOGIC 1: INVENTORY & STOCK HEALTH
     * (Forecasting, Restock Suggestion, Dead Stock)
     */
    public function calculateInventoryHealth(Product $product): array
    {
        return $this->inventoryAnalyzer->calculateInventoryHealth($product);
    }

    /**
     * LOGIC 2: FINANCIAL HEALTH
     * (Profit Analysis, Margin Alert, Price Trend)
     */
    public function calculateFinancialHealth(Product $product): array
    {
        return $this->financialAnalyzer->calculate($product);
    }

    /**
     * LOGIC 3: TREND WATCHER
     * (Fast Moving Detection)
     */
    public function calculateTrendHealth(Product $product): array
    {
        $thisMonthStart = now()->subDays(30);
        $lastMonthStart = now()->subDays(60);

        // Hitung Qty Bulan Ini
        $qtyThisMonth = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn($q) => $q->where('transaction_date', '>=', $thisMonthStart))
            ->sum('quantity');

        // Hitung Qty Bulan Lalu
        $qtyLastMonth = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn($q) => $q->whereBetween('transaction_date', [$lastMonthStart, $thisMonthStart]))
            ->sum('quantity');

        $isTrending = false;
        $growth = 0;
        $message = '';

        // Rule Trending: Laku > 10 pcs & Kenaikan > 30%
        if ($qtyThisMonth >= 10 && $qtyThisMonth > ($qtyLastMonth * 1.3)) {
            $isTrending = true;
            $growth = $qtyLastMonth > 0
                ? round((($qtyThisMonth - $qtyLastMonth) / $qtyLastMonth) * 100)
                : 100; // New star

            $message = "Penjualan naik {$growth}% (Total: {$qtyThisMonth} pcs).";
        }

        return [
            'is_trending' => $isTrending,
            'growth_percent' => $growth,
            'qty_now' => $qtyThisMonth,
            'qty_prev' => $qtyLastMonth,
            'message' => $message
        ];
    }
}
