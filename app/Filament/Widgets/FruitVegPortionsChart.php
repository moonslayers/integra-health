<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasHealthRefresh;
use App\Filament\Concerns\HasRangeFilter;
use App\Services\HealthMetricsService;
use Filament\Widgets\ChartWidget;

class FruitVegPortionsChart extends ChartWidget
{
    use HasHealthRefresh;
    use HasRangeFilter;

    protected ?string $heading = 'Tendencia diaria de porciones de frutas/verduras';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $service = app(HealthMetricsService::class);
        $days = $this->rangeDays();
        $end = $service->todayDate();
        $start = $end->copy()->subDays($days - 1)->startOfDay();
        $series = $service->dailySeries($start, $end);

        $labels = [];
        $data = [];
        foreach ($series as $day) {
            $labels[] = $day['date'];
            $data[] = (float) $day['fruit_veg_portions'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Porciones',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
