<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasHealthRefresh;
use App\Filament\Concerns\HasRangeFilter;
use App\Services\HealthMetricsService;
use Filament\Widgets\ChartWidget;

class WaterFrequencyChart extends ChartWidget
{
    use HasHealthRefresh;
    use HasRangeFilter;

    protected ?string $heading = 'Tendencia diaria de frecuencia de agua';

    protected function getType(): string
    {
        return 'bar';
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
            $data[] = (int) $day['water_count'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Registros',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
