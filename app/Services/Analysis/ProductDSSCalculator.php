<?php

namespace App\Services\Analysis;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SmartInsight;
use App\Services\Analysis\Product\ClassificationAnalyzer;
use App\Services\Analysis\Product\FinancialAnalyzer;
use App\Services\Analysis\Product\InventoryAnalyzer;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;

class ProductDSSCalculator
{
    protected $inventoryAnalyzer;
    protected $financialAnalyzer;
    protected $classificationAnalyzer;

    public function __construct(
        InventoryAnalyzer $inventoryAnalyzer,
        FinancialAnalyzer $financialAnalyzer,
        ClassificationAnalyzer $classificationAnalyzer
    ) {
        $this->inventoryAnalyzer = $inventoryAnalyzer;
        $this->financialAnalyzer = $financialAnalyzer;
        $this->classificationAnalyzer = $classificationAnalyzer;
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
     * LOGIC 3: CLASSIFICATION (ABC/XYZ)
     */
    public function getClassification(Product $product, float $productRevenue, float $totalRevenue, array $monthlyQtys): array
    {
        return $this->classificationAnalyzer->classify($product, $productRevenue, $totalRevenue, $monthlyQtys);
    }

    /**
     * Expose ClassificationAnalyzer for batch queries in InsightService
     */
    public function getClassificationAnalyzer(): ClassificationAnalyzer
    {
        return $this->classificationAnalyzer;
    }

    /**
     * LOGIC 3: ACTION MARGIN ALERT
     * (Notifikasi Telegram Jika Margin Terlalu Rendah)
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

            // DB Saving dihapus untuk menjaga Single Source of Truth.
            // Biarkan InsightService yang menghandle DB saving-nya berdasarkan
            // hasil dari FinancialAnalyzer

            Cache::put($cacheKey, true, now()->addHours(6));
        }
    }
}
