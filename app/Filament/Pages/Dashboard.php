<?php

namespace App\Filament\Pages;

use App\Enums\ExerciseType;
use App\Enums\PauseType;
use App\Enums\WaterType;
use App\Filament\Concerns\HasHealthRefresh;
use App\Filament\Concerns\HasInlineFoodCreation;
use App\Filament\Widgets\ActivePausesChart;
use App\Filament\Widgets\ExerciseWeeklyChart;
use App\Filament\Widgets\FruitVegPortionsChart;
use App\Filament\Widgets\HealthStatsOverview;
use App\Filament\Widgets\RecentActivity;
use App\Filament\Widgets\WaterByTypeChart;
use App\Filament\Widgets\WaterFrequencyChart;
use App\Filament\Widgets\WaterVolumeChart;
use App\Models\ActivePause;
use App\Models\ExerciseSession;
use App\Models\Food;
use App\Models\FoodEntry;
use App\Models\HealthSetting;
use App\Models\WaterIntake;
use App\Services\HealthMetricsService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    use HasHealthRefresh;
    use HasInlineFoodCreation;

    public function getWidgets(): array
    {
        return [
            HealthStatsOverview::class,
            RecentActivity::class,
            WaterVolumeChart::class,
            WaterFrequencyChart::class,
            WaterByTypeChart::class,
            FruitVegPortionsChart::class,
            ActivePausesChart::class,
            ExerciseWeeklyChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->waterAction(),
            $this->pauseAction(),
            $this->foodAction(),
            $this->exerciseAction(),
        ];
    }

    protected function waterAction(): Action
    {
        return Action::make('registrarAgua')
            ->label('Registrar agua')
            ->icon(Heroicon::Beaker)
            ->schema([
                Select::make('type')
                    ->label('Tipo de bebida')
                    ->options(WaterType::class)
                    ->required(),
                TextInput::make('volume_ml')
                    ->label('Volumen (ml)')
                    ->integer()
                    ->minValue(1)
                    ->nullable(),
                DateTimePicker::make('consumed_at')
                    ->label('Fecha y hora')
                    ->default(now())
                    ->required(),
                Textarea::make('notes')
                    ->label('Notas'),
            ])
            ->action(function (array $data): void {
                $volume = filled($data['volume_ml'] ?? null)
                    ? (int) $data['volume_ml']
                    : HealthSetting::current()->default_water_serving_ml;

                WaterIntake::create([
                    'type' => $data['type'],
                    'volume_ml' => $volume,
                    'consumed_at' => $data['consumed_at'],
                    'notes' => $data['notes'] ?? null,
                ]);

                Notification::make()->success()->title('Agua registrada')->send();
                $this->dispatch('health-data-updated');
            });
    }

    protected function pauseAction(): Action
    {
        return Action::make('registrarPausa')
            ->label('Registrar pausa activa')
            ->icon(Heroicon::PauseCircle)
            ->schema([
                TextInput::make('minutes')
                    ->label('Minutos')
                    ->integer()
                    ->minValue(1)
                    ->default(fn (): int => HealthSetting::current()->active_pause_default_minutes)
                    ->required(),
                Select::make('type')
                    ->label('Tipo')
                    ->options(PauseType::class)
                    ->required(),
                DateTimePicker::make('performed_at')
                    ->label('Fecha y hora')
                    ->default(now())
                    ->required(),
            ])
            ->action(function (array $data): void {
                ActivePause::create([
                    'minutes' => (int) $data['minutes'],
                    'type' => $data['type'],
                    'performed_at' => $data['performed_at'],
                ]);

                Notification::make()->success()->title('Pausa registrada')->send();
                $this->dispatch('health-data-updated');
            });
    }

    protected function foodAction(): Action
    {
        return Action::make('registrarAlimento')
            ->label('Registrar alimento')
            ->icon(Heroicon::Cake)
            ->schema([
                static::applyInlineFoodCreation(
                    Select::make('food_id')
                        ->label('Alimento')
                        ->options(fn (): array => Food::query()
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Food $food): array => [$food->getKey() => $food->nameWithUnit()])
                            ->toArray())
                        ->searchable()
                        ->live()
                        ->required()
                ),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->default(1)
                    ->live()
                    ->suffix(fn (Get $get): ?string => $this->quantityUnitSuffix($get))
                    ->required(),
                DateTimePicker::make('consumed_at')
                    ->label('Fecha y hora')
                    ->default(now())
                    ->required(),
                Textarea::make('notes')
                    ->label('Notas'),
            ])
            ->action(function (array $data): void {
                $entry = FoodEntry::create([
                    'food_id' => (int) $data['food_id'],
                    'quantity' => $data['quantity'],
                    'consumed_at' => $data['consumed_at'],
                    'notes' => $data['notes'] ?? null,
                ]);

                $portions = app(HealthMetricsService::class)->portionsForFoodEntry($entry);

                Notification::make()
                    ->success()
                    ->title('Alimento registrado')
                    ->body('Porciones calculadas: '.$portions)
                    ->send();

                $this->dispatch('health-data-updated');
            });
    }

    private function quantityUnitSuffix(Get $get): ?string
    {
        $foodId = $get('food_id');

        if (blank($foodId)) {
            return null;
        }

        $food = Food::query()->find($foodId);

        if ($food === null) {
            return null;
        }

        $quantity = $get('quantity');
        $quantity = filled($quantity) ? $quantity : 1;

        $unitLabel = $food->unitLabelForQuantity($quantity);

        return $unitLabel === '' ? null : $unitLabel;
    }

    protected function exerciseAction(): Action
    {
        return Action::make('registrarEjercicio')
            ->label('Registrar ejercicio')
            ->icon(Heroicon::Bolt)
            ->schema([
                TextInput::make('minutes')
                    ->label('Minutos')
                    ->integer()
                    ->minValue(1)
                    ->required(),
                DateTimePicker::make('performed_at')
                    ->label('Fecha y hora')
                    ->default(now())
                    ->required(),
                Select::make('type')
                    ->label('Tipo')
                    ->options(ExerciseType::class)
                    ->nullable(),
                Textarea::make('notes')
                    ->label('Notas'),
            ])
            ->action(function (array $data): void {
                ExerciseSession::create([
                    'minutes' => (int) $data['minutes'],
                    'performed_at' => $data['performed_at'],
                    'type' => $data['type'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                Notification::make()->success()->title('Ejercicio registrado')->send();
                $this->dispatch('health-data-updated');
            });
    }
}
