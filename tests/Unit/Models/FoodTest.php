<?php

namespace Tests\Unit\Models;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use App\Models\Food;
use App\Models\FoodEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_category_and_serving_unit(): void
    {
        $food = Food::factory()->create([
            'category' => FoodCategory::Fruit,
            'serving_unit' => ServingUnit::Piece,
        ]);

        $this->assertSame(FoodCategory::Fruit, $food->fresh()->category);
        $this->assertSame(ServingUnit::Piece, $food->fresh()->serving_unit);
    }

    public function test_counts_toward_goal_casts_to_bool(): void
    {
        $food = Food::factory()->create(['counts_toward_goal' => 1]);

        $this->assertTrue($food->fresh()->counts_toward_goal);
    }

    public function test_has_many_food_entries(): void
    {
        $food = Food::factory()->create();
        FoodEntry::factory()->count(2)->create(['food_id' => $food->id]);

        $this->assertCount(2, $food->fresh()->foodEntries);
    }
}
