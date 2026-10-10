<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;

class TopProductsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Products (By Quantity Sold)';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $topProducts = OrderItem::query()
            ->selectRaw('product_name, SUM(quantity) as total_quantity')
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get();

        if ($topProducts->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Units Sold',
                        'data' => [0],
                        'backgroundColor' => ['#B08D57'],
                    ],
                ],
                'labels' => ['No sales recorded yet'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Bottles / Units Sold',
                    'data' => $topProducts->pluck('total_quantity')->map(fn ($q) => (int) $q)->all(),
                    'backgroundColor' => [
                        '#B08D57',
                        '#C8AF89',
                        '#9E7F4E',
                        '#846A41',
                        '#6A5534',
                    ],
                    'borderColor' => '#B08D57',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $topProducts->pluck('product_name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
