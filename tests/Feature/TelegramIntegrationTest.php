<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Jobs\SendOrderToTelegram;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\TelegramService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.telegram.bot_token', '123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ');
        Config::set('services.telegram.admin_chat_id', '987654321,555444333');
        Config::set('services.telegram.webhook_secret', 'test-webhook-secret-123');
        Config::set('app.url', 'https://soniperfume.com');

        Cache::flush();
    }

    protected function createSampleOrder(int $initialStatus = 0): Order
    {
        $variant = ProductVariant::first();

        $order = Order::create([
            'order_number' => 'SP-20261011-' . uniqid() . '-' . rand(1000, 9999),
            'customer_name' => 'Abebe Bikila',
            'phone' => '+251911223344',
            'alt_phone' => '+251922334455',
            'city' => 'Addis Ababa',
            'address' => 'Bole Medhanialem, House 123',
            'notes' => 'Please ring the doorbell upon arrival',
            'delivery_method' => 'delivery',
            'subtotal' => 9000,
            'delivery_fee' => 0,
            'total' => 9000,
            'status' => OrderStatus::NEW,
            'ip_address' => '127.0.0.1',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_label' => $variant->label,
            'unit_price' => 4500,
            'quantity' => 2,
            'line_total' => 9000,
        ]);

        return $order->fresh(['items']);
    }

    public function test_telegram_service_formats_order_message_correctly(): void
    {
        $order = $this->createSampleOrder();
        $telegramService = app(TelegramService::class);

        $message = $telegramService->formatOrderMessage($order);

        $this->assertStringContainsString("New Order #{$order->order_number}", $message);
        $this->assertStringContainsString('Abebe Bikila', $message);
        $this->assertStringContainsString('+251911223344', $message);
        $this->assertStringContainsString('+251922334455', $message);
        $this->assertStringContainsString('Bole Medhanialem', $message);
        $this->assertStringContainsString('Please ring the doorbell upon arrival', $message);
        $this->assertStringContainsString('ETB 9,000', $message);
        $this->assertStringContainsString('Status:</b> New Order', $message);
        $this->assertStringContainsString('Placed:', $message);
    }

    public function test_telegram_service_generates_inline_keyboard(): void
    {
        $order = $this->createSampleOrder();
        $telegramService = app(TelegramService::class);

        $keyboard = $telegramService->getOrderInlineKeyboard($order);

        $this->assertArrayHasKey('inline_keyboard', $keyboard);
        $rows = $keyboard['inline_keyboard'];

        // Row 1: Confirm & Out for delivery
        $this->assertEquals("order:{$order->id}:confirmed", $rows[0][0]['callback_data']);
        $this->assertEquals("order:{$order->id}:out_for_delivery", $rows[0][1]['callback_data']);

        // Row 2: Completed & Cancel
        $this->assertEquals("order:{$order->id}:completed", $rows[1][0]['callback_data']);
        $this->assertEquals("order:{$order->id}:cancelled", $rows[1][1]['callback_data']);

        // Row 3: WhatsApp & Open in Admin
        $this->assertStringContainsString('wa.me/251911223344', $rows[2][0]['url']);
        $this->assertStringContainsString("/admin/orders/{$order->id}", $rows[2][1]['url']);
    }

    public function test_send_order_notification_sends_http_and_updates_order(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response([
                'ok' => true,
                'result' => [
                    'message_id' => 778899,
                ],
            ], 200),
        ]);

        $order = $this->createSampleOrder();
        $this->assertNull($order->telegram_message_id);
        $this->assertNull($order->telegram_sent_at);

        $telegramService = app(TelegramService::class);
        $sent = $telegramService->sendOrderNotification($order);

        $this->assertTrue($sent);

        $order->refresh();
        $this->assertEquals('778899', $order->telegram_message_id);
        $this->assertNotNull($order->telegram_sent_at);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && in_array($request['chat_id'], ['987654321', '555444333']);
        });
    }

    public function test_order_placed_event_dispatches_send_order_to_telegram_job(): void
    {
        Queue::fake();

        $order = $this->createSampleOrder();

        OrderPlaced::dispatch($order);

        Queue::assertPushed(SendOrderToTelegram::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });
    }

    public function test_send_order_to_telegram_job_executes_successfully(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 12345],
            ], 200),
        ]);

        $order = $this->createSampleOrder();

        $job = new SendOrderToTelegram($order);
        $job->handle(app(TelegramService::class));

        $order->refresh();
        $this->assertEquals('12345', $order->telegram_message_id);
        $this->assertNotNull($order->telegram_sent_at);
    }

    public function test_webhook_rejects_invalid_secret_token(): void
    {
        $response = $this->postJson('/telegram/webhook/wrong-secret', [
            'update_id' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_ignores_unauthorized_sender(): void
    {
        Http::fake();

        $response = $this->postJson('/telegram/webhook/test-webhook-secret-123', [
            'callback_query' => [
                'id' => 'cb_unauthorized',
                'from' => ['id' => 99999999], // Not an admin
                'message' => [
                    'message_id' => 10,
                    'chat' => ['id' => 99999999],
                ],
                'data' => 'order:1:confirmed',
            ],
        ]);

        $response->assertOk();
    }

    public function test_webhook_callback_query_updates_status_and_edits_telegram_message(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/editMessageText' => Http::response(['ok' => true], 200),
            'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
        ]);

        $order = $this->createSampleOrder();
        $this->assertEquals(OrderStatus::NEW, $order->status);

        $response = $this->postJson('/telegram/webhook/test-webhook-secret-123', [
            'callback_query' => [
                'id' => 'cb_confirm_123',
                'from' => ['id' => 987654321], // authorized admin
                'message' => [
                    'message_id' => 101,
                    'chat' => ['id' => 987654321],
                ],
                'data' => "order:{$order->id}:confirmed",
            ],
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);

        Http::assertSent(fn ($req) => str_contains($req->url(), 'editMessageText'));
        Http::assertSent(fn ($req) => str_contains($req->url(), 'answerCallbackQuery'));
    }

    public function test_webhook_callback_query_cancellation_restores_variant_stock_once_only(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/editMessageText' => Http::response(['ok' => true], 200),
            'https://api.telegram.org/bot*/answerCallbackQuery' => Http::response(['ok' => true], 200),
        ]);

        $order = $this->createSampleOrder();
        $item = $order->items->first();
        $variant = $item->productVariant;
        $initialStock = $variant->stock;

        // 1. First cancellation: stock should be restored by item->quantity (2)
        $this->postJson('/telegram/webhook/test-webhook-secret-123', [
            'callback_query' => [
                'id' => 'cb_cancel_1',
                'from' => ['id' => 987654321],
                'message' => [
                    'message_id' => 101,
                    'chat' => ['id' => 987654321],
                ],
                'data' => "order:{$order->id}:cancelled",
            ],
        ])->assertOk();

        $order->refresh();
        $variant->refresh();
        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
        $this->assertEquals($initialStock + 2, $variant->stock);

        // 2. Second cancellation call: stock must NOT be restored again
        $this->postJson('/telegram/webhook/test-webhook-secret-123', [
            'callback_query' => [
                'id' => 'cb_cancel_2',
                'from' => ['id' => 987654321],
                'message' => [
                    'message_id' => 101,
                    'chat' => ['id' => 987654321],
                ],
                'data' => "order:{$order->id}:cancelled",
            ],
        ])->assertOk();

        $variant->refresh();
        $this->assertEquals($initialStock + 2, $variant->stock);
    }

    public function test_webhook_handles_start_command(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        $response = $this->postJson('/telegram/webhook/test-webhook-secret-123', [
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 44332211],
                'chat' => ['id' => 44332211],
                'text' => '/start',
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request['text'], '44332211')
                && str_contains($request['text'], 'TELEGRAM_ADMIN_CHAT_ID');
        });
    }

    public function test_webhook_handles_today_command_for_admin(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        $this->createSampleOrder();

        $response = $this->postJson('/telegram/webhook/test-webhook-secret-123', [
            'message' => [
                'message_id' => 2,
                'from' => ['id' => 987654321], // admin
                'chat' => ['id' => 987654321],
                'text' => '/today',
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request['text'], "Today's Orders")
                && str_contains($request['text'], 'Total Revenue:');
        });
    }

    public function test_webhook_handles_pending_command_for_admin(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        $order = $this->createSampleOrder();

        $response = $this->postJson('/telegram/webhook/test-webhook-secret-123', [
            'message' => [
                'message_id' => 3,
                'from' => ['id' => 987654321], // admin
                'chat' => ['id' => 987654321],
                'text' => '/pending',
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) use ($order) {
            return str_contains($request['text'], 'Pending Orders')
                && str_contains($request['text'], $order->order_number);
        });
    }

    public function test_telegram_set_webhook_artisan_command(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/setWebhook' => Http::response([
                'ok' => true,
                'description' => 'Webhook was set',
            ], 200),
        ]);

        $this->artisan('telegram:set-webhook', ['--url' => 'https://soniperfume.com'])
            ->expectsOutputToContain('Telegram webhook registered successfully')
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains($request['url'], 'https://soniperfume.com/telegram/webhook/test-webhook-secret-123');
        });
    }

    public function test_telegram_test_artisan_command(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        $this->artisan('telegram:test', ['--chat' => '987654321'])
            ->expectsOutputToContain('Successfully sent test notification')
            ->assertSuccessful();

        Http::assertSent(function ($request) {
            return ($request['chat_id'] ?? null) === '987654321'
                && str_contains($request['text'] ?? '', 'Telegram Bot Test');
        });
    }

    public function test_notify_low_stock_alerts_and_debounces(): void
    {
        Http::fake([
            'https://api.telegram.org/bot*/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        $variant = ProductVariant::first();
        $variant->update(['stock' => 2]);

        $telegramService = app(TelegramService::class);

        // 1. First low stock alert should send
        $sentFirst = $telegramService->notifyLowStock($variant, 2);
        $this->assertTrue($sentFirst);

        // 2. Second low stock alert within 24h should be suppressed to avoid spam
        $sentSecond = $telegramService->notifyLowStock($variant, 1);
        $this->assertFalse($sentSecond);

        // 3. Replenishment to >= 3 clears the suppression
        $variant->update(['stock' => 10]);
        $telegramService->notifyLowStock($variant, 10);
        $this->assertFalse(Cache::has("low_stock_notified:{$variant->id}"));
    }
}
