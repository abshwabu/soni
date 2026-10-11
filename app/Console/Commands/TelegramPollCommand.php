<?php

namespace App\Console\Commands;

use App\Http\Controllers\TelegramWebhookController;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll {--once : Process pending updates once and exit} {--timeout=20 : Long polling timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Telegram Bot API for incoming messages and callback queries in local development';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService, TelegramWebhookController $controller): int
    {
        $botToken = $telegramService->getBotToken();
        if (empty($botToken)) {
            $this->error('Telegram bot token is not configured. Please set TELEGRAM_BOT_TOKEN in .env.');
            return self::FAILURE;
        }

        $once = (bool) $this->option('once');
        $timeout = (int) $this->option('timeout');
        $offset = 0;

        $this->info('🤖 Telegram polling worker started. Listening for messages and button clicks...');
        if (! $once) {
            $this->line('Press Ctrl+C to stop.');
        }

        do {
            $response = $telegramService->getUpdates($offset, 100, $once ? 0 : $timeout);

            if ($response && ($response['ok'] ?? false)) {
                $updates = $response['result'] ?? [];

                foreach ($updates as $update) {
                    $updateId = $update['update_id'] ?? 0;
                    $offset = max($offset, $updateId + 1);

                    // Acknowledge this update immediately by updating offset
                    if (isset($update['callback_query'])) {
                        $this->line("Processing callback query [{$updateId}]...");
                        $controller->handleCallbackQuery($update['callback_query'], $telegramService);
                    } elseif (isset($update['message'])) {
                        $text = $update['message']['text'] ?? '';
                        $user = $update['message']['from']['first_name'] ?? 'User';
                        $this->line("Processing message from {$user}: \"{$text}\" [{$updateId}]...");
                        $controller->handleMessage($update['message'], $telegramService);
                    }
                }

                // If processed updates, acknowledge last offset
                if (! empty($updates)) {
                    $telegramService->getUpdates($offset, 1, 0);
                }
            }

            if (! $once) {
                usleep(500000); // 0.5s pause between polls
            }
        } while (! $once);

        $this->info('Done processing updates.');
        return self::SUCCESS;
    }
}
