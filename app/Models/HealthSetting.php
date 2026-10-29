<?php

namespace App\Models;

use Database\Factories\HealthSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'water_goal_ml',
    'fruit_veg_goal_portions',
    'active_pause_goal_count',
    'active_pause_default_minutes',
    'exercise_weekly_minutes_goal',
    'exercise_weekly_days_goal',
    'default_water_serving_ml',
])]
class HealthSetting extends Model
{
    /** @use HasFactory<HealthSettingFactory> */
    use HasFactory;

    protected $table = 'health_settings';

    protected function casts(): array
    {
        return [
            'water_goal_ml' => 'integer',
            'fruit_veg_goal_portions' => 'decimal:2',
            'active_pause_goal_count' => 'integer',
            'active_pause_default_minutes' => 'integer',
            'exercise_weekly_minutes_goal' => 'integer',
            'exercise_weekly_days_goal' => 'integer',
            'default_water_serving_ml' => 'integer',
        ];
    }

    public static function current(): self
    {
        $instance = static::query()->first();

        if ($instance === null) {
            $instance = static::query()->create([]);
            $instance->refresh();
        }

        return $instance;
    }
}
