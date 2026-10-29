<?php

namespace App\Filament\Resources\FoodEntries\Pages;

use App\Filament\Resources\FoodEntries\FoodEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFoodEntries extends ListRecords
{
    protected static string $resource = FoodEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
