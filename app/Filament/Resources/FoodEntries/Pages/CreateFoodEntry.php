<?php

namespace App\Filament\Resources\FoodEntries\Pages;

use App\Filament\Resources\FoodEntries\FoodEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFoodEntry extends CreateRecord
{
    protected static string $resource = FoodEntryResource::class;
}
