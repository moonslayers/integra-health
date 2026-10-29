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

    public function test_quantity_with_unit_uses_singular_label_for_one(): void
    {
        $food = Food::factory()->create(['serving_unit' => ServingUnit::Cup]);

        $this->assertSame('1 taza', $food->quantityWithUnit(1));
    }

    public function test_quantity_with_unit_uses_plural_label_without_trailing_zeros_for_fractions(): void
    {
        $food = Food::factory()->create(['serving_unit' => ServingUnit::Cup]);

        $this->assertSame('1.5 tazas', $food->quantityWithUnit(1.5));
        $this->assertSame('1.5 tazas', $food->quantityWithUnit('1.50'));
    }

    public function test_quantity_with_unit_uses_plural_label_above_one(): void
    {
        $food = Food::factory()->create(['serving_unit' => ServingUnit::Gram]);

        $this->assertSame('250 gramos', $food->quantityWithUnit(250));
    }

    public function test_quantity_with_unit_falls_back_to_plain_quantity_without_unit(): void
    {
        $food = Food::factory()->make(['serving_unit' => null]);

        $this->assertSame('1.5', $food->quantityWithUnit(1.5));
    }

    public function test_name_with_unit_includes_unit_label(): void
    {
        $food = Food::factory()->create([
            'name' => 'Avena',
            'serving_unit' => ServingUnit::Cup,
        ]);

        $this->assertSame('Avena (taza)', $food->nameWithUnit());
    }

    public function test_name_with_unit_falls_back_to_name_without_unit(): void
    {
        $food = Food::factory()->make([
            'name' => 'Avena',
            'serving_unit' => null,
        ]);

        $this->assertSame('Avena', $food->nameWithUnit());
    }
}
