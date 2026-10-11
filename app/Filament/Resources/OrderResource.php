<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\TelegramService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                            ->required(),
                        Forms\Components\TextInput::make('customer_name')
                            ->disabled(),
                        Forms\Components\TextInput::make('phone')
                            ->disabled(),
                        Forms\Components\TextInput::make('alt_phone')
                            ->disabled(),
                        Forms\Components\TextInput::make('city')
                            ->disabled(),
                        Forms\Components\TextInput::make('address')
                            ->disabled(),
                        Forms\Components\Textarea::make('notes')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order Overview')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('order_number')
                                    ->label('Order #')
                                    ->weight('bold')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (OrderStatus $state): string => $state->color()),

                                Infolists\Components\TextEntry::make('delivery_method')
                                    ->label('Method')
                                    ->formatStateUsing(fn ($state) => $state?->label()),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Date Placed')
                                    ->dateTime('M d, Y h:i A'),
                            ]),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('telegram_sent_at')
                                    ->label('Telegram Forwarding')
                                    ->formatStateUsing(fn ($state) => $state ? 'Sent on ' . $state->format('M d, Y - h:i A') : 'Not forwarded / Failed')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'danger'),

                                Infolists\Components\TextEntry::make('telegram_message_id')
                                    ->label('Telegram Message ID')
                                    ->placeholder('N/A'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Customer & Shipping Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer_name')
                                    ->label('Customer Name')
                                    ->weight('medium'),

                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Phone Number')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('alt_phone')
                                    ->label('Alternate Phone')
                                    ->placeholder('None'),

                                Infolists\Components\TextEntry::make('city')
                                    ->label('City'),

                                Infolists\Components\TextEntry::make('address')
                                    ->label('Address / Neighborhood')
                                    ->columnSpan(2),

                                Infolists\Components\TextEntry::make('notes')
                                    ->label('Customer Instructions')
                                    ->placeholder('No notes provided')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Ordered Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('product_name')
                                    ->label('Product')
                                    ->weight('bold'),

                                Infolists\Components\TextEntry::make('variant_label')
                                    ->label('Size / Variant'),

                                Infolists\Components\TextEntry::make('formatted_unit_price')
                                    ->label('Unit Price'),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Quantity'),

                                Infolists\Components\TextEntry::make('formatted_line_total')
                                    ->label('Line Total')
                                    ->weight('bold'),
                            ])
                            ->columns(5),
                    ]),

                Infolists\Components\Section::make('Payment & Totals')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('formatted_subtotal')
                                    ->label('Subtotal'),

                                Infolists\Components\TextEntry::make('formatted_delivery_fee')
                                    ->label('Delivery Fee'),

                                Infolists\Components\TextEntry::make('formatted_total')
                                    ->label('Grand Total')
                                    ->weight('bold'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('formatted_total')
                    ->label('Total')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total', $direction)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color()),

                Tables\Columns\IconColumn::make('telegram_sent_at')
                    ->label('Telegram')
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->tooltip(fn (Order $record) => $record->telegram_sent_at ? 'Sent at ' . $record->telegram_sent_at->format('M d, H:i') : 'Not forwarded / Failed'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->label('Order Status'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From Date'),
                        Forms\Components\DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('changeStatus')
                    ->label('Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('New Order Status')
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

                Tables\Actions\Action::make('resendTelegram')
                    ->label('Telegram')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Resend Order to Telegram')
                    ->modalDescription('Send order notification to the admin Telegram bot via queue job.')
                    ->action(function (Order $record): void {
                        try {
                            \App\Jobs\SendOrderToTelegram::dispatchSync($record);
                            $record->refresh();

                            if ($record->telegram_sent_at) {
                                Notification::make()
                                    ->title('Telegram notification sent successfully!')
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

                Tables\Actions\Action::make('openWhatsApp')
                    ->label('WhatsApp')
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

                Tables\Actions\Action::make('printInvoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Order $record): string => route('admin.orders.invoice', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
