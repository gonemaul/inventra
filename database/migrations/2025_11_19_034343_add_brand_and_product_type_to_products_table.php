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
            $table->string('slug')->unique()->after('code');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null')->after('supplier_id');
            $table->foreignId('product_type_id')->nullable()->constrained('product_types')->onDelete('set null')->after('brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_type_id']);
            $table->dropForeign(['brand_id']);

            $table->dropColumn('slug');
            $table->dropColumn('product_type_id');
            $table->dropColumn('brand_id');
        });
    }
};
