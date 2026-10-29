<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PauseType: string implements HasLabel
{
    case Stretching = 'stretching';
    case Passive = 'passive';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Stretching => 'Estiramiento',
            self::Passive => 'Pasiva',
        };
    }
}
