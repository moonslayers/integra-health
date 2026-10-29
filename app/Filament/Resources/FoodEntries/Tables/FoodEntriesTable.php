<?php

namespace App\Filament\Resources\FoodEntries\Tables;

use App\Enums\FoodCategory;
use App\Models\FoodEntry;
use App\Services\HealthMetricsService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FoodEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('food.name')
                    ->label('Alimento')
                    ->searchable(),
                TextColumn::make('food.category')
                    ->label('Categoría')
                    ->formatStateUsing(fn (FoodCategory $state): string => $state->getLabel()),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric(2),
                TextColumn::make('portions')
                    ->label('Porciones')
                    ->state(fn (FoodEntry $record): float => app(HealthMetricsService::class)->portionsForFoodEntry($record))
                    ->numeric(2),
                TextColumn::make('consumed_at')
                    ->label('Fecha y hora')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(FoodCategory::class)
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $value): Builder => $query->whereHas('food', fn (Builder $q) => $q->where('category', $value)),
                    )),
                Filter::make('consumed_at')
                    ->form([
                        DatePicker::make('consumed_from')->label('Desde'),
                        DatePicker::make('consumed_until')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['consumed_from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('consumed_at', '>=', $date))
                        ->when($data['consumed_until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('consumed_at', '<=', $date))),
            ])
            ->defaultSort('consumed_at', direction: 'desc')
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
