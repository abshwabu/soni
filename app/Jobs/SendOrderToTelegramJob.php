<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderToTelegramJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        $telegramService->sendOrderNotification($this->order);
    }
}
