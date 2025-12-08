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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('name'); // Contoh: "Listrik Bulan Mei"
            $table->string('category')->nullable(); // Contoh: "Operasional", "Gaji", "Marketing"
            $table->decimal('amount', 19, 4);
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable(); // Siapa yang input
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
