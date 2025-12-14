<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->boolean('is_decimal')->default(false)->after('name');
            // Default false artinya dianggap barang satuan (integer)
        });

        // 2. Tambah Diskon & Notes di Transactions
        Schema::table('sales', callback: function (Blueprint $table) {
            // Simpan info diskon
            $table->enum('discount_type', ['fixed', 'percent'])->nullable()->after('total_amount'); // Tipe diskon
            $table->decimal('discount_value', 19, 4)->default(0)->after('discount_type'); // Input user (misal: 10 atau 5000)
            $table->decimal('discount_total', 19, 4)->default(0)->after('discount_value'); // Hasil rupiah diskon

            // total_amount di transaction biasanya adalah Grand Total (setelah diskon)
            // Kita butuh subtotal jika belum ada, tapi biasanya total_amount sudah cukup jika kita simpan diskonnya.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units_and_transactions', function (Blueprint $table) {
            //
        });
    }
};
