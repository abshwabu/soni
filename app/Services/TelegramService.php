<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $botToken;
    protected ?string $chatId;

    public function __construct(
        ?string $botToken = null,
        ?string $chatId = null,
    ) {
        $this->botToken = $botToken ?? config('services.telegram.bot_token');
        $this->chatId = $chatId ?? config('services.telegram.admin_chat_id');
    }

    /**
     * Get the configured Bot Token.
     */
    public function getBotToken(): ?string
    {
        return $this->botToken;
    }

    /**
     * Get the configured base Telegram Bot API URL.
     */
    protected function getApiBaseUrl(): string
    {
        return "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Get list of allowed admin chat/user IDs (parsed from comma-separated string).
     *
     * @return array<int, string>
     */
    public function getAllowedAdminChatIds(): array
    {
        $raw = $this->chatId ?? config('services.telegram.admin_chat_id', '');
        if (empty($raw)) {
            return [];
        }

        $ids = array_map('trim', explode(',', (string) $raw));
        return array_values(array_filter($ids, fn ($id) => $id !== ''));
    }

    /**
     * Check if a given chat ID or user ID is an authorized admin.
     */
    public function isAdmin(string|int|null $senderId): bool
    {
        if ($senderId === null || $senderId === '') {
            return false;
        }

        $allowed = $this->getAllowedAdminChatIds();
        return in_array((string) $senderId, $allowed, true);
    }

    /**
     * Safely escape HTML characters for Telegram HTML parse mode.
     */
    public function escape(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Send a text message via Telegram Bot API.
     */
    public function sendMessage(
        string|int $chatId,
        string $text,
        array $replyMarkup = [],
        string $parseMode = 'HTML'
    ): ?array {
        if (empty($this->botToken)) {
            Log::warning('Telegram bot token not configured; skipping sendMessage.');
            return null;
        }

        $payload = [
            'chat_id' => (string) $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ];

        if (! empty($replyMarkup)) {
            $payload['reply_markup'] = $replyMarkup;
        }

        try {
            $response = Http::timeout(10)->post("{$this->getApiBaseUrl()}/sendMessage", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telegram sendMessage failed: ' . $response->body(), ['payload' => $payload]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Edit an existing message text.
     */
    public function editMessageText(
        string|int $chatId,
        int|string $messageId,
        string $text,
        array $replyMarkup = [],
        string $parseMode = 'HTML'
    ): ?array {
        if (empty($this->botToken)) {
            Log::warning('Telegram bot token not configured; skipping editMessageText.');
            return null;
        }

        $payload = [
            'chat_id' => (string) $chatId,
            'message_id' => (int) $messageId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ];

        if (! empty($replyMarkup)) {
            $payload['reply_markup'] = $replyMarkup;
        }

        try {
            $response = Http::timeout(10)->post("{$this->getApiBaseUrl()}/editMessageText", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Telegram editMessageText failed: ' . $response->body(), ['payload' => $payload]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Telegram editMessageText exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Edit only the reply markup of an existing message.
     */
    public function editMessageReplyMarkup(
        string|int $chatId,
        int|string $messageId,
        array $replyMarkup = []
    ): ?array {
        if (empty($this->botToken)) {
            return null;
        }

        $payload = [
            'chat_id' => (string) $chatId,
            'message_id' => (int) $messageId,
            'reply_markup' => $replyMarkup,
        ];

        try {
            $response = Http::timeout(10)->post("{$this->getApiBaseUrl()}/editMessageReplyMarkup", $payload);
            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::error('Telegram editMessageReplyMarkup exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Answer a callback query from an inline keyboard button.
     */
    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        bool $showAlert = false
    ): bool {
        if (empty($this->botToken)) {
            return false;
        }

        $payload = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
        ];

        if ($text !== null) {
            $payload['text'] = $text;
        }

        try {
            $response = Http::timeout(10)->post("{$this->getApiBaseUrl()}/answerCallbackQuery", $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram answerCallbackQuery exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Register a webhook endpoint with Telegram.
     */
    public function setWebhook(string $url, ?string $secretToken = null): ?array
    {
        if (empty($this->botToken)) {
            Log::error('Cannot set webhook: bot token is empty.');
            return null;
        }

        $payload = [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
        ];

        if (! empty($secretToken)) {
            $payload['secret_token'] = $secretToken;
        }

        try {
            $response = Http::timeout(15)->post("{$this->getApiBaseUrl()}/setWebhook", $payload);
            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Telegram setWebhook exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete existing webhook.
     */
    public function deleteWebhook(): bool
    {
        if (empty($this->botToken)) {
            return false;
        }

        try {
            $response = Http::timeout(10)->post("{$this->getApiBaseUrl()}/deleteWebhook");
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram deleteWebhook exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current webhook info.
     */
    public function getWebhookInfo(): ?array
    {
        if (empty($this->botToken)) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get("{$this->getApiBaseUrl()}/getWebhookInfo");
            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::error('Telegram getWebhookInfo exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format order details into HTML message for Telegram.
     */
    public function formatOrderMessage(Order $order): string
    {
        $order->loadMissing('items');

        $itemsText = '';
        foreach ($order->items as $item) {
            $variantStr = $item->variant_label ? " {$this->escape($item->variant_label)}" : '';
            $productName = $this->escape($item->product_name);
            $lineTotal = $item->formatted_line_total;
            $itemsText .= "• {$productName}{$variantStr} × {$item->quantity} — {$lineTotal}\n";
        }

        $deliveryMethod = $order->delivery_method?->label() ?? 'Delivery';
        $statusLabel = $order->status?->label() ?? 'New Order';

        $notes = $order->notes ? "\n📝 Notes: " . $this->escape($order->notes) : '';
        $altPhone = $order->alt_phone ? " / " . $this->escape($order->alt_phone) : '';

        $welcomeOfferClaimed = session('welcome_offer', false)
            || str_contains(strtolower($order->notes ?? ''), 'welcome offer')
            || str_contains(strtolower($order->notes ?? ''), 'sample vial')
            || str_contains(strtolower($order->notes ?? ''), 'soniwelcome');

        $offerNotice = $welcomeOfferClaimed
            ? "\n🎁 First-order offer claimed (Include complimentary sample vial!)\n"
            : '';

        $placedAt = $order->created_at
            ? $order->created_at->timezone('Africa/Addis_Ababa')->format('d M Y, h:i A')
            : now('Africa/Addis_Ababa')->format('d M Y, h:i A');

        $customerName = $this->escape($order->customer_name);
        $phone = $this->escape($order->phone);
        $address = $this->escape($order->city . ', ' . $order->address);

        return <<<TEXT
🛍 <b>New Order #{$order->order_number}</b>

👤 Name: {$customerName}
📞 Phone: {$phone}{$altPhone}
🚚 Delivery: {$deliveryMethod}
📍 Address: {$address}{$notes}{$offerNotice}
<b>Items</b>

{$itemsText}
💰 Subtotal: {$order->formatted_subtotal} / Delivery: {$order->formatted_delivery_fee} / <b>Total: {$order->formatted_total}</b>
🕒 Placed: {$placedAt}
🏷 <b>Status:</b> {$statusLabel}
TEXT;
    }

    /**
     * Generate inline keyboard markup for order actions.
     */
    public function getOrderInlineKeyboard(Order $order): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $order->phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '251' . substr($cleanPhone, 1);
        }

        $telUrl = "tel:{$order->phone}";
        $whatsappMsg = urlencode("Hello {$order->customer_name}, regarding your Soni Perfume order #{$order->order_number}:");
        $whatsappUrl = "https://wa.me/{$cleanPhone}?text={$whatsappMsg}";

        $adminUrl = rtrim(config('app.url', 'http://localhost'), '/') . "/admin/orders/{$order->id}";

        return [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Confirm', 'callback_data' => "order:{$order->id}:confirmed"],
                    ['text' => '🚚 Out for delivery', 'callback_data' => "order:{$order->id}:out_for_delivery"],
                ],
                [
                    ['text' => '✔️ Completed', 'callback_data' => "order:{$order->id}:completed"],
                    ['text' => '❌ Cancel', 'callback_data' => "order:{$order->id}:cancelled"],
                ],
                [
                    ['text' => '📞 Call customer', 'url' => $telUrl],
                    ['text' => '💬 WhatsApp', 'url' => $whatsappUrl],
                ],
                [
                    ['text' => '🔎 Open in Admin', 'url' => $adminUrl],
                ],
            ],
        ];
    }

    /**
     * Send order notification to admin Telegram chat(s).
     * Keeps DB write short after HTTP request finishes.
     */
    public function sendOrderNotification(Order $order): bool
    {
        $adminChatIds = $this->getAllowedAdminChatIds();

        if (empty($this->botToken) || empty($adminChatIds)) {
            Log::info("Telegram credentials not configured. Skipping notification for Order #{$order->order_number}.");
            return false;
        }

        $text = $this->formatOrderMessage($order);
        $keyboard = $this->getOrderInlineKeyboard($order);

        $sentPrimaryMessageId = null;
        $anySuccess = false;

        // Perform HTTP requests FIRST (outside of any database write lock)
        foreach ($adminChatIds as $chatId) {
            $response = $this->sendMessage($chatId, $text, $keyboard);
            if ($response && isset($response['result']['message_id'])) {
                $anySuccess = true;
                if ($sentPrimaryMessageId === null) {
                    $sentPrimaryMessageId = $response['result']['message_id'];
                }
            }
        }

        // Perform short DB write AFTER HTTP request is completed
        if ($anySuccess) {
            $order->updateQuietly([
                'telegram_message_id' => $sentPrimaryMessageId ? (string) $sentPrimaryMessageId : $order->telegram_message_id,
                'telegram_sent_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Notify admin when a product variant stock drops below 3.
     * Rate-limited/debounced to prevent spam.
     */
    public function notifyLowStock(ProductVariant $variant, ?int $newStock = null): bool
    {
        $stock = $newStock ?? $variant->stock;

        if ($stock >= 3) {
            // If stock was replenished to 3 or more, clear previous alert
            Cache::forget("low_stock_notified:{$variant->id}");
            return false;
        }

        // Prevent spam: only alert once every 24 hours per variant while low
        $cacheKey = "low_stock_notified:{$variant->id}";
        if (Cache::has($cacheKey)) {
            return false;
        }

        $adminChatIds = $this->getAllowedAdminChatIds();
        if (empty($this->botToken) || empty($adminChatIds)) {
            return false;
        }

        $productName = $this->escape($variant->product?->name ?? 'Perfume');
        $sizeStr = $this->escape($variant->label ?? "{$variant->size_ml}ml");
        $skuStr = $this->escape($variant->sku ?? 'N/A');

        $message = <<<TEXT
⚠️ <b>Low Stock Alert!</b>

Product: <b>{$productName}</b>
Variant: <b>{$sizeStr}</b> (SKU: {$skuStr})
Remaining Stock: <b>{$stock} units</b>
Threshold: &lt; 3 units

Please restock soon!
TEXT;

        $sent = false;
        foreach ($adminChatIds as $chatId) {
            $res = $this->sendMessage($chatId, $message);
            if ($res) {
                $sent = true;
            }
        }

        if ($sent) {
            Cache::put($cacheKey, true, now()->addHours(24));
        }

        return $sent;
    }
}
