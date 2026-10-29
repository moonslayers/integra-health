<?php

namespace App\Filament\Resources\WaterIntakes\Schemas;

use App\Enums\WaterType;
use App\Models\HealthSetting;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WaterIntakeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo de bebida')
                    ->options(WaterType::class)
                    ->required(),
                TextInput::make('volume_ml')
                    ->label('Volumen (ml)')
                    ->integer()
                    ->minValue(1)
                    ->nullable()
                    ->default(fn (): int => HealthSetting::current()->default_water_serving_ml)
                    ->dehydrateStateUsing(fn (mixed $state): int => filled($state) ? (int) $state : HealthSetting::current()->default_water_serving_ml),
                DateTimePicker::make('consumed_at')
                    ->label('Fecha y hora')
                    ->default(now())
                    ->required(),
                Textarea::make('notes')
                    ->label('Notas'),
            ]);
    }
}
