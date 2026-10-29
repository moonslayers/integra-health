<?php

namespace Tests\Feature;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use App\Filament\Resources\Food\Pages\CreateFood;
use App\Filament\Resources\Food\Pages\EditFood;
use App\Filament\Resources\Food\Pages\ListFood;
use App\Models\Food;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FoodResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_load_list_page(): void
    {
        Livewire::test(ListFood::class)
            ->assertOk();
    }

    public function test_guest_can_load_create_page(): void
    {
        Livewire::test(CreateFood::class)
            ->assertOk();
    }

    public function test_counts_toward_goal_toggle_appears_before_portions_per_serving(): void
    {
        $page = Livewire::test(CreateFood::class)
            ->fillForm(['counts_toward_goal' => true]);

        $names = collect($page->instance()->form->getComponents())
            ->map(fn ($component) => $component->getName())
            ->all();

        $toggleIndex = array_search('counts_toward_goal', $names, true);
        $portionsIndex = array_search('portions_per_serving', $names, true);

        $this->assertNotFalse($toggleIndex);
        $this->assertNotFalse($portionsIndex);
        $this->assertLessThan($portionsIndex, $toggleIndex);
    }

    public function test_can_create_food(): void
    {
        Livewire::test(CreateFood::class)
            ->fillForm([
                'name' => 'Manzana test',
                'slug' => 'manzana-test',
                'category' => FoodCategory::Fruit->value,
                'serving_unit' => ServingUnit::Piece->value,
                'portions_per_serving' => 2,
                'counts_toward_goal' => true,
                'source' => 'manual',
                'notes' => 'nota',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('foods', [
            'name' => 'Manzana test',
            'slug' => 'manzana-test',
        ]);
    }

    public function test_can_edit_food(): void
    {
        $food = Food::factory()->create();

        Livewire::test(EditFood::class, ['record' => $food->id])
            ->fillForm(['name' => 'Alimento actualizado'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('foods', [
            'id' => $food->id,
            'name' => 'Alimento actualizado',
        ]);
    }

    public function test_validation_requires_basic_fields(): void
    {
        Livewire::test(CreateFood::class)
            ->fillForm([
                'name' => null,
                'slug' => null,
                'category' => null,
                'serving_unit' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'slug' => 'required',
                'category' => 'required',
                'serving_unit' => 'required',
            ])
            ->assertNotNotified();
    }

    public function test_search_by_name(): void
    {
        $foods = Food::factory()->count(3)->create();

        Livewire::test(ListFood::class)
            ->searchTable($foods->first()->name)
            ->assertCanSeeTableRecords($foods->take(1))
            ->assertCanNotSeeTableRecords($foods->skip(1));
    }
}
