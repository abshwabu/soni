<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function __construct(
        protected ?string $botToken = null,
        protected ?string $chatId = null,
    ) {
        $this->botToken = $botToken ?? config('services.telegram.bot_token');
        $this->chatId = $chatId ?? config('services.telegram.admin_chat_id');
    }

    /**
     * Send order notification to Telegram admin chat.
     */
    public function sendOrderNotification(Order $order): bool
    {
        if (empty($this->botToken) || empty($this->chatId)) {
            Log::info("Telegram credentials not configured. Skipping notification for Order #{$order->order_number}.");
            return false;
        }

        $order->loadMissing('items');

        $message = $this->formatOrderMessage($order);

        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['result']['message_id'] ?? null;

                $order->updateQuietly([
                    'telegram_message_id' => $messageId ? (string) $messageId : null,
                    'telegram_sent_at' => now(),
                ]);

                return true;
            }

            Log::error("Failed to send order notification to Telegram: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("Telegram notification exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format order details into HTML message for Telegram.
     */
    public function formatOrderMessage(Order $order): string
    {
        $itemsText = '';
        foreach ($order->items as $index => $item) {
            $i = $index + 1;
            $itemsText .= "<b>{$i}. {$item->product_name}</b> ({$item->variant_label})\n";
            $itemsText .= "   {$item->quantity}x {$item->formatted_unit_price} = <b>{$item->formatted_line_total}</b>\n";
        }

        $deliveryMethod = $order->delivery_method?->label() ?? 'Delivery';
        $status = $order->status?->label() ?? 'New';

        $notes = $order->notes ? "\n📝 <b>Notes:</b> " . htmlspecialchars($order->notes) : '';
        $altPhone = $order->alt_phone ? " / " . htmlspecialchars($order->alt_phone) : '';

        return <<<TEXT
🛍️ <b>NEW ORDER: #{$order->order_number}</b>
━━━━━━━━━━━━━━━━━━━━
👤 <b>Customer:</b> {$order->customer_name}
📞 <b>Phone:</b> {$order->phone}{$altPhone}
📍 <b>City/Address:</b> {$order->city}, {$order->address}
🚚 <b>Method:</b> {$deliveryMethod}
🏷️ <b>Status:</b> {$status}
{$notes}

📦 <b>Items Ordered:</b>
{$itemsText}
━━━━━━━━━━━━━━━━━━━━
Subtotal: <b>{$order->formatted_subtotal}</b>
Delivery Fee: <b>{$order->formatted_delivery_fee}</b>
Total: <b>{$order->formatted_total}</b>
━━━━━━━━━━━━━━━━━━━━
🕒 Placed: {$order->created_at->format('M d, Y h:i A')}
TEXT;
    }
}
