<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\TelegramService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, string $secret, TelegramService $telegramService): JsonResponse
    {
        // 1. Verify webhook secret
        $configuredSecret = config('services.telegram.webhook_secret');
        if (empty($configuredSecret)) {
            $botToken = config('services.telegram.bot_token');
            $configuredSecret = ! empty($botToken) ? hash('sha256', (string) $botToken) : null;
        }

        if (empty($configuredSecret) || ! hash_equals((string) $configuredSecret, $secret)) {
            Log::warning('Telegram webhook invalid secret attempt.', ['received' => $secret]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $update = $request->all();

        // 2. Handle callback_query (Inline keyboard button actions)
        if (isset($update['callback_query'])) {
            return $this->handleCallbackQuery($update['callback_query'], $telegramService);
        }

        // 3. Handle message (Commands like /start, /today, /pending)
        if (isset($update['message'])) {
            return $this->handleMessage($update['message'], $telegramService);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handle incoming callback queries from inline keyboard buttons.
     */
    protected function handleCallbackQuery(array $callbackQuery, TelegramService $telegramService): JsonResponse
    {
        $callbackQueryId = (string) ($callbackQuery['id'] ?? '');
        $fromId = $callbackQuery['from']['id'] ?? null;
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;
        $data = (string) ($callbackQuery['data'] ?? '');

        // Verify that sender is an authorized admin
        if (! $telegramService->isAdmin($fromId) && ! $telegramService->isAdmin($chatId)) {
            Log::warning('Telegram callback_query from unauthorized sender.', [
                'from_id' => $fromId,
                'chat_id' => $chatId,
            ]);
            $telegramService->answerCallbackQuery($callbackQueryId, 'Unauthorized: admin access only.', true);
            return response()->json(['ok' => true]);
        }

        // Parse callback data: format "order:{id}:{status}"
        if (! preg_match('/^order:(\d+):([a-z_]+)$/', $data, $matches)) {
            $telegramService->answerCallbackQuery($callbackQueryId);
            return response()->json(['ok' => true]);
        }

        $orderId = (int) $matches[1];
        $statusStr = $matches[2];
        $targetStatus = OrderStatus::tryFrom($statusStr);

        if (! $targetStatus) {
            $telegramService->answerCallbackQuery($callbackQueryId, 'Invalid status.');
            return response()->json(['ok' => true]);
        }

        $order = Order::with('items')->find($orderId);
        if (! $order) {
            $telegramService->answerCallbackQuery($callbackQueryId, 'Order not found.');
            return response()->json(['ok' => true]);
        }

        $previousStatus = $order->status;

        // If status changed to cancelled, restore stock of items (once only)
        if ($targetStatus === OrderStatus::CANCELLED && $previousStatus !== OrderStatus::CANCELLED) {
            foreach ($order->items as $item) {
                if ($item->product_variant_id && $item->quantity > 0) {
                    DB::update(
                        'UPDATE product_variants SET stock = stock + ? WHERE id = ?',
                        [$item->quantity, $item->product_variant_id]
                    );
                }
            }
        }

        // Update DB status
        $order->update(['status' => $targetStatus]);
        $order->refresh();

        // Update original message text with new status and re-attach inline keyboard
        if ($chatId && $messageId) {
            $updatedText = $telegramService->formatOrderMessage($order);
            $keyboard = $telegramService->getOrderInlineKeyboard($order);
            $telegramService->editMessageText($chatId, $messageId, $updatedText, $keyboard);
        }

        // Answer callback query with confirmation feedback
        $telegramService->answerCallbackQuery(
            $callbackQueryId,
            "Order #{$order->order_number} marked as {$targetStatus->label()}!"
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Handle incoming bot commands (/start, /today, /pending).
     */
    protected function handleMessage(array $message, TelegramService $telegramService): JsonResponse
    {
        $chatId = $message['chat']['id'] ?? null;
        $fromId = $message['from']['id'] ?? null;
        $text = trim($message['text'] ?? '');

        if (! $chatId || empty($text)) {
            return response()->json(['ok' => true]);
        }

        // Command: /start (always available to allow admin setup and Chat ID discovery)
        if (str_starts_with($text, '/start')) {
            $setupMessage = <<<TEXT
👋 <b>Welcome to Soni Perfume Store Bot!</b>

Your Chat ID is: <code>{$chatId}</code>

<b>Setup Instructions:</b>
1. Copy the Chat ID above.
2. In your <code>.env</code> file, set:
   <code>TELEGRAM_ADMIN_CHAT_ID={$chatId}</code>
3. Multiple admins can be comma-separated:
   <code>TELEGRAM_ADMIN_CHAT_ID=1234567,{$chatId}</code>

<b>Available Admin Commands:</b>
• /today — Today's order count & revenue
• /pending — List all pending orders
TEXT;

            $telegramService->sendMessage($chatId, $setupMessage);
            return response()->json(['ok' => true]);
        }

        // For all other commands, strictly require authorized admin
        if (! $telegramService->isAdmin($fromId) && ! $telegramService->isAdmin($chatId)) {
            Log::info("Ignoring message from unauthorized user {$fromId} in chat {$chatId}.");
            return response()->json(['ok' => true]);
        }

        // Command: /today
        if (str_starts_with($text, '/today')) {
            $this->handleTodayCommand($chatId, $telegramService);
            return response()->json(['ok' => true]);
        }

        // Command: /pending
        if (str_starts_with($text, '/pending')) {
            $this->handlePendingCommand($chatId, $telegramService);
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handle /today command.
     */
    protected function handleTodayCommand(string|int $chatId, TelegramService $telegramService): void
    {
        $startOfDay = now('Africa/Addis_Ababa')->startOfDay();
        $endOfDay = now('Africa/Addis_Ababa')->endOfDay();

        $ordersToday = Order::whereBetween('created_at', [$startOfDay, $endOfDay])->get();
        $count = $ordersToday->count();
        $revenue = $ordersToday->where('status', '!=', OrderStatus::CANCELLED)->sum('total');
        $formattedRevenue = Money::format($revenue);

        $newCount = $ordersToday->where('status', OrderStatus::NEW)->count();
        $confirmedCount = $ordersToday->where('status', OrderStatus::CONFIRMED)->count();
        $outCount = $ordersToday->where('status', OrderStatus::OUT_FOR_DELIVERY)->count();
        $completedCount = $ordersToday->where('status', OrderStatus::COMPLETED)->count();
        $cancelledCount = $ordersToday->where('status', OrderStatus::CANCELLED)->count();

        $todayStr = now('Africa/Addis_Ababa')->format('d M Y');

        $message = <<<TEXT
📊 <b>Today's Orders ({$todayStr})</b>
━━━━━━━━━━━━━━━━━━━━
Total Orders: <b>{$count}</b>
Total Revenue: <b>{$formattedRevenue}</b>

• New Orders: <b>{$newCount}</b>
• Confirmed: <b>{$confirmedCount}</b>
• Out for Delivery: <b>{$outCount}</b>
• Completed: <b>{$completedCount}</b>
• Cancelled: <b>{$cancelledCount}</b>
TEXT;

        $telegramService->sendMessage($chatId, $message);
    }

    /**
     * Handle /pending command.
     */
    protected function handlePendingCommand(string|int $chatId, TelegramService $telegramService): void
    {
        $pendingOrders = Order::where('status', OrderStatus::NEW)
            ->latest('created_at')
            ->take(10)
            ->get();

        if ($pendingOrders->isEmpty()) {
            $telegramService->sendMessage($chatId, "✅ <b>No pending orders!</b>\nAll orders have been processed.");
            return;
        }

        $count = $pendingOrders->count();
        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');

        $orderLines = '';
        foreach ($pendingOrders as $order) {
            $adminUrl = "{$baseUrl}/admin/orders/{$order->id}";
            $phone = $telegramService->escape($order->phone);
            $name = $telegramService->escape($order->customer_name);
            $orderLines .= "• <b>#{$order->order_number}</b> — {$order->formatted_total}\n";
            $orderLines .= "  👤 {$name} (📞 {$phone})\n";
            $orderLines .= "  👉 <a href=\"{$adminUrl}\">View in Admin</a>\n\n";
        }

        $message = <<<TEXT
📋 <b>Pending Orders ({$count})</b>
━━━━━━━━━━━━━━━━━━━━
{$orderLines}
TEXT;

        $telegramService->sendMessage($chatId, $message);
    }
}
