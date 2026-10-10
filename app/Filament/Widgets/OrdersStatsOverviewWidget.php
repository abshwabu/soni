<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todayOrders = Order::whereDate('created_at', today())->count();
        $thisWeekOrders = Order::where('created_at', '>=', now()->startOfWeek())->count();
        $monthlyCompletedRevenue = Order::where('status', OrderStatus::COMPLETED->value)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total');
        $pendingOrders = Order::whereIn('status', [OrderStatus::NEW->value, OrderStatus::CONFIRMED->value])->count();

        return [
            Stat::make('Orders Today', number_format($todayOrders))
                ->description('Orders placed today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Orders This Week', number_format($thisWeekOrders))
                ->description('Orders since Monday')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),

            Stat::make('Completed Revenue (Month)', Money::format((int) $monthlyCompletedRevenue))
                ->description('Completed orders this month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Pending Orders', number_format($pendingOrders))
                ->description('Awaiting dispatch / confirmation')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'gray'),
        ];
    }
}
