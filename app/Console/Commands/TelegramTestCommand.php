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
    protected $signature = 'telegram:test 
        {--chat= : Specify a chat ID to test (defaults to TELEGRAM_ADMIN_CHAT_ID)}
        {--order : Send a full order notification with inline buttons instead of a basic message}
        {--diagnose : Output full diagnostics for bot token, queue worker, and webhook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification to Telegram and diagnose bot, queue, and webhook health';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegramService): int
    {
        $this->info('🔍 Soni Perfume — Telegram Integration Diagnostics & Test');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // 1. Verify Bot Token & API Connectivity
        $botToken = $telegramService->getBotToken();
        if (empty($botToken)) {
            $this->error('❌ TELEGRAM_BOT_TOKEN is not configured in .env.');
            return self::FAILURE;
        }

        $botMe = $telegramService->getMe();
        if ($botMe && ($botMe['ok'] ?? false)) {
            $botUsername = $botMe['result']['username'] ?? 'Unknown';
            $botName = $botMe['result']['first_name'] ?? 'Bot';
            $this->info("✅ Telegram Bot Authenticated: @{$botUsername} ({$botName})");
        } else {
            $this->warn('⚠️  Could not fetch bot profile via getMe. Proceeding to test direct message delivery...');
        }

        // 2. Verify Admin Chat IDs
        $chatOption = $this->option('chat');
        $chatIds = $chatOption ? [trim((string) $chatOption)] : $telegramService->getAllowedAdminChatIds();

        if (empty($chatIds)) {
            $this->error('❌ No TELEGRAM_ADMIN_CHAT_ID configured in .env or passed via --chat.');
            return self::FAILURE;
        }
        $this->line('📱 Target Chat ID(s): ' . implode(', ', $chatIds));

        // 3. Check Queue Health
        $queueConn = config('queue.default');
        $isSyncDispatch = (bool) config('services.telegram.sync_dispatch', false);
        $this->line("⚡ Queue Driver: <comment>{$queueConn}</comment> | Sync Dispatch: <comment>" . ($isSyncDispatch ? 'ENABLED' : 'DISABLED') . '</comment>');

        if ($queueConn === 'database' || $queueConn === 'queue_db') {
            try {
                $dbConn = config("queue.connections.{$queueConn}.connection") ?? config('queue.connections.database.connection', 'queue_db');
                $table = config("queue.connections.{$queueConn}.table") ?? 'jobs';
                $pendingJobs = \Illuminate\Support\Facades\DB::connection($dbConn)->table($table)->count();

                if ($pendingJobs > 0) {
                    $this->warn("⚠️  {$pendingJobs} job(s) pending in queue table [{$table}]. Make sure 'php artisan queue:work' is running!");
                } else {
                    $this->line("✅ Queue backlog: 0 pending jobs in [{$table}].");
                }
            } catch (\Throwable $e) {
                $this->warn("⚠️  Could not inspect queue table: {$e->getMessage()}");
            }
        }

        // 4. Check Webhook Info
        $webhookInfo = $telegramService->getWebhookInfo();
        if ($webhookInfo && ($webhookInfo['ok'] ?? false)) {
            $webhookUrl = $webhookInfo['result']['url'] ?? '';
            if (! empty($webhookUrl)) {
                $this->info("✅ Webhook Active: {$webhookUrl}");
            } else {
                $this->line('ℹ️  Webhook is NOT set (url is empty). Incoming bot commands and button clicks require either a webhook (php artisan telegram:set-webhook) or polling worker (php artisan telegram:poll).');
            }
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // 5. Send Test Notification
        $allOk = true;

        if ($this->option('order')) {
            $order = \App\Models\Order::with('items')->latest()->first();
            if (! $order) {
                $this->warn('No orders found in database to test. Creating a sample order for test...');
                $order = \App\Models\Order::create([
                    'order_number' => 'SP-TEST-' . rand(1000, 9999),
                    'customer_name' => 'Test Customer',
                    'phone' => '+251911223344',
                    'city' => 'Addis Ababa',
                    'address' => 'Bole Medhanialem',
                    'delivery_method' => 'delivery',
                    'subtotal' => 4500,
                    'delivery_fee' => 150,
                    'total' => 4650,
                    'status' => \App\Enums\OrderStatus::NEW,
                ]);
            }

            $this->line("Sending order notification test for Order #{$order->order_number}...");
            $sent = $telegramService->sendOrderNotification($order);
            if ($sent) {
                $this->info("✅ Successfully sent order notification with interactive buttons!");
            } else {
                $this->error("❌ Failed to send order notification.");
                $allOk = false;
            }
        } else {
            $nowAddis = now('Africa/Addis_Ababa')->format('M d, Y h:i A');
            $appUrl = config('app.url', 'http://localhost');

            $text = <<<TEXT
🎉 <b>Soni Perfume Store — Telegram Bot Test</b>
━━━━━━━━━━━━━━━━━━━━
Your Telegram bot notification integration is connected and verified!

🕒 Addis Ababa Time: <b>{$nowAddis}</b>
🌐 Storefront URL: <code>{$appUrl}</code>

Order alerts will be delivered directly here with instant action buttons.
TEXT;

            $keyboard = [];
            if (str_starts_with($appUrl, 'https://') && ! str_contains($appUrl, 'localhost') && ! str_contains($appUrl, '127.0.0.1')) {
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🛍 Visit Storefront', 'url' => $appUrl],
                        ],
                    ],
                ];
            }

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
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
