<?php

namespace App\Filament\Resources\ActivePauses\Pages;

use App\Filament\Resources\ActivePauses\ActivePauseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActivePause extends CreateRecord
{
    protected static string $resource = ActivePauseResource::class;
}
