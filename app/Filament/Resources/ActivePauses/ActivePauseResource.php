<?php

namespace App\Filament\Resources\ActivePauses;

use App\Filament\Resources\ActivePauses\Pages\CreateActivePause;
use App\Filament\Resources\ActivePauses\Pages\EditActivePause;
use App\Filament\Resources\ActivePauses\Pages\ListActivePauses;
use App\Filament\Resources\ActivePauses\Schemas\ActivePauseForm;
use App\Filament\Resources\ActivePauses\Tables\ActivePausesTable;
use App\Models\ActivePause;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ActivePauseResource extends Resource
{
    protected static ?string $model = ActivePause::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPause;

    protected static ?string $navigationLabel = 'Pausas activas';

    protected static ?string $modelLabel = 'Pausa activa';

    protected static ?string $pluralModelLabel = 'Pausas activas';

    public static function form(Schema $schema): Schema
    {
        return ActivePauseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivePausesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivePauses::route('/'),
            'create' => CreateActivePause::route('/create'),
            'edit' => EditActivePause::route('/{record}/edit'),
        ];
    }
}
