<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum WaterType: string implements HasLabel
{
    case Natural = 'natural';
    case ColdInfusion = 'cold_infusion';
    case HotInfusion = 'hot_infusion';
    case Coffee = 'coffee';
    case Mineral = 'mineral';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Natural => 'Agua natural',
            self::ColdInfusion => 'Infusión fría',
            self::HotInfusion => 'Infusión caliente',
            self::Coffee => 'Café',
            self::Mineral => 'Agua mineral',
        };
    }
}
