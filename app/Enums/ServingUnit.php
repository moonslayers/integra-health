<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ServingUnit: string implements HasLabel
{
    case Piece = 'piece';
    case Cup = 'cup';
    case Portion = 'portion';
    case Gram = 'gram';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Piece => 'Pieza',
            self::Cup => 'Taza',
            self::Portion => 'Porción',
            self::Gram => 'Gramo',
        };
    }

    public function getPluralLabel(): string
    {
        return match ($this) {
            self::Piece => 'Piezas',
            self::Cup => 'Tazas',
            self::Portion => 'Porciones',
            self::Gram => 'Gramos',
        };
    }
}
