<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oil_service_logs', function (Blueprint $table) {
            // 1. Add sale_id FK so we can link service logs to sales
            $table->foreignId('sale_id')->nullable()->after('id')->constrained('sales')->nullOnDelete();

            // 2. Make engine_oil_id nullable (not always selected)
            $table->foreignId('engine_oil_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('oil_service_logs', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn('sale_id');
        });
    }
};
