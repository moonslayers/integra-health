<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\FoodEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodEntry>
 */
class FoodEntryFactory extends Factory
{
    protected $model = FoodEntry::class;

    public function definition(): array
    {
        return [
            'food_id' => Food::factory(),
            'quantity' => 1,
            'consumed_at' => now(),
        ];
    }
}
