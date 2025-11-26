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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            $table->string('reference_no')->unique()->comment('Nomor Transaksi Internal (TRX-001)');
            $table->date('transaction_date')->index();
            $table->string('status')->comment('draft, dipesan, dikirim, diterima, checking, selesai, dibatalkan')->index();

            $table->date('received_at')->nullable()->comment('Tanggal barang fisik tiba');
            $table->string('supplier_reference')->nullable()->comment('No Resi/SO Supplier');

            $table->text('notes')->nullable();

            // --- STRUKTUR KEUANGAN (Hybrid) ---
            // Gunakan Kolom Decimal agar mudah dihitung SQL
            $table->decimal('total_item_price', 19, 4)->default(0); // Total harga barang saja
            $table->decimal('shipping_cost', 19, 4)->default(0);    // Ongkir
            $table->decimal('other_costs', 19, 4)->default(0);
            $table->decimal('grand_total', 19, 4)->default(0);      // Total Akhir (Items + Ongkir + Lain)
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
