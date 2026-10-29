<?php

namespace App\Filament\Widgets;

use App\Enums\ExerciseType;
use App\Enums\PauseType;
use App\Enums\WaterType;
use App\Filament\Concerns\HasHealthRefresh;
use App\Models\ActivePause;
use App\Models\ExerciseSession;
use App\Models\FoodEntry;
use App\Models\WaterIntake;
use App\Services\HealthMetricsService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class RecentActivity extends TableWidget
{
    use HasHealthRefresh;

    protected static ?string $heading = 'Actividad reciente';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->recentRecords())
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo'),
                TextColumn::make('description')
                    ->label('Descripción'),
                TextColumn::make('occurred_at')
                    ->label('Fecha y hora'),
            ])
            ->paginated(false);
    }

    public function recentRecords(): Collection
    {
        $items = collect();

        foreach (WaterIntake::query()->latest('consumed_at')->limit(10)->get() as $record) {
            $items->push([
                '__key' => 'water-'.$record->id,
                'type' => 'Agua',
                'description' => ($record->type instanceof WaterType ? $record->type->getLabel() : (string) $record->type)
                    .' - '.$record->volume_ml.' ml'
                    .($record->notes ? ' - '.$record->notes : ''),
                'occurred_at' => $record->consumed_at?->format('d/m/Y H:i'),
                'sort_at' => $record->consumed_at,
            ]);
        }

        foreach (ActivePause::query()->latest('performed_at')->limit(10)->get() as $record) {
            $items->push([
                '__key' => 'pause-'.$record->id,
                'type' => 'Pausa activa',
                'description' => ($record->type instanceof PauseType ? $record->type->getLabel() : (string) $record->type)
                    .' - '.$record->minutes.' min',
                'occurred_at' => $record->performed_at?->format('d/m/Y H:i'),
                'sort_at' => $record->performed_at,
            ]);
        }

        foreach (FoodEntry::query()->with('food')->latest('consumed_at')->limit(10)->get() as $record) {
            $items->push([
                '__key' => 'food-'.$record->id,
                'type' => 'Alimento',
                'description' => ($record->food?->name ?? 'Alimento')
                    .' x'.rtrim(rtrim((string) $record->quantity, '0'), '.')
                    .' ('.app(HealthMetricsService::class)->portionsForFoodEntry($record).' porciones)',
                'occurred_at' => $record->consumed_at?->format('d/m/Y H:i'),
                'sort_at' => $record->consumed_at,
            ]);
        }

        foreach (ExerciseSession::query()->latest('performed_at')->limit(10)->get() as $record) {
            $items->push([
                '__key' => 'exercise-'.$record->id,
                'type' => 'Ejercicio',
                'description' => $record->minutes.' min'
                    .($record->type instanceof ExerciseType ? ' - '.$record->type->getLabel() : '')
                    .($record->notes ? ' - '.$record->notes : ''),
                'occurred_at' => $record->performed_at?->format('d/m/Y H:i'),
                'sort_at' => $record->performed_at,
            ]);
        }

        return $items->sortByDesc('sort_at')->take(10)->values();
    }
}
