<?php

namespace App\Filament\Resources\ExerciseSessions\Pages;

use App\Filament\Resources\ExerciseSessions\ExerciseSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExerciseSession extends EditRecord
{
    protected static string $resource = ExerciseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
