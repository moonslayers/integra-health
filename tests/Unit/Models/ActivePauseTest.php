<?php

namespace Tests\Unit\Models;

use App\Enums\PauseType;
use App\Models\ActivePause;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivePauseTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_type_to_enum(): void
    {
        $pause = ActivePause::factory()->create(['type' => PauseType::Passive]);

        $this->assertSame(PauseType::Passive, $pause->fresh()->type);
    }

    public function test_database_default_minutes(): void
    {
        $pause = ActivePause::create([
            'type' => PauseType::Stretching,
            'performed_at' => now(),
        ]);

        $this->assertSame(5, $pause->fresh()->minutes);
    }
}
