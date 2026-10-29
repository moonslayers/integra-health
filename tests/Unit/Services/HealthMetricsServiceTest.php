<?php

namespace Tests\Unit\Services;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use App\Enums\WaterType;
use App\Models\ActivePause;
use App\Models\ExerciseSession;
use App\Models\Food;
use App\Models\FoodEntry;
use App\Models\HealthSetting;
use App\Models\WaterIntake;
use App\Services\HealthMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    private HealthMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HealthMetricsService;
        Carbon::setTestNow(Carbon::parse('2026-10-29 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function food(array $attrs): Food
    {
        return Food::factory()->create(array_merge([
            'counts_toward_goal' => true,
            'portions_per_serving' => 1,
        ], $attrs));
    }

    public function test_portions_apple(): void
    {
        $food = $this->food(['name' => 'Manzana', 'portions_per_serving' => 2]);
        $entry = FoodEntry::factory()->create(['food_id' => $food->id, 'quantity' => 1]);
        $this->assertEquals(2.0, $this->service->portionsForFoodEntry($entry));
        $this->assertEquals(2.0, $entry->portions);
    }

    public function test_portions_strawberries_cup(): void
    {
        $food = $this->food(['portions_per_serving' => 1, 'serving_unit' => ServingUnit::Cup]);
        $entry = FoodEntry::factory()->create(['food_id' => $food->id, 'quantity' => 1]);
        $this->assertEquals(1.0, $entry->portions);
    }

    public function test_portions_broccoli(): void
    {
        $food = $this->food(['portions_per_serving' => 1]);
        $entry = FoodEntry::factory()->create(['food_id' => $food->id, 'quantity' => 1]);
        $this->assertEquals(1.0, $entry->portions);
    }

    public function test_portions_carrot(): void
    {
        $food = $this->food(['portions_per_serving' => 1]);
        $entry = FoodEntry::factory()->create(['food_id' => $food->id, 'quantity' => 1]);
        $this->assertEquals(1.0, $entry->portions);
    }

    public function test_quantity_doubles_portions(): void
    {
        $food = $this->food(['portions_per_serving' => 1]);
        $entry = FoodEntry::factory()->create(['food_id' => $food->id, 'quantity' => 2]);
        $this->assertEquals(2.0, $entry->portions);
    }

    public function test_non_counting_foods_do_not_sum(): void
    {
        $legume = Food::factory()->create(['counts_toward_goal' => false, 'portions_per_serving' => 2]);
        $meat = Food::factory()->create(['counts_toward_goal' => false, 'portions_per_serving' => 3, 'category' => FoodCategory::Protein]);
        $other = Food::factory()->create(['counts_toward_goal' => false, 'portions_per_serving' => 4, 'category' => FoodCategory::Other]);

        FoodEntry::factory()->create(['food_id' => $legume->id, 'quantity' => 1, 'consumed_at' => now()]);
        FoodEntry::factory()->create(['food_id' => $meat->id, 'quantity' => 1, 'consumed_at' => now()]);
        FoodEntry::factory()->create(['food_id' => $other->id, 'quantity' => 1, 'consumed_at' => now()]);

        $summary = $this->service->todaySummary();
        $this->assertEquals(0.0, $summary['fruit_veg_portions']);
    }

    public function test_all_drinks_sum_volume_and_frequency(): void
    {
        foreach (WaterType::cases() as $type) {
            WaterIntake::factory()->create(['type' => $type, 'volume_ml' => 250, 'consumed_at' => now()]);
        }
        $summary = $this->service->todaySummary();
        $this->assertSame(250 * count(WaterType::cases()), $summary['water_total_ml']);
        $this->assertSame(count(WaterType::cases()), $summary['water_count']);
    }

    public function test_mineral_water_sums_into_volume_and_breakdown(): void
    {
        WaterIntake::factory()->create(['type' => WaterType::Mineral, 'volume_ml' => 330, 'consumed_at' => now()]);

        $summary = $this->service->todaySummary();

        $this->assertSame(330, $summary['water_total_ml']);
        $this->assertSame(1, $summary['water_count']);
        $this->assertSame(330, $summary['water_by_type'][WaterType::Mineral->value]);
        $this->assertSame('Agua mineral', WaterType::Mineral->getLabel());
    }

    public function test_pauses_count_and_minutes(): void
    {
        ActivePause::factory()->create(['minutes' => 5, 'performed_at' => now()]);
        ActivePause::factory()->create(['minutes' => 10, 'performed_at' => now()]);
        $summary = $this->service->todaySummary();
        $this->assertSame(2, $summary['pauses_count']);
        $this->assertSame(15, $summary['pauses_minutes']);
    }

    public function test_exercise_meet_minutes_not_days(): void
    {
        $settings = HealthSetting::current();
        $settings->update(['exercise_weekly_minutes_goal' => 30, 'exercise_weekly_days_goal' => 3]);

        // one session 40 minutes on one day
        ExerciseSession::factory()->create(['minutes' => 40, 'performed_at' => now()]);

        $ex = $this->service->weeklyExerciseSummary();
        $this->assertTrue($ex['minutes_met']);
        $this->assertFalse($ex['days_met']);
    }

    public function test_exercise_meet_days_not_minutes(): void
    {
        $settings = HealthSetting::current();
        $settings->update(['exercise_weekly_minutes_goal' => 100, 'exercise_weekly_days_goal' => 2]);

        ExerciseSession::factory()->create(['minutes' => 10, 'performed_at' => now()]);
        ExerciseSession::factory()->create(['minutes' => 10, 'performed_at' => now()->copy()->addDay()]);

        $ex = $this->service->weeklyExerciseSummary();
        $this->assertFalse($ex['minutes_met']);
        $this->assertTrue($ex['days_met']);
    }

    public function test_days_without_data_zero(): void
    {
        $start = now()->copy()->subDays(2)->startOfDay();
        $end = now()->copy()->startOfDay();
        $series = $this->service->dailySeries($start, $end);
        $this->assertCount(3, $series);
        foreach ($series as $day) {
            $this->assertSame(0, $day['water_volume_ml']);
            $this->assertSame(0, $day['water_count']);
            $this->assertEquals(0.0, $day['fruit_veg_portions']);
            $this->assertSame(0, $day['pauses_count']);
            $this->assertSame(0, $day['pauses_minutes']);
        }
    }

    public function test_date_range_series(): void
    {
        WaterIntake::factory()->create(['volume_ml' => 500, 'consumed_at' => now()->copy()->subDay()]);
        $start = now()->copy()->subDays(2)->startOfDay();
        $end = now()->copy()->startOfDay();
        $series = $this->service->dailySeries($start, $end);
        $yesterday = now()->copy()->subDay()->toDateString();
        $this->assertSame(500, $series[$yesterday]['water_volume_ml']);
        $this->assertSame(1, $series[$yesterday]['water_count']);
    }

    public function test_week_starts_monday(): void
    {
        // Sunday
        $sunday = Carbon::parse('2026-10-25', config('app.timezone')); // Sunday
        $this->assertTrue($sunday->isSunday());

        ExerciseSession::factory()->create(['minutes' => 20, 'performed_at' => $sunday->copy()->setTime(10, 0)]);

        // Monday of that week
        $monday = $sunday->copy()->startOfWeek(Carbon::MONDAY);
        $ex = $this->service->weeklyExerciseSummary($monday);
        $this->assertSame(20, $ex['minutes']);
        $this->assertSame($monday->toDateString(), $ex['week_start']);
    }

    public function test_goal_progress(): void
    {
        HealthSetting::current()->update(['water_goal_ml' => 1000, 'fruit_veg_goal_portions' => 2, 'active_pause_goal_count' => 1]);
        WaterIntake::factory()->create(['volume_ml' => 1000, 'consumed_at' => now()]);
        $food = $this->food(['portions_per_serving' => 2]);
        FoodEntry::factory()->create(['food_id' => $food->id, 'quantity' => 1, 'consumed_at' => now()]);

        $progress = $this->service->goalProgress();
        $this->assertTrue($progress['water']['met']);
        $this->assertTrue($progress['portions']['met']);
    }

    public function test_series_keyed_by_date_accessible(): void
    {
        $series = $this->service->dailySeries(now()->copy()->subDay(), now());
        $this->assertArrayHasKey(now()->toDateString(), $series);
    }

    public function test_day_boundary_timezone(): void
    {
        $tz = config('app.timezone');
        $day = Carbon::parse('2026-10-29', $tz);
        $next = $day->copy()->addDay();

        WaterIntake::factory()->create(['volume_ml' => 100, 'consumed_at' => $day->copy()->setTime(23, 30)]);
        WaterIntake::factory()->create(['volume_ml' => 200, 'consumed_at' => $next->copy()->setTime(0, 30)]);

        $firstSummary = $this->service->todaySummary($day);
        $secondSummary = $this->service->todaySummary($next);

        $this->assertSame(100, $firstSummary['water_total_ml']);
        $this->assertSame(200, $secondSummary['water_total_ml']);

        $series = $this->service->dailySeries($day, $next);
        $this->assertSame(100, $series[$day->toDateString()]['water_volume_ml']);
        $this->assertSame(200, $series[$next->toDateString()]['water_volume_ml']);
    }

    public function test_daily_series_groups_by_local_day_at_tijuana_boundary(): void
    {
        $originalTimezone = config('app.timezone');
        config(['app.timezone' => 'America/Tijuana']);
        Carbon::setTestNow(Carbon::parse('2026-10-29 12:00:00', 'America/Tijuana'));

        $tz = 'America/Tijuana';

        WaterIntake::factory()->create([
            'volume_ml' => 100,
            'consumed_at' => Carbon::parse('2026-10-29 23:30:00', $tz),
        ]);
        WaterIntake::factory()->create([
            'volume_ml' => 200,
            'consumed_at' => Carbon::parse('2026-10-30 00:30:00', $tz),
        ]);
        ActivePause::factory()->create([
            'minutes' => 5,
            'performed_at' => Carbon::parse('2026-10-29 23:30:00', $tz),
        ]);

        $series = $this->service->dailySeries('2026-10-29', '2026-10-30');

        $this->assertSame(100, $series['2026-10-29']['water_volume_ml']);
        $this->assertSame(1, $series['2026-10-29']['water_count']);
        $this->assertSame(200, $series['2026-10-30']['water_volume_ml']);
        $this->assertSame(1, $series['2026-10-30']['water_count']);
        $this->assertSame(5, $series['2026-10-29']['pauses_minutes']);
        $this->assertSame(0, $series['2026-10-30']['pauses_minutes']);

        config(['app.timezone' => $originalTimezone]);
    }
}
