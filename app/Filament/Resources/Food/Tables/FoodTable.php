<?php

namespace App\Filament\Resources\Food\Tables;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FoodTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Categoría')
                    ->formatStateUsing(fn (FoodCategory $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('serving_unit')
                    ->label('Unidad')
                    ->formatStateUsing(fn (ServingUnit $state): string => $state->getLabel()),
                TextColumn::make('portions_per_serving')
                    ->label('Porciones por unidad')
                    ->numeric(2)
                    ->placeholder('-'),
                IconColumn::make('counts_toward_goal')
                    ->label('Cuenta para la meta')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(FoodCategory::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
