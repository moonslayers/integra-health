<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum FoodCategory: string implements HasLabel
{
    case Fruit = 'fruit';
    case Vegetable = 'vegetable';
    case Legume = 'legume';
    case Protein = 'protein';
    case Grain = 'grain';
    case Dairy = 'dairy';
    case Other = 'other';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Fruit => 'Fruta',
            self::Vegetable => 'Verdura',
            self::Legume => 'Legumbre',
            self::Protein => 'Proteína',
            self::Grain => 'Grano',
            self::Dairy => 'Lácteo',
            self::Other => 'Otro',
        };
    }
}
