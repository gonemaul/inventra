<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan kolom type (default: retail)
        Schema::table('sales', function (Blueprint $table) {
            $table->string('type', 20)->default('retail')->after('input_type');
        });

        // 2. Tambah composite index untuk optimasi query per tipe & tanggal
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['type', 'transaction_date'], 'sales_type_date_index');
        });

        // 3. Backfill: Deteksi transaksi bengkel dari data yang sudah ada.
        //    Logika: Jika sale memiliki sale_items yang produknya berkategori 'Jasa' atau 'Layanan',
        //    maka itu transaksi bengkel. Ini konsisten dengan hasServiceItem() di frontend.
        $bengkelSaleIds = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereIn(DB::raw('LOWER(categories.name)'), ['jasa', 'layanan'])
            ->distinct()
            ->pluck('sale_items.sale_id');

        if ($bengkelSaleIds->isNotEmpty()) {
            DB::table('sales')
                ->whereIn('id', $bengkelSaleIds)
                ->update(['type' => 'bengkel']);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_type_date_index');
            $table->dropColumn('type');
        });
    }
};
