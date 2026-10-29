<?php

namespace Tests\Feature;

use App\Enums\WaterType;
use App\Filament\Resources\WaterIntakes\Pages\CreateWaterIntake;
use App\Filament\Resources\WaterIntakes\Pages\EditWaterIntake;
use App\Filament\Resources\WaterIntakes\Pages\ListWaterIntakes;
use App\Models\HealthSetting;
use App\Models\WaterIntake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WaterIntakeResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_list_page(): void
    {
        Livewire::test(ListWaterIntakes::class)
            ->assertOk();
    }

    public function test_guest_can_access_create_page(): void
    {
        Livewire::test(CreateWaterIntake::class)
            ->assertOk();
    }

    public function test_guest_can_create_water_intake(): void
    {
        Livewire::test(CreateWaterIntake::class)
            ->fillForm([
                'type' => WaterType::ColdInfusion->value,
                'volume_ml' => 300,
                'consumed_at' => now(),
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas('water_intakes', [
            'type' => WaterType::ColdInfusion->value,
            'volume_ml' => 300,
        ]);
    }

    public function test_guest_can_create_water_intake_with_notes(): void
    {
        Livewire::test(CreateWaterIntake::class)
            ->fillForm([
                'type' => WaterType::Mineral->value,
                'volume_ml' => 500,
                'consumed_at' => now(),
                'notes' => 'Sin gas, bien fría',
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas('water_intakes', [
            'type' => WaterType::Mineral->value,
            'volume_ml' => 500,
            'notes' => 'Sin gas, bien fría',
        ]);
    }

    public function test_notes_are_optional_when_creating_water_intake(): void
    {
        Livewire::test(CreateWaterIntake::class)
            ->fillForm([
                'type' => WaterType::Mineral->value,
                'volume_ml' => 300,
                'consumed_at' => now(),
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        $intake = WaterIntake::query()->latest('id')->first();
        $this->assertNotNull($intake);
        $this->assertNull($intake->notes);
    }

    public function test_default_volume_is_applied_when_omitted(): void
    {
        HealthSetting::current();

        Livewire::test(CreateWaterIntake::class)
            ->fillForm([
                'type' => WaterType::Natural->value,
                'consumed_at' => now(),
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas('water_intakes', [
            'type' => WaterType::Natural->value,
            'volume_ml' => 250,
        ]);
    }

    public function test_guest_can_edit_water_intake(): void
    {
        $intake = WaterIntake::factory()->create();

        Livewire::test(EditWaterIntake::class, ['record' => $intake->id])
            ->fillForm([
                'volume_ml' => 400,
            ])
            ->call('save')
            ->assertNotified();

        $this->assertDatabaseHas('water_intakes', [
            'id' => $intake->id,
            'volume_ml' => 400,
        ]);
    }

    public function test_volume_validation(): void
    {
        Livewire::test(CreateWaterIntake::class)
            ->fillForm([
                'type' => WaterType::Natural->value,
                'volume_ml' => 0,
                'consumed_at' => now(),
            ])
            ->call('create')
            ->assertHasFormErrors(['volume_ml'])
            ->assertNotNotified();
    }

    public function test_list_shows_records(): void
    {
        $intakes = WaterIntake::factory()->count(3)->create();

        Livewire::test(ListWaterIntakes::class)
            ->assertCanSeeTableRecords($intakes);
    }

    public function test_list_shows_notes_when_present(): void
    {
        $intake = WaterIntake::factory()->create(['notes' => 'Con limón']);

        Livewire::test(ListWaterIntakes::class)
            ->assertCanSeeTableRecords([$intake])
            ->assertSee('Con limón');
    }
}
