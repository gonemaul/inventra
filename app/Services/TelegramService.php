<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $token;
    protected $chatId;

    public function __construct()
    {
        $this->token = env('TELEGRAM_BOT_TOKEN');
        $this->chatId = env('TELEGRAM_CHAT_ID');
    }

    public function send($message)
    {
        if (!$this->token || !$this->chatId) return;

        try {
            // Kirim via API Telegram
            $url = "https://api.telegram.org/bot{$this->token}/sendMessage";

            Http::post($url, [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML' // Agar bisa bold/italic
            ]);
        } catch (\Exception $e) {
            // Jangan sampai error telegram bikin aplikasi crash
            Log::error("Gagal kirim telegram: " . $e->getMessage());
        }
    }
}
