<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {--chat= : Specify a chat ID to test (defaults to TELEGRAM_ADMIN_CHAT_ID)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification to configured Telegram admin chat';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService): int
    {
        $botToken = $telegramService->getBotToken();
        if (empty($botToken)) {
            $this->error('Telegram bot token is not configured. Please set TELEGRAM_BOT_TOKEN in .env.');
            return self::FAILURE;
        }

        $chatOption = $this->option('chat');
        $chatIds = $chatOption ? [trim((string) $chatOption)] : $telegramService->getAllowedAdminChatIds();

        if (empty($chatIds)) {
            $this->error('No Telegram admin chat ID configured. Please set TELEGRAM_ADMIN_CHAT_ID in .env or pass --chat.');
            return self::FAILURE;
        }

        $nowAddis = now('Africa/Addis_Ababa')->format('M d, Y h:i A');
        $appUrl = config('app.url', 'http://localhost');

        $text = <<<TEXT
🎉 <b>Soni Perfume Store — Telegram Bot Test</b>
━━━━━━━━━━━━━━━━━━━━
Your Telegram bot integration is active and operating correctly!

🕒 Addis Ababa Time: <b>{$nowAddis}</b>
🌐 Storefront URL: <code>{$appUrl}</code>

You will receive order notifications directly here.
TEXT;

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🛍 Visit Storefront', 'url' => $appUrl],
                ],
            ],
        ];

        $allOk = true;

        foreach ($chatIds as $chatId) {
            $this->line("Sending test message to chat ID {$chatId}...");
            $response = $telegramService->sendMessage($chatId, $text, $keyboard);

            if ($response && ($response['ok'] ?? false)) {
                $this->info("✅ Successfully sent test notification to chat ID {$chatId}!");
            } else {
                $this->error("❌ Failed to send test notification to chat ID {$chatId}.");
                $allOk = false;
            }
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
