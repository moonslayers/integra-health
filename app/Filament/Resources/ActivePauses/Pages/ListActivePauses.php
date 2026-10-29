<?php

namespace App\Filament\Resources\ActivePauses\Pages;

use App\Filament\Resources\ActivePauses\ActivePauseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivePauses extends ListRecords
{
    protected static string $resource = ActivePauseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
