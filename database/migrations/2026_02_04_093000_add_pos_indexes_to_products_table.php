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
            // Composite Index untuk Filter Kategori + Sorting Nama (Query paling sering di POS)
            // "SELECT * FROM products WHERE category_id = ? ORDER BY name ASC"
            $table->index(['category_id', 'name'], 'idx_category_name');
            
            // Composite Index untuk Filter SubKategori + Sorting Nama
             $table->index(['product_type_id', 'name'], 'idx_type_name');
            
             // Index tunggal product_type_id (jika belum ada, karena foreign key biasanya auto, tapi explicit lebih aman)
             // $table->index('product_type_id'); // Sudah dicover composite index (left-prefix rule)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_category_name');
            $table->dropIndex('idx_type_name');
        });
    }
};
