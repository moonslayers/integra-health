<?php

namespace App\Filament\Resources\ExerciseSessions\Schemas;

use App\Enums\ExerciseType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExerciseSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('minutes')
                    ->label('Minutos')
                    ->numeric()
                    ->integer()
                    ->required()
                    ->minValue(1),
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
            ]);
    }
}
