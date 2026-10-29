<?php

namespace App\Filament\Resources\Food\Schemas;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FoodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                        if (($get('slug') ?? '') !== Str::slug($old ?? '')) {
                            return;
                        }

                        $set('slug', Str::slug($state ?? ''));
                    }),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique()
                    ->maxLength(255),
                Select::make('category')
                    ->label('Categoría')
                    ->options(FoodCategory::class)
                    ->required(),
                Select::make('serving_unit')
                    ->label('Unidad de servicio')
                    ->options(ServingUnit::class)
                    ->required(),
                Toggle::make('counts_toward_goal')
                    ->label('Cuenta para la meta')
                    ->default(false)
                    ->live(),
                TextInput::make('portions_per_serving')
                    ->label('Porciones por unidad')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->visible(fn (Get $get): bool => (bool) $get('counts_toward_goal'))
                    ->required(fn (Get $get): bool => (bool) $get('counts_toward_goal')),
                TextInput::make('source')
                    ->label('Fuente')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpanFull(),
            ]);
    }
}
