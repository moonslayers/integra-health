<?php

namespace Tests\Unit\Enums;

use App\Enums\ServingUnit;
use PHPUnit\Framework\TestCase;

class ServingUnitTest extends TestCase
{
    public function test_plural_label_is_available_for_every_unit(): void
    {
        $this->assertSame('Piezas', ServingUnit::Piece->getPluralLabel());
        $this->assertSame('Tazas', ServingUnit::Cup->getPluralLabel());
        $this->assertSame('Porciones', ServingUnit::Portion->getPluralLabel());
        $this->assertSame('Gramos', ServingUnit::Gram->getPluralLabel());
    }
}
