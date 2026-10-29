<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasHealthRefresh;
use App\Filament\Concerns\HasRangeFilter;
use App\Services\HealthMetricsService;
use Filament\Widgets\ChartWidget;

class ActivePausesChart extends ChartWidget
{
    use HasHealthRefresh;
    use HasRangeFilter;

    protected ?string $heading = 'Tendencia diaria de pausas activas';

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
        $counts = [];
        $minutes = [];
        foreach ($series as $day) {
            $labels[] = $day['date'];
            $counts[] = (int) $day['pauses_count'];
            $minutes[] = (int) $day['pauses_minutes'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cantidad',
                    'data' => $counts,
                ],
                [
                    'label' => 'Minutos',
                    'data' => $minutes,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
