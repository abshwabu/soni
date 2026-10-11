<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\TelegramService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('changeStatus')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Order Status')
                        ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                        ->default(fn (Order $record) => $record->status->value)
                        ->required(),
                ])
                ->action(function (Order $record, array $data): void {
                    $record->update(['status' => $data['status']]);
                    Notification::make()
                        ->title('Status updated to ' . OrderStatus::from($data['status'])->label())
                        ->success()
                        ->send();
                }),

            Actions\Action::make('resendTelegram')
                ->label('Resend to Telegram')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Resend Order to Telegram')
                ->modalDescription('Dispatch order notification to the admin Telegram bot via queue job.')
                ->action(function (Order $record): void {
                    try {
                        \App\Jobs\SendOrderToTelegram::dispatchSync($record);
                        $record->refresh();

                        if ($record->telegram_sent_at) {
                            Notification::make()
                                ->title('Telegram notification sent!')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Telegram dispatch executed (check Telegram token and admin chat ID).')
                                ->warning()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Telegram dispatch failed: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('openWhatsApp')
                ->label('Open WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->url(function (Order $record): string {
                    $clean = preg_replace('/[^0-9]/', '', $record->phone);
                    if (str_starts_with($clean, '0')) {
                        $clean = '251' . substr($clean, 1);
                    }
                    $msg = urlencode("Hello {$record->customer_name}, regarding your Soni Perfume order #{$record->order_number}:");
                    return "https://wa.me/{$clean}?text={$msg}";
                })
                ->openUrlInNewTab(),

            Actions\Action::make('printInvoice')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (Order $record): string => route('admin.orders.invoice', $record))
                ->openUrlInNewTab(),
        ];
    }
}
