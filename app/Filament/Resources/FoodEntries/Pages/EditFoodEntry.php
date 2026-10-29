<?php

namespace App\Filament\Resources\FoodEntries\Pages;

use App\Filament\Resources\FoodEntries\FoodEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFoodEntry extends EditRecord
{
    protected static string $resource = FoodEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
