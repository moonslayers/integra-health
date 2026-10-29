<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\HasHealthRefresh;
use App\Services\HealthMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HealthStatsOverview extends StatsOverviewWidget
{
    use HasHealthRefresh;

    protected ?string $heading = 'Resumen de hoy';

    protected function getStats(): array
    {
        $summary = app(HealthMetricsService::class)->dashboardSummary();
        $today = $summary['today'];
        $exercise = $summary['exercise'];

        $waterCurrent = (int) $today['water_total_ml'];
        $waterGoal = (int) $summary['water']['goal'];

        $portionsCurrent = (float) $today['fruit_veg_portions'];
        $portionsGoal = (float) $summary['portions']['goal'];
        $portionsPct = (float) $summary['portions']['percentage'];

        $pausesCurrent = (int) $today['pauses_count'];
        $pausesGoal = (int) $summary['pauses']['goal'];
        $pausesMinutes = (int) $today['pauses_minutes'];

        return [
            Stat::make('Agua de hoy', sprintf('%d / %d ml', $waterCurrent, $waterGoal))
                ->description(sprintf('%d registros', (int) $today['water_count']))
                ->descriptionIcon('heroicon-m-beaker')
                ->color($summary['water']['met'] ? 'success' : 'warning'),
            Stat::make('Porciones frutas/verduras', sprintf('%.1f / %.1f', $portionsCurrent, $portionsGoal))
                ->description(sprintf('%.0f%% de la meta', $portionsPct))
                ->descriptionIcon('heroicon-m-sparkles')
                ->color($summary['portions']['met'] ? 'success' : 'warning'),
            Stat::make('Pausas activas', sprintf('%d / %d', $pausesCurrent, $pausesGoal))
                ->description(sprintf('%d minutos totales', $pausesMinutes))
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color($summary['pauses']['met'] ? 'success' : 'warning'),
            Stat::make('Ejercicio (minutos)', sprintf('%d / %d min', (int) $exercise['minutes'], (int) $exercise['minutes_goal']))
                ->description(sprintf('%.0f%% de la meta semanal', (float) $exercise['minutes_percentage']))
                ->descriptionIcon('heroicon-m-bolt')
                ->color($exercise['minutes_met'] ? 'success' : 'warning'),
            Stat::make('Ejercicio (días)', sprintf('%d / %d días', (int) $exercise['days'], (int) $exercise['days_goal']))
                ->description('Días activos esta semana')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($exercise['days_met'] ? 'success' : 'warning'),
        ];
    }
}
