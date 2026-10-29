<?php

namespace Tests\Unit\Models;

use App\Enums\ExerciseType;
use App\Models\ExerciseSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_type_to_enum(): void
    {
        $session = ExerciseSession::factory()->create(['type' => ExerciseType::Cardio]);

        $this->assertSame(ExerciseType::Cardio, $session->fresh()->type);
    }

    public function test_nullable_type(): void
    {
        $session = ExerciseSession::create([
            'minutes' => 30,
            'performed_at' => now(),
            'type' => null,
        ]);

        $this->assertNull($session->fresh()->type);
    }
}
