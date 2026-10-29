<?php

namespace Tests\Unit\Models;

use App\Models\HealthSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_singleton_returns_same_record(): void
    {
        $first = HealthSetting::current();
        $second = HealthSetting::current();

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('health_settings', 1);
    }

    public function test_defaults_are_applied(): void
    {
        $settings = HealthSetting::current();

        $this->assertSame(2000, $settings->water_goal_ml);
        $this->assertEquals('1.00', $settings->fruit_veg_goal_portions);
        $this->assertSame(2, $settings->active_pause_goal_count);
        $this->assertSame(5, $settings->active_pause_default_minutes);
        $this->assertSame(60, $settings->exercise_weekly_minutes_goal);
        $this->assertSame(3, $settings->exercise_weekly_days_goal);
        $this->assertSame(250, $settings->default_water_serving_ml);
    }
}
