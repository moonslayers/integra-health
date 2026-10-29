<?php

namespace Database\Factories;

use App\Enums\ExerciseType;
use App\Models\ExerciseSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseSession>
 */
class ExerciseSessionFactory extends Factory
{
    protected $model = ExerciseSession::class;

    public function definition(): array
    {
        return [
            'minutes' => fake()->numberBetween(10, 60),
            'performed_at' => now(),
            'type' => fake()->randomElement(ExerciseType::cases()),
            'notes' => null,
        ];
    }
}
