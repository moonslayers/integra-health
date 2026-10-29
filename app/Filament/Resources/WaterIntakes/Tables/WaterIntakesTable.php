<?php

namespace App\Filament\Resources\WaterIntakes\Tables;

use App\Enums\WaterType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WaterIntakesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (WaterType $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('volume_ml')
                    ->label('Volumen (ml)')
                    ->suffix(' ml')
                    ->sortable(),
                TextColumn::make('consumed_at')
                    ->label('Fecha y hora')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->toggleable()
                    ->limit(40),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(WaterType::class),
                Filter::make('consumed_at')
                    ->label('Fecha')
                    ->schema([
                        DatePicker::make('consumed_at')->label('Fecha'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['consumed_at'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate('consumed_at', $date),
                    )),
            ])
            ->defaultSort('consumed_at', 'desc')
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
