<?php

namespace App\Filament\Concerns;

trait HasRangeFilter
{
    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 días',
            '30' => '30 días',
            '90' => '90 días',
        ];
    }

    protected function rangeDays(): int
    {
        return in_array($this->filter, ['7', '30', '90'], true) ? (int) $this->filter : 30;
    }
}
