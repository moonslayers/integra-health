<?php

namespace Tests\Unit\Models;

use App\Models\Food;
use App\Models\FoodEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_food(): void
    {
        $food = Food::factory()->create();
        $entry = FoodEntry::factory()->create(['food_id' => $food->id]);

        $this->assertSame($food->id, $entry->fresh()->food->id);
    }

    public function test_database_default_quantity(): void
    {
        $food = Food::factory()->create();
        $entry = FoodEntry::create([
            'food_id' => $food->id,
            'consumed_at' => now(),
        ]);

        $this->assertEquals('1.00', $entry->fresh()->quantity);
    }

    public function test_cascade_delete(): void
    {
        $food = Food::factory()->create();
        FoodEntry::factory()->create(['food_id' => $food->id]);

        $food->delete();

        $this->assertDatabaseCount('food_entries', 0);
    }
}
