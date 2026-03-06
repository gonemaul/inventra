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

class AnalyzePostPurchaseJob implements ShouldQueue
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

        $marginAlerts = [];

        Product::whereIn('id', $this->productIds)->chunk(50, function ($products) use ($calculator, $insightService, &$marginAlerts) {
            foreach ($products as $product) {
                // 1. Calculate Margin metrics
                $metrics = $calculator->calculateFinancialHealth($product);

                // 2. Persist to DB, MUTING individual telegrams
                $insightService->processMarginInsight($product, $metrics, true);

                // 3. Compile Alerts
                if ($metrics['margin']['is_critical']) {
                    $cacheKey = 'notif_margin_low_grouped_' . $product->id;
                    if (!Cache::has($cacheKey)) {
                        $currentMargin = round($metrics['margin']['percent'], 2);
                        $targetMargin = $product->target_margin_percent;
                        $beli = number_format($product->purchase_price, 0, ',', '.');
                        
                        $smartHint = '';
                        if (isset($metrics['smart_pricing']['has_recommendation']) && $metrics['smart_pricing']['has_recommendation']) {
                            $recPrice = number_format($metrics['smart_pricing']['recommended_price'], 0, ',', '.');
                            $smartHint = "💡 Saran AI: Rp {$recPrice}";
                        }

                        $marginAlerts[] = "📦 <b>{$product->name}</b>\n📉 Profit: {$currentMargin}% (Target: {$targetMargin}%)\n💸 Modal: Rp {$beli} | {$smartHint}";

                        // Cache individual product alert status to avoid spam per 6 hrs
                        Cache::put($cacheKey, true, now()->addHours(6));
                    }
                }
            }
        });

        // 4. Send combined telegram message
        if (!empty($marginAlerts)) {
            $msg = "⚠️ <b>PERINGATAN DARI BARANG MASUK: " . count($marginAlerts) . " PRODUK MENGALAMI PENURUNAN PROFIT!</b>\n";
            $msg .= "Harga modal belanja terbaru menekan margin perusahaan di bawah batas target.\n\n";
            $msg .= implode("\n\n", $marginAlerts);
            $msg .= "\n\nHARAP SESUAIKAN HARGA JUAL!";

            $telegramService->send($msg);
        }
    }
}
