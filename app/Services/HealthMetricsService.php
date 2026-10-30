<?php

namespace App\Services;

use App\Enums\WaterType;
use App\Models\ActivePause;
use App\Models\ExerciseSession;
use App\Models\FoodEntry;
use App\Models\HealthSetting;
use App\Models\WaterIntake;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HealthMetricsService
{
    public function timezone(): string
    {
        return (string) config('app.timezone');
    }

    public function now(): Carbon
    {
        return Carbon::now($this->timezone());
    }

    public function todayDate(): Carbon
    {
        return $this->now()->startOfDay();
    }

    public function portionsForFoodEntry(FoodEntry $entry): float
    {
        $entry->loadMissing('food');

        return (float) $entry->portions;
    }

    protected function dayBounds(Carbon|string|null $date = null): array
    {
        $tz = $this->timezone();
        $day = $date instanceof Carbon ? $date->copy()->timezone($tz) : Carbon::parse($date ?? 'now', $tz);
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        return [$start, $end];
    }

    protected function weekBounds(Carbon|string|null $date = null): array
    {
        $tz = $this->timezone();
        $day = $date instanceof Carbon ? $date->copy()->timezone($tz) : Carbon::parse($date ?? 'now', $tz);
        $start = $day->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        return [$start, $end];
    }

    public function todaySummary(Carbon|string|null $date = null): array
    {
        [$start, $end] = $this->dayBounds($date);

        $waterTotal = (int) WaterIntake::query()
            ->whereBetween('consumed_at', [$start, $end])
            ->sum('volume_ml');

        $waterCount = (int) WaterIntake::query()
            ->whereBetween('consumed_at', [$start, $end])
            ->count();

        $waterByType = WaterIntake::query()
            ->whereBetween('consumed_at', [$start, $end])
            ->select('type', DB::raw('SUM(volume_ml) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn ($v) => (int) $v)
            ->all();

        // ensure all types present with 0
        foreach (WaterType::cases() as $type) {
            $waterByType[$type->value] = $waterByType[$type->value] ?? 0;
        }

        $portions = (float) (FoodEntry::query()
            ->join('foods', 'foods.id', '=', 'food_entries.food_id')
            ->where('foods.counts_toward_goal', true)
            ->whereBetween('food_entries.consumed_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(food_entries.quantity * COALESCE(foods.portions_per_serving, 0)), 0) as total')
            ->value('total') ?? 0);

        $pauseRow = ActivePause::query()
            ->whereBetween('performed_at', [$start, $end])
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(minutes), 0) as m')
            ->first();

        $pausesCount = (int) ($pauseRow->c ?? 0);
        $pausesMinutes = (int) ($pauseRow->m ?? 0);

        return [
            'date' => $start->toDateString(),
            'water_total_ml' => $waterTotal,
            'water_count' => $waterCount,
            'water_by_type' => $waterByType,
            'fruit_veg_portions' => $portions,
            'pauses_count' => $pausesCount,
            'pauses_minutes' => $pausesMinutes,
        ];
    }

    public function goalProgress(Carbon|string|null $date = null): array
    {
        $today = $this->todaySummary($date);

        return $this->buildGoalProgress($today, $date);
    }

    public function dashboardSummary(Carbon|string|null $date = null): array
    {
        $today = $this->todaySummary($date);
        $progress = $this->buildGoalProgress($today, $date);
        $progress['today'] = $today;

        return $progress;
    }

    protected function buildGoalProgress(array $today, Carbon|string|null $date = null): array
    {
        $settings = HealthSetting::current();
        $exercise = $this->weeklyExerciseSummary($date);

        $waterCurrent = $today['water_total_ml'];
        $waterGoal = (int) $settings->water_goal_ml;
        $portionsCurrent = $today['fruit_veg_portions'];
        $portionsGoal = (float) $settings->fruit_veg_goal_portions;
        $pausesCurrent = $today['pauses_count'];
        $pausesGoal = (int) $settings->active_pause_goal_count;

        $waterPct = $waterGoal > 0 ? ($waterCurrent / $waterGoal) * 100 : 0.0;
        $portionsPct = $portionsGoal > 0 ? ($portionsCurrent / $portionsGoal) * 100 : 0.0;
        $pausesPct = $pausesGoal > 0 ? ($pausesCurrent / $pausesGoal) * 100 : 0.0;

        return [
            'date' => $today['date'],
            'water' => [
                'current' => $waterCurrent,
                'goal' => $waterGoal,
                'percentage' => $waterPct,
                'met' => $waterCurrent >= $waterGoal,
            ],
            'portions' => [
                'current' => $portionsCurrent,
                'goal' => $portionsGoal,
                'percentage' => $portionsPct,
                'met' => $portionsCurrent >= $portionsGoal,
            ],
            'pauses' => [
                'current' => $pausesCurrent,
                'goal' => $pausesGoal,
                'percentage' => $pausesPct,
                'met' => $pausesCurrent >= $pausesGoal,
            ],
            'exercise' => $exercise,
        ];
    }

    public function dailySeries(Carbon|string $from, Carbon|string $to): array
    {
        $tz = $this->timezone();
        $start = ($from instanceof Carbon ? $from->copy() : Carbon::parse($from, $tz))->timezone($tz)->startOfDay();
        $end = ($to instanceof Carbon ? $to->copy() : Carbon::parse($to, $tz))->timezone($tz)->startOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        // water
        $waterRows = WaterIntake::query()
            ->whereBetween('consumed_at', [$start, $end->copy()->endOfDay()])
            ->selectRaw('date(consumed_at) as d, COALESCE(SUM(volume_ml),0) as volume, COUNT(*) as freq')
            ->groupByRaw('date(consumed_at)')
            ->get()
            ->keyBy('d');

        $portionRows = FoodEntry::query()
            ->join('foods', 'foods.id', '=', 'food_entries.food_id')
            ->where('foods.counts_toward_goal', true)
            ->whereBetween('food_entries.consumed_at', [$start, $end->copy()->endOfDay()])
            ->selectRaw('date(food_entries.consumed_at) as d, COALESCE(SUM(food_entries.quantity * COALESCE(foods.portions_per_serving, 0)), 0) as total_portions')
            ->groupByRaw('date(food_entries.consumed_at)')
            ->get()
            ->keyBy('d');

        $pauseRows = ActivePause::query()
            ->whereBetween('performed_at', [$start, $end->copy()->endOfDay()])
            ->selectRaw('date(performed_at) as d, COUNT(*) as c, COALESCE(SUM(minutes),0) as m')
            ->groupByRaw('date(performed_at)')
            ->get()
            ->keyBy('d');

        $result = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $w = $waterRows->get($key);
            $p = $portionRows->get($key);
            $a = $pauseRows->get($key);

            $waterVolume = (int) ($w->volume ?? 0);
            $waterFreq = (int) ($w->freq ?? 0);
            $portions = (float) ($p->total_portions ?? 0);
            $pausesCount = (int) ($a->c ?? 0);
            $pausesMinutes = (int) ($a->m ?? 0);

            $day = [
                'date' => $key,
                'water_volume_ml' => $waterVolume,
                'water_count' => $waterFreq,
                'fruit_veg_portions' => $portions,
                'pauses_count' => $pausesCount,
                'pauses_minutes' => $pausesMinutes,
            ];

            $result[$key] = $day;
            $cursor->addDay();
        }

        return $result;
    }

    public function weeklyExerciseSummary(Carbon|string|null $date = null): array
    {
        [$start, $end] = $this->weekBounds($date);
        $settings = HealthSetting::current();

        $sessions = ExerciseSession::query()
            ->whereBetween('performed_at', [$start, $end])
            ->get(['minutes', 'performed_at']);

        $minutes = (int) $sessions->sum('minutes');
        $days = $sessions->map(fn ($s) => Carbon::parse($s->performed_at, $this->timezone())->toDateString())->unique()->count();

        $minutesGoal = (int) $settings->exercise_weekly_minutes_goal;
        $daysGoal = (int) $settings->exercise_weekly_days_goal;

        $minutesPct = $minutesGoal > 0 ? ($minutes / $minutesGoal) * 100 : 0.0;
        $daysPct = $daysGoal > 0 ? ($days / $daysGoal) * 100 : 0.0;

        return [
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'minutes' => $minutes,
            'days' => $days,
            'minutes_goal' => $minutesGoal,
            'days_goal' => $daysGoal,
            'minutes_met' => $minutes >= $minutesGoal,
            'days_met' => $days >= $daysGoal,
            'minutes_percentage' => $minutesPct,
            'days_percentage' => $daysPct,
        ];
    }
}
