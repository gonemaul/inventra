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
        Schema::table('sales', function (Blueprint $table) {
            // 1. Relasi ke Customer (Nullable = Boleh kosong untuk tamu umum)
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete()->after('user_id');

            // 2. Tipe Input (Realtime vs Rekap)
            // Kita pakai string atau enum. String lebih fleksibel.
            // Values: 'realtime' (Kasir HP), 'recap' (Input Malam Laptop)
            $table->string('input_type')->default('realtime')->after('grand_total');

            $table->string('payment_method')->after('input_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            //
        });
    }
};
