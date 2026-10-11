<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOrderToTelegram implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job (exponential backoff).
     */
    public array $backoff = [5, 25, 100];

    public function __construct(
        public Order $order,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegramService): void
    {
        try {
            $sent = $telegramService->sendOrderNotification($this->order);

            if (! $sent) {
                Log::warning("SendOrderToTelegram: Could not send notification for Order #{$this->order->order_number} (check credentials or connection).");
            }
        } catch (\Throwable $e) {
            Log::error("SendOrderToTelegram exception for Order #{$this->order->order_number}: " . $e->getMessage());
            // Rethrow to allow queue worker to retry up to $tries with backoff
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("SendOrderToTelegram permanently failed for Order #{$this->order->order_number}: " . $exception?->getMessage());
    }
}
