<?php

namespace Database\Factories;

use App\Enums\PauseType;
use App\Models\ActivePause;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivePause>
 */
class ActivePauseFactory extends Factory
{
    protected $model = ActivePause::class;

    public function definition(): array
    {
        return [
            'minutes' => 5,
            'type' => fake()->randomElement(PauseType::cases()),
            'performed_at' => now(),
        ];
    }
}
