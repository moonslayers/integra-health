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
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    private HealthMetricsService $service;

    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HealthMetricsService;
        $this->originalTimezone = (string) config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-10-29 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        config(['app.timezone' => $this->originalTimezone]);
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

    public function test_fruit_veg_portions_chart_matches_service(): void
    {
        $food = Food::factory()->create(['counts_toward_goal' => true, 'portions_per_serving' => 2]);
        FoodEntry::factory()->create(['food_id' => $food->id, 'quantity' => 1.5, 'consumed_at' => now()->copy()->subDay()]);
        FoodEntry::factory()->create(['food_id' => $food->id, 'quantity' => 1, 'consumed_at' => now()]);

        $testable = Livewire::test(FruitVegPortionsChart::class);
        $data = $this->reflect($testable->instance(), 'getData');

        $service = $this->service;
        $end = $service->todayDate();
        $start = $end->copy()->subDays(29)->startOfDay();
        $series = $service->dailySeries($start, $end);

        $expected = array_map(fn ($d) => (float) $d['fruit_veg_portions'], array_values($series));
        $this->assertSame($expected, $data['datasets'][0]['data']);
        $this->assertCount(30, $data['labels']);
        $this->assertSame(5.0, array_sum($data['datasets'][0]['data']));
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

    public function test_water_by_type_chart_uses_local_day_boundaries(): void
    {
        config(['app.timezone' => 'America/Tijuana']);
        Carbon::setTestNow(Carbon::parse('2026-10-29 00:30:00', 'America/Tijuana'));

        $tz = 'America/Tijuana';

        // Just inside the 7-day local window (start = 2026-10-23 00:00 Tijuana).
        WaterIntake::factory()->create([
            'type' => WaterType::Mineral,
            'volume_ml' => 150,
            'consumed_at' => Carbon::parse('2026-10-23 00:30:00', $tz),
        ]);
        // Just outside the lower bound of the local window.
        WaterIntake::factory()->create([
            'type' => WaterType::Mineral,
            'volume_ml' => 300,
            'consumed_at' => Carbon::parse('2026-10-22 23:30:00', $tz),
        ]);
        // After today's local end of day.
        WaterIntake::factory()->create([
            'type' => WaterType::Coffee,
            'volume_ml' => 400,
            'consumed_at' => Carbon::parse('2026-10-30 00:30:00', $tz),
        ]);

        $testable = Livewire::test(WaterByTypeChart::class)->set('filter', '7');
        $data = $this->reflect($testable->instance(), 'getData');

        $byLabel = array_combine($data['labels'], $data['datasets'][0]['data']);

        $this->assertSame(150, $byLabel['Agua mineral']);
        $this->assertSame(0, $byLabel['Café']);
    }

    public function test_range_filter_includes_90_days(): void
    {
        WaterIntake::factory()->create(['volume_ml' => 100, 'consumed_at' => now()->copy()->subDays(45)]);
        WaterIntake::factory()->create(['volume_ml' => 200, 'consumed_at' => now()]);

        $testable = Livewire::test(WaterVolumeChart::class)->set('filter', '90');
        $data = $this->reflect($testable->instance(), 'getData');

        $this->assertCount(90, $data['labels']);
        $this->assertCount(90, $data['datasets'][0]['data']);
        $this->assertContains(100, $data['datasets'][0]['data']);
        $this->assertContains(200, $data['datasets'][0]['data']);
    }

    public function test_active_pauses_chart_includes_minutes_for_90_day_range(): void
    {
        ActivePause::factory()->create(['minutes' => 7, 'performed_at' => now()->copy()->subDays(40)]);
        ActivePause::factory()->create(['minutes' => 5, 'performed_at' => now()]);

        $testable = Livewire::test(ActivePausesChart::class)->set('filter', '90');
        $data = $this->reflect($testable->instance(), 'getData');

        $this->assertCount(90, $data['labels']);
        $this->assertSame(['Cantidad', 'Minutos'], array_column($data['datasets'], 'label'));
        $this->assertContains(7, $data['datasets'][1]['data']);
        $this->assertContains(5, $data['datasets'][1]['data']);
        $this->assertSame(12, array_sum($data['datasets'][1]['data']));
        $this->assertSame(2, array_sum($data['datasets'][0]['data']));
    }

    public function test_stats_overview_shows_pause_minutes(): void
    {
        ActivePause::factory()->create(['minutes' => 7, 'performed_at' => now()]);
        ActivePause::factory()->create(['minutes' => 5, 'performed_at' => now()]);

        $testable = Livewire::test(HealthStatsOverview::class);
        $stats = collect($this->reflect($testable->instance(), 'getStats'));

        $pauses = $stats->first(fn (Stat $stat) => $stat->getLabel() === 'Pausas activas');

        $this->assertNotNull($pauses);
        $this->assertSame('12 minutos totales', $pauses->getDescription());
    }
}
