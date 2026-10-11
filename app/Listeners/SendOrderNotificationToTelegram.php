<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderToTelegram;

class SendOrderNotificationToTelegram
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        SendOrderToTelegram::dispatch($event->order);
    }
}
