<?php

namespace App\Filament\Resources\ActivePauses\Tables;

use App\Enums\PauseType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivePausesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('minutes')
                    ->label('Minutos')
                    ->suffix(' min')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (PauseType $state): string => $state->getLabel())
                    ->sortable(),
                TextColumn::make('performed_at')
                    ->label('Fecha y hora')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(PauseType::class),
                Filter::make('performed_at')
                    ->label('Fecha')
                    ->schema([
                        DatePicker::make('performed_at')->label('Fecha'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['performed_at'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate('performed_at', $date),
                    )),
            ])
            ->defaultSort('performed_at', 'desc')
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
