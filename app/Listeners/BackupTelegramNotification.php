<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\CleanupWasSuccessful;
use Spatie\Backup\Events\CleanupHasFailed;

class BackupTelegramNotification
{
    public function handle(object $event): void
    {
        // Cek apakah Token & Chat ID sudah diisi
        $token = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (!$token || !$chatId) {
            return; // Jangan jalan kalau belum disetting
        }

        $message = '';

        // 1. JIKA BACKUP SUKSES
        if ($event instanceof BackupWasSuccessful) {
            $backupDestination = $event->backupDestination;
            $diskName = $backupDestination->diskName(); // public / google

            // Ambil ukuran file terbaru
            $newestBackup = $backupDestination->newestBackup();
            $size = $newestBackup ? $this->formatSize($newestBackup->sizeInBytes()) : 'Unknown';
            $appName = env('APP_NAME', 'Inventra POS');

            $message = "✅ *BACKUP SUKSES!* \n\n" .
                "📱 *App:* {$appName}\n" .
                "💾 *Disk:* " . strtoupper($diskName) . "\n" .
                "📦 *Size:* {$size}\n" .
                "⏰ *Time:* " . now()->format('d M Y H:i');
        }

        // 2. JIKA BACKUP GAGAL
        elseif ($event instanceof BackupHasFailed) {
            $error = $event->exception->getMessage();
            $appName = env('APP_NAME', 'Inventra POS');

            $message = "❌ *BACKUP GAGAL!* ⚠️ \n\n" .
                "📱 *App:* {$appName}\n" .
                "💀 *Error:* `{$error}`\n" .
                "⏰ *Time:* " . now()->format('d M Y H:i');
        }

        // 3. JIKA CLEANUP (BERSIH-BERSIH) SUKSES
        elseif ($event instanceof CleanupWasSuccessful) {
            // Opsional: Aktifkan jika ingin notif saat file lama dihapus
            $message = "🧹 *CLEANUP SELESAI* \nFile backup lama berhasil dihapus untuk menghemat ruang.";
        }

        // --- KIRIM KE TELEGRAM ---
        if ($message) {
            try {
                Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown', // Agar bisa bold/italic
                ]);
            } catch (\Exception $e) {
                Log::error("Gagal kirim notif Telegram: " . $e->getMessage());
            }
        }
    }

    // Helper format ukuran
    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024; $i++) $bytes /= 1024;
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
