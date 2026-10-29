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
}
