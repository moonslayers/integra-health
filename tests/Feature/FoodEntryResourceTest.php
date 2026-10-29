<?php

namespace Tests\Feature;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use App\Filament\Resources\FoodEntries\Pages\CreateFoodEntry;
use App\Filament\Resources\FoodEntries\Pages\ListFoodEntries;
use App\Models\Food;
use App\Models\FoodEntry;
use App\Services\HealthMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FoodEntryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_load_list_page(): void
    {
        Livewire::test(ListFoodEntries::class)
            ->assertOk();
    }

    public function test_guest_can_load_create_page(): void
    {
        Livewire::test(CreateFoodEntry::class)
            ->assertOk();
    }

    public function test_can_create_food_entry_and_calculate_portions(): void
    {
        $food = Food::factory()->create([
            'counts_toward_goal' => true,
            'portions_per_serving' => 2,
        ]);

        Livewire::test(CreateFoodEntry::class)
            ->fillForm([
                'food_id' => $food->id,
                'quantity' => 1.5,
                'consumed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $entry = FoodEntry::latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertEquals(3.0, app(HealthMetricsService::class)->portionsForFoodEntry($entry));
    }

    public function test_validation_requires_food_and_positive_quantity(): void
    {
        $food = Food::factory()->create();

        Livewire::test(CreateFoodEntry::class)
            ->fillForm([
                'food_id' => null,
                'quantity' => 0,
                'consumed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasFormErrors([
                'food_id' => 'required',
                'quantity' => 'min',
            ])
            ->assertNotNotified();
    }

    public function test_filter_by_category(): void
    {
        $fruitFood = Food::factory()->create(['category' => FoodCategory::Fruit, 'counts_toward_goal' => true]);
        $proteinFood = Food::factory()->create(['category' => FoodCategory::Protein, 'counts_toward_goal' => false]);

        $fruitEntry = FoodEntry::factory()->create(['food_id' => $fruitFood->id]);
        $proteinEntry = FoodEntry::factory()->create(['food_id' => $proteinFood->id]);

        Livewire::test(ListFoodEntries::class)
            ->filterTable('category', FoodCategory::Fruit->value)
            ->assertCanSeeTableRecords(collect([$fruitEntry]))
            ->assertCanNotSeeTableRecords(collect([$proteinEntry]));
    }

    public function test_can_create_food_inline_from_select_and_use_it_in_entry(): void
    {
        $component = Livewire::test(CreateFoodEntry::class)
            ->callFormComponentAction('food_id', 'createOption', data: [
                'name' => 'Manzana Roja',
                'category' => FoodCategory::Fruit->value,
                'serving_unit' => ServingUnit::Piece->value,
                'counts_toward_goal' => true,
                'portions_per_serving' => 0.5,
                'source' => 'Tabla SMA',
                'notes' => 'Fruta de prueba',
            ])
            ->assertHasNoFormErrors();

        $food = Food::query()->where('slug', 'manzana-roja')->first();
        $this->assertNotNull($food);
        $this->assertSame('Manzana Roja', $food->name);
        $this->assertSame(FoodCategory::Fruit, $food->category);
        $this->assertSame(ServingUnit::Piece, $food->serving_unit);
        $this->assertTrue($food->counts_toward_goal);
        $this->assertEquals(0.5, (float) $food->portions_per_serving);
        $this->assertSame('Tabla SMA', $food->source);
        $this->assertSame('Fruta de prueba', $food->notes);

        $component
            ->assertSchemaComponentStateSet('food_id', $food->id)
            ->fillForm([
                'quantity' => 2,
                'consumed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $entry = FoodEntry::latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame($food->id, $entry->food_id);
    }

    public function test_inline_food_creation_generates_unique_slug(): void
    {
        Food::factory()->create(['slug' => 'manzana-roja']);

        Livewire::test(CreateFoodEntry::class)
            ->callFormComponentAction('food_id', 'createOption', data: [
                'name' => 'Manzana Roja',
                'category' => FoodCategory::Fruit->value,
                'serving_unit' => ServingUnit::Piece->value,
                'counts_toward_goal' => false,
            ])
            ->assertHasNoFormErrors();

        $food = Food::query()->latest('id')->first();
        $this->assertNotNull($food);
        $this->assertSame('manzana-roja-2', $food->slug);
        $this->assertFalse($food->counts_toward_goal);
        $this->assertNull($food->portions_per_serving);
    }

    public function test_inline_food_creation_requires_portions_when_counts_toward_goal(): void
    {
        Livewire::test(CreateFoodEntry::class)
            ->callFormComponentAction('food_id', 'createOption', data: [
                'name' => 'Pera',
                'category' => FoodCategory::Fruit->value,
                'serving_unit' => ServingUnit::Piece->value,
                'counts_toward_goal' => true,
                'portions_per_serving' => null,
            ])
            ->assertHasFormErrors(['portions_per_serving']);

        $this->assertDatabaseMissing('foods', ['name' => 'Pera']);
    }
}
