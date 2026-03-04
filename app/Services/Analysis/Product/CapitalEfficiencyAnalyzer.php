<?php

namespace App\Services\Analysis\Product;

use App\Models\Product;
use App\Models\SaleItem;

/**
 * =========================================================================
 * CAPITAL EFFICIENCY ADVISOR (Working Capital Optimizer)
 * =========================================================================
 * Menganalisa seberapa efisien modal yang tertanam di stok produk ini.
 *
 * Key Metrics:
 * - Inventory Turnover Ratio (ITR): Berapa kali stok "berputar" dalam setahun.
 *   ITR = (Total HPP Terjual) / (Rata-rata Nilai Stok)
 *   Semakin tinggi ITR, semakin efisien. ITR rendah = modal "parkir" sia-sia.
 *
 * - Days Sales of Inventory (DSI): Berapa hari rata-rata stok tersimpan.
 *   DSI = 365 / ITR
 *
 * - Capital Locked: Total rupiah yang saat ini "terkunci" di stok produk ini.
 *   Capital Locked = current_stock × purchase_price
 *
 * - Actionability Score: Seberapa mendesak intervensi modal diperlukan.
 */
class CapitalEfficiencyAnalyzer
{
    /**
     * Menghitung metrik efisiensi modal untuk satu produk.
     */
    public function analyze(Product $product): array
    {
        $currentStock  = (int) $product->stock;
        $purchasePrice = (float) $product->purchase_price;

        // Total modal yang saat ini "parkir" di stok produk ini
        $capitalLocked = $currentStock * $purchasePrice;

        // --- INVENTORY TURNOVER RATIO ---
        // Gunakan data 90 hari (3 bulan) dan annualize untuk estimasi tahunan
        $qtySold90Days = SaleItem::where('sale_items.product_id', $product->id)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.transaction_date', '>=', now()->subDays(90))
            ->sum('sale_items.quantity') ?? 0;

        // HPP yang terjual dalam 90 hari (annualize ke 365 hari)
        $cogsLast90 = $qtySold90Days * $purchasePrice;
        $cogsAnnualized = $cogsLast90 * (365 / 90);

        // Nilai stok rata-rata (approximasi dengan stok saat ini)
        $avgInventoryValue = $capitalLocked > 0 ? $capitalLocked : 1; // Hindari div by zero

        $itr = $cogsAnnualized > 0 ? round($cogsAnnualized / $avgInventoryValue, 2) : 0;
        $dsi = $itr > 0 ? round(365 / $itr, 0) : 999; // Days Sales of Inventory

        // --- EFISIENSI KLASIFIKASI ---
        // ITR < 2 dalam setahun = lamban sekali (butuh > 6 bulan untuk 1 putaran)
        // ITR 2–6  = Normal untuk ritel FMCG
        // ITR 6–12 = Baik
        // ITR > 12 = Sangat efisien (stok berganti > 1x/bulan)

        $efficiencyLabel = 'Sangat Efisien';
        $efficiencyScore = 'A';
        $isActionable = false;
        $recommendation = '';

        if ($itr === 0) {
            $efficiencyLabel = 'Tidak Ada Penjualan';
            $efficiencyScore = 'F';
            $isActionable = $capitalLocked > 0;
            $recommendation = $capitalLocked > 0
                ? 'Produk ini tidak laku sama sekali dalam 90 hari terakhir, namun modal Rp '.number_format($capitalLocked, 0, ',', '.').' masih tertahan di stok.'
                : 'Stok kosong dan tidak ada penjualan.';
        } elseif ($itr < 2) {
            $efficiencyLabel = 'Sangat Lamban (Perlu Perhatian)';
            $efficiencyScore = 'D';
            $isActionable = true;
            $recommendation = 'Modal Rp '.number_format($capitalLocked, 0, ',', '.').' tertahan rata-rata '.$dsi.' hari. Pertimbangkan promosi atau kurangi safety stock untuk meningkatkan perputaran modal.';
        } elseif ($itr < 4) {
            $efficiencyLabel = 'Lambat';
            $efficiencyScore = 'C';
            $isActionable = $capitalLocked > 500000; // Hanya actionable jika nilai modalnya signifikan
            $recommendation = 'Turnover {$itr}x/tahun masih di bawah rata-rata. Modal Rp '.number_format($capitalLocked, 0, ',', '.').' bisa lebih produktif.';
        } elseif ($itr < 8) {
            $efficiencyLabel = 'Normal';
            $efficiencyScore = 'B';
        } else {
            $efficiencyLabel = 'Efisien';
            $efficiencyScore = 'A';
        }

        // Cost of Holding (biaya menyimpan modal per bulan)
        // Asumsi biaya modal / opportunity cost = 1% per bulan dari nilai stok
        $holdingCostPerMonth = $capitalLocked * 0.01;

        return [
            'capital_locked'      => $capitalLocked,
            'qty_sold_90_days'    => (int) $qtySold90Days,
            'cogs_annualized'     => round($cogsAnnualized, 0),
            'itr'                 => $itr,          // Inventory Turnover Ratio
            'dsi'                 => $dsi,          // Days Sales of Inventory
            'efficiency_label'    => $efficiencyLabel,
            'efficiency_score'    => $efficiencyScore,   // A, B, C, D, F
            'holding_cost_monthly' => round($holdingCostPerMonth, 0),
            'is_actionable'       => $isActionable,
            'recommendation'      => $recommendation,
        ];
    }
}
