<?php

namespace Tests\Feature;

use App\Filament\Pages\HealthSettings;
use App\Models\HealthSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HealthSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_load_page(): void
    {
        Livewire::test(HealthSettings::class)
            ->assertOk();
    }

    public function test_defaults_are_loaded(): void
    {
        Livewire::test(HealthSettings::class)
            ->assertSchemaStateSet([
                'water_goal_ml' => 2000,
                'fruit_veg_goal_portions' => '1.00',
                'active_pause_goal_count' => 2,
                'active_pause_default_minutes' => 5,
                'exercise_weekly_minutes_goal' => 60,
                'exercise_weekly_days_goal' => 3,
                'default_water_serving_ml' => 250,
            ]);
    }

    public function test_can_save_changes(): void
    {
        Livewire::test(HealthSettings::class)
            ->fillForm([
                'water_goal_ml' => 2500,
                'fruit_veg_goal_portions' => 3.5,
                'active_pause_goal_count' => 4,
                'active_pause_default_minutes' => 10,
                'exercise_weekly_minutes_goal' => 120,
                'exercise_weekly_days_goal' => 5,
                'default_water_serving_ml' => 300,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $settings = HealthSetting::current();
        $this->assertSame(2500, $settings->water_goal_ml);
        $this->assertEquals('3.50', $settings->fruit_veg_goal_portions);
        $this->assertSame(4, $settings->active_pause_goal_count);
        $this->assertSame(10, $settings->active_pause_default_minutes);
        $this->assertSame(120, $settings->exercise_weekly_minutes_goal);
        $this->assertSame(5, $settings->exercise_weekly_days_goal);
        $this->assertSame(300, $settings->default_water_serving_ml);
    }

    public function test_validation_rejects_invalid_values(): void
    {
        Livewire::test(HealthSettings::class)
            ->fillForm([
                'water_goal_ml' => 0,
                'fruit_veg_goal_portions' => 0,
                'active_pause_goal_count' => 0,
                'active_pause_default_minutes' => 1.5,
                'exercise_weekly_minutes_goal' => 0,
                'exercise_weekly_days_goal' => 0,
                'default_water_serving_ml' => -1,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'water_goal_ml',
                'fruit_veg_goal_portions',
                'active_pause_goal_count',
                'active_pause_default_minutes',
                'exercise_weekly_minutes_goal',
                'exercise_weekly_days_goal',
                'default_water_serving_ml',
            ]);
    }
}
