<?php

namespace App\Filament\Resources\FoodEntries;

use App\Filament\Resources\FoodEntries\Pages\CreateFoodEntry;
use App\Filament\Resources\FoodEntries\Pages\EditFoodEntry;
use App\Filament\Resources\FoodEntries\Pages\ListFoodEntries;
use App\Filament\Resources\FoodEntries\Schemas\FoodEntryForm;
use App\Filament\Resources\FoodEntries\Tables\FoodEntriesTable;
use App\Models\FoodEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FoodEntryResource extends Resource
{
    protected static ?string $model = FoodEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Consumo de alimentos';

    protected static ?string $modelLabel = 'Registro de alimento';

    protected static ?string $pluralModelLabel = 'Registros de alimentos';

    public static function form(Schema $schema): Schema
    {
        return FoodEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoodEntriesTable::configure($table);
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
            'index' => ListFoodEntries::route('/'),
            'create' => CreateFoodEntry::route('/create'),
            'edit' => EditFoodEntry::route('/{record}/edit'),
        ];
    }
}
