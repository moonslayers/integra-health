<?php

namespace Database\Factories;

use App\Enums\WaterType;
use App\Models\WaterIntake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaterIntake>
 */
class WaterIntakeFactory extends Factory
{
    protected $model = WaterIntake::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(WaterType::cases()),
            'volume_ml' => 250,
            'consumed_at' => now(),
        ];
    }
}
