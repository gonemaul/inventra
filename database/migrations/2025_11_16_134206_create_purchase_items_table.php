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
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->onDelete('cascade');
            // Jika nota dihapus, item ini tidak terhapus, hanya link-nya putus
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->onDelete('set null');
            // Relasi ke Produk (nullable), untuk jaga-jaga produk dihapus
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            // Snapshot data produk (permintaan Anda)

            $table->json('product_snapshot')->comment('Snapshot data produk saat pembelian dilakukan');
            $table->decimal('quantity', 19, 4);
            $table->decimal('purchase_price', 19, 4)->comment('Harga beli satuan saat itu');
            $table->decimal('subtotal', 19, 4); // quantity * purchase_price

            $table->decimal('rejected_quantity', 19, 4)->default(0)->comment('Barang rusak/ditolak saat terima');
            $table->string('rejection_note')->nullable()->comment('Alasan barang ditolak');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
