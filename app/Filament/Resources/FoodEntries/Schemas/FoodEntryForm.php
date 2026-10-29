<?php

namespace App\Filament\Resources\FoodEntries\Schemas;

use App\Filament\Concerns\HasInlineFoodCreation;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FoodEntryForm
{
    use HasInlineFoodCreation;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                static::applyInlineFoodCreation(
                    Select::make('food_id')
                        ->label('Alimento')
                        ->relationship('food', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                ),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->default(1)
                    ->required(),
                DateTimePicker::make('consumed_at')
                    ->label('Fecha y hora')
                    ->default(now())
                    ->required(),
            ]);
    }
}
