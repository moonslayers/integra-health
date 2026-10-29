<?php

namespace Tests\Feature;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use App\Models\Food;
use App\Models\HealthSetting;
use Database\Seeders\FoodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_twice_does_not_duplicate_foods(): void
    {
        $this->seed(FoodSeeder::class);
        $first = Food::count();

        $this->seed(FoodSeeder::class);
        $second = Food::count();

        $this->assertSame($first, $second);
    }

    public function test_catalog_has_at_least_forty_foods(): void
    {
        $this->seed(FoodSeeder::class);

        $this->assertGreaterThanOrEqual(40, Food::count());
    }

    public function test_authoritative_examples_have_correct_equivalences(): void
    {
        $this->seed(FoodSeeder::class);

        $apple = Food::where('slug', 'manzana-roja')->first();
        $this->assertSame(FoodCategory::Fruit, $apple->category);
        $this->assertSame(ServingUnit::Piece, $apple->serving_unit);
        $this->assertEquals('2.00', $apple->portions_per_serving);
        $this->assertTrue($apple->counts_toward_goal);

        $strawberries = Food::where('slug', 'fresas')->first();
        $this->assertSame(FoodCategory::Fruit, $strawberries->category);
        $this->assertSame(ServingUnit::Cup, $strawberries->serving_unit);
        $this->assertEquals('1.00', $strawberries->portions_per_serving);
        $this->assertTrue($strawberries->counts_toward_goal);

        $broccoli = Food::where('slug', 'brocoli')->first();
        $this->assertSame(FoodCategory::Vegetable, $broccoli->category);
        $this->assertSame(ServingUnit::Portion, $broccoli->serving_unit);
        $this->assertEquals('1.00', $broccoli->portions_per_serving);
        $this->assertTrue($broccoli->counts_toward_goal);

        $carrot = Food::where('slug', 'zanahoria')->first();
        $this->assertSame(FoodCategory::Vegetable, $carrot->category);
        $this->assertSame(ServingUnit::Portion, $carrot->serving_unit);
        $this->assertEquals('1.00', $carrot->portions_per_serving);
        $this->assertTrue($carrot->counts_toward_goal);
    }

    public function test_only_fruits_and_vegetables_count_toward_goal(): void
    {
        $this->seed(FoodSeeder::class);

        $this->assertSame(
            Food::whereIn('category', [FoodCategory::Fruit, FoodCategory::Vegetable])->count(),
            Food::where('counts_toward_goal', true)->count()
        );

        $this->assertSame(0, Food::whereNotIn('category', [FoodCategory::Fruit, FoodCategory::Vegetable])->where('counts_toward_goal', true)->count());
    }

    public function test_database_seeder_creates_health_setting_singleton(): void
    {
        $this->seed();

        $this->assertDatabaseCount('health_settings', 1);
        $this->assertNotNull(HealthSetting::current());
    }
}
