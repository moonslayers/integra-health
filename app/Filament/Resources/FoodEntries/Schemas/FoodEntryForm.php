<?php

namespace App\Filament\Resources\FoodEntries\Schemas;

use App\Filament\Concerns\HasInlineFoodCreation;
use App\Models\Food;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
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
                        ->getOptionLabelFromRecordUsing(fn (Food $record): string => $record->nameWithUnit())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                ),
                TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->default(1)
                    ->required()
                    ->live(onBlur: true)
                    ->suffix(fn (Get $get): ?string => static::quantityUnitSuffix($get)),
                DateTimePicker::make('consumed_at')
                    ->label('Fecha y hora')
                    ->default(now())
                    ->required(),
                Textarea::make('notes')
                    ->label('Notas')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    protected static function quantityUnitSuffix(Get $get): ?string
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
        $unitLabel = $food->unitLabelForQuantity(filled($quantity) ? $quantity : 1);

        return $unitLabel === '' ? null : $unitLabel;
    }
}
