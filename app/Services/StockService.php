<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SmartInsight;
use App\Models\StockMovement;
use App\Services\Analysis\Product\FinancialAnalyzer;
use App\Services\Analysis\Product\InventoryAnalyzer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class StockService
{
    protected $inventoryAnalyzer;

    protected $financialAnalyzer;

    public function __construct(InventoryAnalyzer $inventoryAnalyzer, FinancialAnalyzer $financialAnalyzer)
    {
        $this->inventoryAnalyzer = $inventoryAnalyzer;
        $this->financialAnalyzer = $financialAnalyzer;
    }

    /**
     * Mencatat Perubahan Stok
     *
     * * @param int $productId
     * @param  int  $qty  (Positif = Masuk, Negatif = Keluar)
     * @param  string  $type  ('sale', 'purchase', 'adjustment', etc)
     * @param  string|null  $ref  (No Referensi Dokumen)
     * @param  string|null  $desc  (Catatan Tambahan)
     */
    public function record($productId, $qty, $type, $ref = null, $desc = null)
    {
        // Pastikan update stok dan catat log terjadi bersamaan (Atomic)
        // Gunakan try-catch di controller pemanggil untuk handle error

        $product = Product::findOrFail($productId);

        $stockBefore = $product->stock;
        $stockAfter = $qty;

        if ($type == StockMovement::TYPE_INITIAL) {
            $metrics = $this->financialAnalyzer->calculate($product);
            SmartInsight::updateOrCreate(
                ['product_id' => $product->id, 'type' => SmartInsight::TYPE_NEW],
                [
                    'severity' => SmartInsight::SEVERITY_INFO, // HARDCODED CONSTANT
                    'title' => 'Produk Baru: '.$product->name,
                    'message' => "Produk baru ditambahkan {$metrics['lifecycle']['days_active']} hari lalu.",
                    'payload' => $metrics['lifecycle'],
                    'action_url' => '/products/'.$product->id,
                    'updated_at' => now(),
                ]
            );
        }
        // 1. Update Master Stok di Produk
        if ($type != StockMovement::TYPE_INITIAL) {
            // tambah stok
            if (in_array($type, [StockMovement::TYPE_ADJUSTMENT_IN, StockMovement::TYPE_PURCHASE, StockMovement::TYPE_RETURN_IN])) {
                $stockAfter = $stockBefore + $qty;
            }
            // rubah stok(timpa)
            elseif (in_array($type, [StockMovement::TYPE_ADJUSTMENT_OPNAME])) {
                $stockAfter = $qty;
                $qty = $qty > $stockBefore ? $qty - $stockBefore : $stockBefore - $qty;
                $analysis = $this->inventoryAnalyzer->calculateInventoryHealth($product);

                if (in_array($analysis['status'], [SmartInsight::SEVERITY_CRITICAL, SmartInsight::SEVERITY_WARNING])) {
                    $this->sendLowStock($product, $analysis);
                }
            }
            // kurangi stock
            elseif (in_array($type, [StockMovement::TYPE_ADJUSTMENT_OUT, StockMovement::TYPE_SALE, StockMovement::TYPE_RETURN_OUT])) {
                $stockAfter = $stockBefore - $qty;
                $analysis = $this->inventoryAnalyzer->calculateInventoryHealth($product);

                if (in_array($analysis['status'], [SmartInsight::SEVERITY_CRITICAL, SmartInsight::SEVERITY_WARNING])) {
                    $this->sendLowStock($product, $analysis);
                }
            }
            $product->stock = $stockAfter;
        }
        $product->save();

        // 2. Catat di Buku Sejarah (Stock Movement)
        StockMovement::create([
            'product_id' => $productId,
            'user_id' => Auth::id() ?? 'system', // Handle jika dijalankan seeder/system
            'type' => $type,
            'reference_number' => $ref,
            'quantity' => $qty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'cost_price' => $product->purchase_price, // Kita simpan HPP saat kejadian ini
            'description' => $desc,
        ]);

        return $stockAfter;
    }

    public function sendLowStock($product, $analysis)
    {
        if (! Cache::has('notif_critical_'.$product->id)) {

            // A. Buat Pesan Singkat & Padat (Actionable)
            $msg = "⚠️ <b>STOK KRITIS ALERT!</b>\n\n";
            $msg .= "📦 <b>{$product->name}</b>\n";
            $msg .= "Sisa Stok: <b>{$analysis['current_stock']} {$product->unit->name}</b>\n";
            $msg .= "Habis dalam: <b>{$analysis['days_left']} hari</b>\nPada Tgl: {$analysis['stockout_date']}\n";
            $msg .= "<i>Saran: Segera order {$analysis['suggested_qty']} pcs</i>";

            // B. Kirim Telegram Langsung
            TelegramService::send($msg);

            // C. Simpan ke Tabel Insight (Sebagai Log History)
            // Kita set is_notified = 1 karena sudah dikirim barusan.
            // Jadi besok pagi tidak perlu dikirim ulang di laporan rangkuman, cukup jadi arsip.
            SmartInsight::updateOrCreate([
                'product_id' => $product->id,
                'type' => SmartInsight::TYPE_RESTOCK,
                'severity' => SmartInsight::SEVERITY_CRITICAL,
                'title' => 'Stok Kritis (Realtime)',
                'message' => $analysis['message'],
                'payload' => $analysis, // Simpan semua data analisa lengkap di sini
                'action_url' => route('products.show', $product->id),
                'is_read' => false,
                'is_notified' => true, // <--- PENTING: Agar tidak dobel notif besok pagi
            ]);
            Cache::put('notif_critical_'.$product->id, true, now()->addHours(6));
        }
    }
}
