<?php

namespace App\Jobs;

use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Data yang diterima formatnya:
     * [
     * product_id_1 => qty_1,
     * product_id_2 => qty_2
     * ]
     */
    public function __construct(protected array $stockData)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(StockService $stockService): void
    {
        foreach ($this->stockData as $productId => $qty) {
            $stockService->record(
                productId: $productId,
                qty: $qty,
                type: StockMovement::TYPE_INITIAL,
                ref: 'INIT',
                desc: 'Stok Awal Import Produk Baru'
            );
        }
    }
}
