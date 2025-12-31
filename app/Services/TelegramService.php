<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public static function send($message)
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (!$token || !$chatId) return;

        try {
            // Kirim via API Telegram
            $url = "https://api.telegram.org/bot{$token}/sendMessage";

            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML' // Agar bisa bold/italic
            ]);
            if ($response->successful()) {
                return true;
            } else {
                // Log error jika ditolak Telegram (misal token salah)
                Log::error('Telegram Error: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            // Jangan sampai error telegram bikin aplikasi crash
            Log::error("Gagal kirim telegram: " . $e->getMessage());
        }
    }
}
