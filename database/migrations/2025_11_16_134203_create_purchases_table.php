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
            $table->date('transaction_date');
            $table->string('status')->comment('draft, dipesan, dikirim, diterima, checking, selesai, dibatalkan');

            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('other_costs', 15, 2)->default(0);
            $table->date('received_at')->nullable()->comment('Tanggal barang fisik tiba');
            $table->string('supplier_reference')->nullable()->comment('No Resi/SO Supplier');

            $table->text('notes')->nullable();
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
