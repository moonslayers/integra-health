<?php

namespace App\Filament\Widgets;

use App\Enums\WaterType;
use App\Filament\Concerns\HasHealthRefresh;
use App\Filament\Concerns\HasRangeFilter;
use App\Models\WaterIntake;
use App\Services\HealthMetricsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class WaterByTypeChart extends ChartWidget
{
    use HasHealthRefresh;
    use HasRangeFilter;

    protected ?string $heading = 'Desglose de agua por tipo de bebida';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $service = app(HealthMetricsService::class);
        $days = $this->rangeDays();
        $end = $service->todayDate()->copy()->endOfDay();
        $start = $service->todayDate()->copy()->subDays($days - 1)->startOfDay();

        $rows = WaterIntake::query()
            ->whereBetween('consumed_at', [$start, $end])
            ->select('type', DB::raw('COALESCE(SUM(volume_ml), 0) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $labels = [];
        $data = [];
        foreach (WaterType::cases() as $type) {
            $labels[] = $type->getLabel();
            $data[] = (int) ($rows[$type->value] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Volumen (ml)',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function isEmpty(): bool
    {
        $data = $this->getCachedData();

        return empty(array_filter($data['datasets'][0]['data'] ?? []));
    }
}
