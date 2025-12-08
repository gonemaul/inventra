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
        Schema::table('products', function (Blueprint $table) {
            // Kolom untuk DSS dan Strategi Bisnis
            // $table->string('inventory_type')->default('FAST')->after('min_stock')->comment('FAST/SLOW/SEASONAL');
            $table->decimal('target_margin_percent', 5, 2)->default(20.00)->after('selling_price')->comment('Target Margin (%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('target_margin_percent');
            $table->dropColumn('inventory_type');
        });
    }
};
