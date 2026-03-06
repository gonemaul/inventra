<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\SmartInsight;
use App\Services\Analysis\ProductDSSCalculator;
use App\Services\InsightService;
use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class AnalyzePostSaleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $productIds;

    /**
     * Create a new job instance.
     */
    public function __construct(array $productIds)
    {
        $this->productIds = array_unique($productIds);
    }

    /**
     * Execute the job.
     */
    public function handle(ProductDSSCalculator $calculator, InsightService $insightService, TelegramService $telegramService): void
    {
        if (empty($this->productIds)) {
            return;
        }

        $criticalAlerts = [];

        // Load targeted products
        Product::whereIn('id', $this->productIds)->chunk(50, function ($products) use ($calculator, $insightService, &$criticalAlerts) {
            foreach ($products as $product) {
                // 1. Calculate Inventory Health silently
                $analysis = $calculator->calculateInventoryHealth($product);

                // 2. Persist to SmartInsight Database, MUTING the individual Telegram message
                $insightService->processRestockInsight($product, $analysis, true);

                // 3. Pack Critical insights into array
                if ($analysis['status'] === SmartInsight::SEVERITY_CRITICAL) {
                    $cacheKey = 'notif_restock_critical_grouped_' . $product->id;
                    if (!Cache::has($cacheKey)) {
                        $daysLeft = $analysis['days_left'] ?? '?';
                        $stockNow = $analysis['stock'] ?? $product->stock ?? '?';
                        $avgDaily = $analysis['avg_daily'] ?? '?';

                        $criticalAlerts[] = "📦 <b>{$product->name}</b>\n📉 Stok: {$stockNow} pcs | ⏰ Sisa: ~{$daysLeft} hari | 📈 Jual: {$avgDaily} pcs/hari";

                        // Cache for 6 hours
                        Cache::put($cacheKey, true, now()->addHours(6));
                    }
                }
            }
        });

        // 4. Send ONE Grouped Telegram message if there are critical items
        if (!empty($criticalAlerts)) {
            $msg = "🚨 <b>STOK KRITIS: " . count($criticalAlerts) . " PRODUK HARUS DISEGERAKAN!</b>\n\n";
            $msg .= implode("\n\n", $criticalAlerts);
            $msg .= "\n\n👉 Segera lakukan pembelian di Restock Area.";

            $telegramService->send($msg);
        }
    }
}
