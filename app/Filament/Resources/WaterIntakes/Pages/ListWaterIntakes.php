<?php

namespace App\Filament\Resources\WaterIntakes\Pages;

use App\Filament\Resources\WaterIntakes\WaterIntakeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWaterIntakes extends ListRecords
{
    protected static string $resource = WaterIntakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
