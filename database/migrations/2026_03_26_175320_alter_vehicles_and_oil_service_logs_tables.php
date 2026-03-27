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
        // 1. Penyesuaian Tabel Vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            // Menambah kolom warna setelah model
            $table->string('color')->nullable()->after('model');
            
            // Rename kolom interval yang lama (Laravel 10+ sudah support native rename)
            $table->renameColumn('service_interval_km', 'engine_interval_km');
            $table->renameColumn('service_interval_days', 'engine_interval_days');
        });

        // Menambah kolom interval gardan (harus dipisah eksekusinya setelah rename jika pakai SQLite/versi DB tertentu)
        Schema::table('vehicles', function (Blueprint $table) {
            $table->integer('gear_interval_km')->default(6000)->after('engine_interval_days');
            $table->integer('gear_interval_days')->default(180)->after('gear_interval_km');
        });

        // 2. Penyesuaian Tabel Oil Service Logs
        Schema::table('oil_service_logs', function (Blueprint $table) {
            // Menambah relasi ke transaksi POS dan tanggal servis aktual
            $table->date('service_date')->nullable()->after('transaction_id'); // Nullable dulu agar data lama tidak error
            
            // Rename target jadwal lama menjadi khusus mesin
            $table->renameColumn('next_service_date', 'next_engine_oil_date');
            $table->renameColumn('next_service_km', 'next_engine_oil_km');
        });

        // Menambah target jadwal khusus gardan
        Schema::table('oil_service_logs', function (Blueprint $table) {
            $table->date('next_gear_oil_date')->nullable()->after('next_engine_oil_km');
            $table->integer('next_gear_oil_km')->nullable()->after('next_gear_oil_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback tabel oil_service_logs
        Schema::table('oil_service_logs', function (Blueprint $table) {
            $table->dropColumn(['service_date', 'next_gear_oil_date', 'next_gear_oil_km']);
            $table->renameColumn('next_engine_oil_date', 'next_service_date');
            $table->renameColumn('next_engine_oil_km', 'next_service_km');
        });

        // Rollback tabel vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['color', 'gear_interval_km', 'gear_interval_days']);
            $table->renameColumn('engine_interval_km', 'service_interval_km');
            $table->renameColumn('engine_interval_days', 'service_interval_days');
        });
    }
};
