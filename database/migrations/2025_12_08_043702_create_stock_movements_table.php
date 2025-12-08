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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            // Relasi ke Produk
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Relasi ke User (Siapa yang melakukan aksi?)
            $table->foreignId('user_id')->nullable();

            // Jenis Mutasi (Penting untuk filter laporan)
            // Contoh: 'initial', 'sale', 'purchase', 'correction', 'return_in', 'return_out'
            $table->string('type', 50)->index();

            // Referensi Dokumen (Supaya bisa dilacak sumbernya)
            // Contoh: 'INV-2024/001' atau 'PO-AGUS/XII/20'
            $table->string('reference_number')->nullable()->index();

            // Jumlah Mutasi (Positif = Masuk, Negatif = Keluar)
            $table->integer('quantity');

            // Snapshot Stok (Untuk audit trail/kartu stok)
            $table->integer('stock_before');
            $table->integer('stock_after');

            // Snapshot Nilai HPP (Penting untuk Laba Rugi nanti)
            $table->decimal('cost_price', 19, 4)->default(0);

            $table->text('description')->nullable();
            $table->timestamps();

            // Indexing tambahan untuk performa report
            $table->index(['product_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
