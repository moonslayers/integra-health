<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ExerciseType: string implements HasLabel
{
    case Cardio = 'cardio';
    case Strength = 'strength';
    case Flexibility = 'flexibility';
    case Other = 'other';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Cardio => 'Cardio',
            self::Strength => 'Fuerza',
            self::Flexibility => 'Flexibilidad',
            self::Other => 'Otro',
        };
    }
}
