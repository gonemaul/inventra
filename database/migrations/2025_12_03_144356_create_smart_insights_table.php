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
        Schema::create('smart_insights', function (Blueprint $table) {
            $table->id();
            // Relasi ke produk (Nullable, karena mungkin ada insight umum toko)
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');

            // Jenis Insight: 'restock', 'dead_stock', 'margin_alert', 'trend', 'health_score'
            $table->string('type')->index();

            // Tingkat Bahaya: 'critical' (Merah), 'warning' (Kuning), 'info' (Biru), 'success' (Hijau)
            $table->string('severity')->default('info');

            // Judul Singkat (Untuk Headline Dashboard/Notif)
            $table->string('title');

            // Pesan Detail (Penjelasan manusiawi)
            $table->text('message')->nullable();

            // Data Pendukung (Bukti Data dalam format JSON)
            // Contoh: { "sisa_stok": 2, "avg_jual": 5, "estimasi_habis": "Besok" }
            $table->json('payload')->nullable();

            // Action Link (Opsional: Jika diklik lari ke mana)
            // Contoh: '/purchases/create?product_id=5'
            $table->string('action_url')->nullable();

            // Status Baca & Kirim
            $table->boolean('is_read')->default(false); // Sudah dilihat di web?
            $table->boolean('is_notified')->default(false); // Sudah dikirim ke Telegram?

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_insights');
    }
};
