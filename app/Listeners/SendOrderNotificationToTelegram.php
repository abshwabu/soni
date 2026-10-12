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
        if (config('services.telegram.sync_dispatch', false) || config('queue.default') === 'sync') {
            SendOrderToTelegram::dispatchSync($event->order);
        } else {
            SendOrderToTelegram::dispatch($event->order);
        }
    }
}
