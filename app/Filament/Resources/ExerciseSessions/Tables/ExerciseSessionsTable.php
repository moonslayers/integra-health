<?php

namespace App\Filament\Resources\ExerciseSessions\Tables;

use App\Enums\ExerciseType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExerciseSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('minutes')
                    ->label('Minutos')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?ExerciseType $state): ?string => $state?->getLabel()),
                TextColumn::make('performed_at')
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
                    ->options(ExerciseType::class),
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
