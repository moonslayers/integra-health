<?php

namespace App\Models;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use Database\Factories\FoodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'category', 'serving_unit', 'portions_per_serving', 'counts_toward_goal', 'source', 'notes'])]
class Food extends Model
{
    /** @use HasFactory<FoodFactory> */
    use HasFactory;

    protected $table = 'foods';

    protected function casts(): array
    {
        return [
            'category' => FoodCategory::class,
            'serving_unit' => ServingUnit::class,
            'portions_per_serving' => 'decimal:2',
            'counts_toward_goal' => 'boolean',
        ];
    }

    public function foodEntries(): HasMany
    {
        return $this->hasMany(FoodEntry::class);
    }

    public function unitLabelForQuantity(string|float|int $quantity): string
    {
        $unit = $this->serving_unit;

        if ($unit === null) {
            return '';
        }

        $label = (float) $quantity === 1.0
            ? $unit->getLabel()
            : $unit->getPluralLabel();

        return mb_strtolower((string) $label);
    }

    public function quantityWithUnit(string|float|int $quantity): string
    {
        $formattedQuantity = $this->formatQuantity($quantity);
        $unitLabel = $this->unitLabelForQuantity($quantity);

        if ($unitLabel === '') {
            return $formattedQuantity;
        }

        return "{$formattedQuantity} {$unitLabel}";
    }

    public function nameWithUnit(): string
    {
        $unitLabel = $this->unitLabelForQuantity(1);

        if ($unitLabel === '') {
            return $this->name;
        }

        return "{$this->name} ({$unitLabel})";
    }

    private function formatQuantity(string|float|int $quantity): string
    {
        $text = (string) $quantity;

        if (str_contains($text, '.')) {
            $text = rtrim(rtrim($text, '0'), '.');
        }

        return $text;
    }
}
