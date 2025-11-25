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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories') // Merujuk ke tabel 'categories'
                ->onDelete('restrict'); // Mencegah penghapusan kategori jika masih memiliki produk
            $table->foreignId('unit_id')
                ->nullable() // Mungkin ada produk tanpa satuan (opsional)
                ->constrained('units') // Merujuk ke tabel 'units'
                ->onDelete('restrict');
            $table->foreignId('size_id')
                ->nullable() // Mungkin ada produk tanpa ukuran (opsional)
                ->constrained('sizes') // Merujuk ke tabel 'sizes'
                ->onDelete('restrict');
            $table->foreignId('supplier_id')
                ->constrained('suppliers') // Merujuk ke tabel 'suppliers'
                ->onDelete('set null');
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->decimal('purchase_price', 15, 2)->default(0)->comment('Harga Beli');
            $table->decimal('selling_price', 15, 2)->default(0)->comment('Harga Jual');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('status');
            $table->softDeletes(); // Menambahkan kolom deleted_at untuk soft deletes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
