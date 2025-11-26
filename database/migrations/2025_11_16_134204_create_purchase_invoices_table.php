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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->onDelete('cascade');

            $table->string('invoice_number')->nullable()->comment('No. faktur dari supplier');
            $table->date('invoice_date')->nullable();
            $table->string('invoice_image')->comment('Path ke gambar nota');
            $table->decimal('total_amount', 19, 4)->comment('Total nilai di nota fisik');

            $table->string('payment_status')->default('unpaid');
            $table->decimal('amount_paid', 19, 4)->default(0);
            $table->date('due_date')->nullable()->comment('Jatuh tempo pembayaran');
            $table->date('paid_at')->nullable()->comment('Tanggal lunas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
