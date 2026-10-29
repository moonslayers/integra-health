<?php

namespace Tests\Feature;

use App\Enums\WaterType;
use App\Filament\Widgets\ActivePausesChart;
use App\Filament\Widgets\ExerciseWeeklyChart;
use App\Filament\Widgets\FruitVegPortionsChart;
use App\Filament\Widgets\HealthStatsOverview;
use App\Filament\Widgets\WaterByTypeChart;
use App\Filament\Widgets\WaterFrequencyChart;
use App\Filament\Widgets\WaterVolumeChart;
use App\Models\ActivePause;
use App\Models\ExerciseSession;
use App\Models\Food;
use App\Models\FoodEntry;
use App\Models\HealthSetting;
use App\Models\WaterIntake;
use App\Services\HealthMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
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

    private function reflect(object $instance, string $method): mixed
    {
        $r = new \ReflectionMethod($instance, $method);
        $r->setAccessible(true);

        return $r->invoke($instance);
    }

    public function test_guest_can_access_homepage(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_widgets_render(): void
    {
        foreach ([
            HealthStatsOverview::class,
            WaterVolumeChart::class,
            WaterFrequencyChart::class,
            WaterByTypeChart::class,
            FruitVegPortionsChart::class,
            ActivePausesChart::class,
            ExerciseWeeklyChart::class,
        ] as $widget) {
            Livewire::test($widget)->assertSuccessful();
        }
    }

    public function test_stats_match_service(): void
    {
        HealthSetting::current()->update(['water_goal_ml' => 1000, 'fruit_veg_goal_portions' => 2, 'active_pause_goal_count' => 2]);

        WaterIntake::factory()->create(['volume_ml' => 500, 'consumed_at' => now()]);
        WaterIntake::factory()->create(['volume_ml' => 250, 'consumed_at' => now()]);

        $food = Food::factory()->create(['counts_toward_goal' => true, 'portions_per_serving' => 2]);
        FoodEntry::factory()->create(['food_id' => $food->id, 'quantity' => 1, 'consumed_at' => now()]);

        ActivePause::factory()->create(['minutes' => 5, 'performed_at' => now()]);
        ExerciseSession::factory()->create(['minutes' => 20, 'performed_at' => now()]);

        $today = $this->service->todaySummary();
        $progress = $this->service->goalProgress();
        $exercise = $this->service->weeklyExerciseSummary();

        $testable = Livewire::test(HealthStatsOverview::class);
        $stats = $this->reflect($testable->instance(), 'getStats');

        $byLabel = [];
        foreach ($stats as $stat) {
            $byLabel[$stat->getLabel()] = $stat->getValue();
        }

        $this->assertSame(sprintf('%d / %d ml', (int) $today['water_total_ml'], (int) $progress['water']['goal']), $byLabel['Agua de hoy']);
        $this->assertSame(sprintf('%.1f / %.1f', (float) $today['fruit_veg_portions'], (float) $progress['portions']['goal']), $byLabel['Porciones frutas/verduras']);
        $this->assertSame(sprintf('%d / %d', (int) $today['pauses_count'], (int) $progress['pauses']['goal']), $byLabel['Pausas activas']);
        $this->assertSame(sprintf('%d / %d min', (int) $exercise['minutes'], (int) $exercise['minutes_goal']), $byLabel['Ejercicio (minutos)']);
        $this->assertSame(sprintf('%d / %d días', (int) $exercise['days'], (int) $exercise['days_goal']), $byLabel['Ejercicio (días)']);
    }

    public function test_water_volume_chart_matches_service(): void
    {
        WaterIntake::factory()->create(['volume_ml' => 500, 'consumed_at' => now()->copy()->subDay()]);
        WaterIntake::factory()->create(['volume_ml' => 250, 'consumed_at' => now()]);

        $testable = Livewire::test(WaterVolumeChart::class);
        $data = $this->reflect($testable->instance(), 'getData');

        $service = $this->service;
        $end = $service->todayDate();
        $start = $end->copy()->subDays(29)->startOfDay();
        $series = $service->dailySeries($start, $end);

        $expected = array_map(fn ($d) => (int) $d['water_volume_ml'], array_values($series));
        $this->assertSame($expected, $data['datasets'][0]['data']);
        $this->assertCount(30, $data['labels']);
    }

    public function test_range_filter_changes_data(): void
    {
        WaterIntake::factory()->create(['volume_ml' => 100, 'consumed_at' => now()]);

        $testable7 = Livewire::test(WaterVolumeChart::class)->set('filter', '7');
        $data7 = $this->reflect($testable7->instance(), 'getData');

        $testable30 = Livewire::test(WaterVolumeChart::class)->set('filter', '30');
        $data30 = $this->reflect($testable30->instance(), 'getData');

        $this->assertCount(7, $data7['labels']);
        $this->assertCount(30, $data30['labels']);
    }

    public function test_water_by_type_chart(): void
    {
        foreach (WaterType::cases() as $type) {
            WaterIntake::factory()->create(['type' => $type, 'volume_ml' => 100, 'consumed_at' => now()]);
        }

        $testable = Livewire::test(WaterByTypeChart::class);
        $data = $this->reflect($testable->instance(), 'getData');

        $this->assertContains('Agua mineral', $data['labels']);

        foreach ($data['datasets'][0]['data'] as $value) {
            $this->assertSame(100, $value);
        }
    }
}
