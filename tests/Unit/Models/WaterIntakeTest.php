<?php

namespace Tests\Unit\Models;

use App\Enums\WaterType;
use App\Models\WaterIntake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WaterIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_type_to_enum(): void
    {
        $intake = WaterIntake::factory()->create(['type' => WaterType::Coffee]);

        $this->assertSame(WaterType::Coffee, $intake->fresh()->type);
    }

    public function test_database_default_volume(): void
    {
        $intake = WaterIntake::create([
            'type' => WaterType::Natural,
            'consumed_at' => now(),
        ]);

        $this->assertSame(250, $intake->fresh()->volume_ml);
    }

    public function test_consumed_at_is_carbon(): void
    {
        $intake = WaterIntake::factory()->create();

        $this->assertInstanceOf(Carbon::class, $intake->consumed_at);
    }
}
