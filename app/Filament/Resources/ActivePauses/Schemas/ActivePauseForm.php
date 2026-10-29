<?php

namespace App\Filament\Resources\ActivePauses\Schemas;

use App\Enums\PauseType;
use App\Models\HealthSetting;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ActivePauseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ]);
    }
}
