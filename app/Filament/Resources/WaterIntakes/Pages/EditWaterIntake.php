<?php

namespace App\Filament\Resources\WaterIntakes\Pages;

use App\Filament\Resources\WaterIntakes\WaterIntakeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWaterIntake extends EditRecord
{
    protected static string $resource = WaterIntakeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
