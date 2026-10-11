<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {--url= : Override the application base URL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register the Telegram Bot Webhook endpoint with Telegram Bot API';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService): int
    {
        $botToken = $telegramService->getBotToken();
        if (empty($botToken)) {
            $this->error('Telegram bot token is not configured. Please set TELEGRAM_BOT_TOKEN in your .env file.');
            return self::FAILURE;
        }

        $secret = config('services.telegram.webhook_secret');
        if (empty($secret)) {
            $secret = hash('sha256', $botToken);
        }

        $baseUrl = $this->option('url') ?: config('app.url');
        if (empty($baseUrl)) {
            $this->error('Application URL (APP_URL) is not configured.');
            return self::FAILURE;
        }

        if (! str_starts_with($baseUrl, 'https://')) {
            $this->warn("Warning: Telegram requires an HTTPS URL for webhooks. Base URL '{$baseUrl}' does not use HTTPS.");
        }

        $webhookUrl = rtrim($baseUrl, '/') . "/telegram/webhook/{$secret}";

        $this->info("Registering webhook: {$webhookUrl}...");

        $response = $telegramService->setWebhook($webhookUrl, $secret);

        if ($response && ($response['ok'] ?? false)) {
            $this->info('✅ Telegram webhook registered successfully!');
            if (isset($response['description'])) {
                $this->line("Telegram Response: {$response['description']}");
            }
            return self::SUCCESS;
        }

        $errorMsg = $response['description'] ?? json_encode($response);
        $this->error("❌ Failed to register webhook: {$errorMsg}");
        return self::FAILURE;
    }
}
