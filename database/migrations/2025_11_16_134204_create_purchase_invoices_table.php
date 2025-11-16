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

            $table->decimal('total_amount', 15, 2)->comment('Total nilai di nota fisik');

            // Status Operasional Nota (uploaded, validated)
            $table->string('status');

            // Status Finansial (unpaid, partial, paid)
            $table->string('payment_status');
            $table->decimal('amount_paid', 15, 2)->default(0)->comment('Jumlah yang sudah dibayar');
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
