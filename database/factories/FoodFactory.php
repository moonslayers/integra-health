<?php

namespace Database\Factories;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use App\Models\Food;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Food>
 */
class FoodFactory extends Factory
{
    protected $model = Food::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'category' => fake()->randomElement(FoodCategory::cases()),
            'serving_unit' => fake()->randomElement(ServingUnit::cases()),
            'portions_per_serving' => null,
            'counts_toward_goal' => false,
            'source' => null,
            'notes' => null,
        ];
    }
}
