<?php

namespace App\Filament\Resources\ActivePauses\Pages;

use App\Filament\Resources\ActivePauses\ActivePauseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActivePause extends EditRecord
{
    protected static string $resource = ActivePauseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
