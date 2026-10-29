<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasHealthRefresh;
use App\Filament\Concerns\HasRangeFilter;
use App\Services\HealthMetricsService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ExerciseWeeklyChart extends ChartWidget
{
    use HasHealthRefresh;
    use HasRangeFilter;

    protected ?string $heading = 'Tendencia semanal de ejercicio';

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

        $labels = [];
        $minutes = [];
        $activeDays = [];

        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        while ($cursor->lte($end)) {
            $summary = $service->weeklyExerciseSummary($cursor);
            $labels[] = $summary['week_start'];
            $minutes[] = (int) $summary['minutes'];
            $activeDays[] = (int) $summary['days'];
            $cursor->addWeek();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Minutos',
                    'data' => $minutes,
                ],
                [
                    'label' => 'Días activos',
                    'data' => $activeDays,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
