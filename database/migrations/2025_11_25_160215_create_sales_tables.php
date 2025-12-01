<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Tabel Header (Mewakili 1 Dokumen Rekap Harian)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique(); // Contoh: POS-241125-01
            $table->date('transaction_date')->index();         // Tanggal Penjualan/Rekap

            // Kolom Finansial (Diisi otomatis oleh sistem)
            $table->decimal('total_revenue', 19, 4)->default(0); // Total Omset
            $table->decimal('total_profit', 19, 4)->default(0);  // Total Laba Bersih

            $table->json('financial_summary');
            $table->foreignId('user_id')->constrained('users');  // Kasir/Admin
            $table->text('notes')->nullable(); // Catatan (misal: "Shift Pagi")

            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Tabel Detail (Barang apa saja yang laku)
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products'); // Relasi ke Stok

            $table->decimal('quantity', 19, 4);

            // --- HARGA DIKUNCI (LOCKING PRICE) ---
            // Kita simpan harga SAAT INI agar profit tidak berubah walau master berubah
            $table->decimal('selling_price', 19, 4);  // Harga Jual ke Customer
            $table->decimal('capital_price', 19, 4);  // HPP / Modal dari Master Produk

            // Hitungan Baris (Disimpan biar query export cepat)
            $table->decimal('subtotal', 19, 4);       // quantity * selling_price
            $table->decimal('profit', 19, 4);         // subtotal - (quantity * capital_price)

            // Snapshot Metadata (Untuk Export Excel masa depan)
            // Menyimpan nama produk, kategori, brand saat transaksi terjadi
            $table->json('product_snapshot')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
