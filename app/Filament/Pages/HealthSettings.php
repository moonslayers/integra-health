<?php

namespace App\Filament\Pages;

use App\Models\HealthSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class HealthSettings extends Page
{
    protected string $view = 'filament.pages.health-settings';

    protected static ?string $navigationLabel = 'Metas';

    protected static ?string $title = 'Metas de salud';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::AdjustmentsHorizontal;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    TextInput::make('water_goal_ml')
                        ->label('Meta de agua (ml)')
                        ->helperText('Cantidad diaria recomendada de agua.')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('fruit_veg_goal_portions')
                        ->label('Meta de porciones de frutas/verduras')
                        ->helperText('Porciones diarias recomendadas.')
                        ->numeric()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->required(),
                    TextInput::make('active_pause_goal_count')
                        ->label('Meta de pausas activas al día')
                        ->helperText('Número de pausas activas recomendadas por día.')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('active_pause_default_minutes')
                        ->label('Minutos por pausa')
                        ->helperText('Duración por defecto de una pausa activa.')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('exercise_weekly_minutes_goal')
                        ->label('Meta semanal de minutos de ejercicio')
                        ->helperText('Minutos de ejercicio recomendados por semana.')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required(),
                    TextInput::make('exercise_weekly_days_goal')
                        ->label('Meta semanal de días activos')
                        ->helperText('Días activos recomendados por semana.')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(7)
                        ->required(),
                    TextInput::make('default_water_serving_ml')
                        ->label('Vaso default de agua (ml)')
                        ->helperText('Cantidad de agua por vaso por defecto.')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required(),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        SchemaActions::make([
                            Action::make('save')
                                ->label('Guardar')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $record = $this->getRecord();
        $record->fill($data);
        $record->save();
        $record->refresh();

        $this->form->record($record)->fill($record->attributesToArray());

        Notification::make()
            ->success()
            ->title('Metas guardadas')
            ->send();
    }

    public function getRecord(): HealthSetting
    {
        return HealthSetting::current();
    }
}
