<?php

namespace App\Filament\Resources\WaterIntakes;

use App\Filament\Resources\WaterIntakes\Pages\CreateWaterIntake;
use App\Filament\Resources\WaterIntakes\Pages\EditWaterIntake;
use App\Filament\Resources\WaterIntakes\Pages\ListWaterIntakes;
use App\Filament\Resources\WaterIntakes\Schemas\WaterIntakeForm;
use App\Filament\Resources\WaterIntakes\Tables\WaterIntakesTable;
use App\Models\WaterIntake;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WaterIntakeResource extends Resource
{
    protected static ?string $model = WaterIntake::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $navigationLabel = 'Agua';

    protected static ?string $modelLabel = 'Ingesta de agua';

    protected static ?string $pluralModelLabel = 'Ingestas de agua';

    public static function form(Schema $schema): Schema
    {
        return WaterIntakeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaterIntakesTable::configure($table);
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
            'index' => ListWaterIntakes::route('/'),
            'create' => CreateWaterIntake::route('/create'),
            'edit' => EditWaterIntake::route('/{record}/edit'),
        ];
    }
}
