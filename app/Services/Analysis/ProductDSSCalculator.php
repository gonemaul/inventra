<?php

namespace App\Services\Analysis;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SmartInsight;
use App\Services\Analysis\Product\FinancialAnalyzer;
use App\Services\Analysis\Product\InventoryAnalyzer;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;

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
        $thisMonthStart = now()->subDays(value: 30);
        $lastMonthStart = now()->subDays(60);

        // Hitung Qty Bulan Ini
        $qtyThisMonth = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn ($q) => $q->where('transaction_date', '>=', $thisMonthStart))
            ->sum('quantity');

        // Hitung Qty Bulan Lalu
        $qtyLastMonth = SaleItem::where('product_id', $product->id)
            ->whereHas('sale', fn ($q) => $q->whereBetween('transaction_date', [$lastMonthStart, $thisMonthStart]))
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
            'message' => $message,
        ];
    }

    /**
     * LOGIC 4: ACTION MARGIN ALERT
     * (Rekomendasi Naikkan Harga Jika Margin Terlalu Rendah)
     */
    public function sendMarginAlert($product)
    {
        $cacheKey = 'notif_margin_low_'.$product->id;

        if (! Cache::has($cacheKey)) {

            // --- A. FORMAT PESAN TELEGRAM (Update Margin Focus) ---
            $msg = "⚠️ <b>ALERT: PROFIT MARGIN RENDAH!</b>\n\n";
            $msg .= "📦 <b>{$product->name}</b>\n";

            // Baris 1: Margin Aktual vs Target (Highlight Merah/Alert secara visual)
            $currentMargin = $product->current_margin['percent'];
            $targetMargin = $product->target_margin_percent;
            $msg .= "🔻 Margin Saat Ini: <b>{$currentMargin}%</b>\n";
            $msg .= "🎯 Target Margin: <b>{$targetMargin}%</b>\n\n";

            $beli = number_format($product->purchase_price, 0, ',', '.');
            $jual = number_format($product->selling_price, 0, ',', '.');

            $msg .= "📊 <b>Analisa Harga:</b>\n";
            $msg .= "├ Modal: Rp {$beli}\n";
            $msg .= "└ Jual : Rp {$jual}\n\n";

            // Baris 3: Action
            $msg .= '👉 <i>Disarankan untuk melakukan Penyesuaian Harga (Adj Price) segera.</i>';

            // B. Kirim Telegram Langsung
            TelegramService::send($msg);

            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_MARGIN], // TYPE MARGIN
                [
                    'severity' => SmartInsight::SEVERITY_WARNING, // HARDCODED CONSTANT
                    'title' => 'Margin Menipis: '.$product->name,
                    'message' => "Margin drop ke {$currentMargin}% (Target: {$targetMargin}%). Modal naik menjadi Rp {$beli}.",
                    'payload' => [
                        'purchase_price' => $product->purchase_price,
                        'selling_price' => $product->selling_price,
                        'current_margin' => $product->current_margin,
                    ],
                    'action_url' => '/products/'.$product->id.'/edit',
                    'updated_at' => now(),
                    'is_read' => false,
                    'is_notified' => true,
                ]
            );
            Cache::put($cacheKey, true, now()->addHours(6));
        }
    }
}
