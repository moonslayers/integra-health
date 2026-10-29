<?php

namespace App\Filament\Resources\ExerciseSessions\Pages;

use App\Filament\Resources\ExerciseSessions\ExerciseSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExerciseSessions extends ListRecords
{
    protected static string $resource = ExerciseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
