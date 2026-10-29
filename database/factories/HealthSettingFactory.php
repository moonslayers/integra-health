<?php

namespace Database\Factories;

use App\Models\HealthSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthSetting>
 */
class HealthSettingFactory extends Factory
{
    protected $model = HealthSetting::class;

    public function definition(): array
    {
        return [
            'water_goal_ml' => 2000,
            'fruit_veg_goal_portions' => 1,
            'active_pause_goal_count' => 2,
            'active_pause_default_minutes' => 5,
            'exercise_weekly_minutes_goal' => 60,
            'exercise_weekly_days_goal' => 3,
            'default_water_serving_ml' => 250,
        ];
    }
}
