<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\FoodCategory;
use App\Enums\PauseType;
use App\Enums\ServingUnit;
use App\Enums\WaterType;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\RecentActivity;
use App\Models\ActivePause;
use App\Models\ExerciseSession;
use App\Models\Food;
use App\Models\FoodEntry;
use App\Models\HealthSetting;
use App\Models\WaterIntake;
use App\Services\HealthMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_dashboard(): void
    {
        Livewire::test(Dashboard::class)->assertOk();
    }

    public function test_water_action_creates_record_with_default_volume(): void
    {
        HealthSetting::current();

        Livewire::test(Dashboard::class)
            ->callAction('registrarAgua', data: [
                'type' => WaterType::Natural->value,
                'consumed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('water_intakes', [
            'type' => WaterType::Natural->value,
            'volume_ml' => 250,
        ]);
    }

    public function test_water_action_creates_record_with_volume(): void
    {
        Livewire::test(Dashboard::class)
            ->callAction('registrarAgua', data: [
                'type' => WaterType::Coffee->value,
                'volume_ml' => 300,
                'consumed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('water_intakes', [
            'type' => WaterType::Coffee->value,
            'volume_ml' => 300,
        ]);
    }

    public function test_water_action_creates_record_with_notes(): void
    {
        Livewire::test(Dashboard::class)
            ->callAction('registrarAgua', data: [
                'type' => WaterType::Mineral->value,
                'volume_ml' => 500,
                'consumed_at' => now()->format('Y-m-d H:i:s'),
                'notes' => 'Marca preferida',
            ])
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('water_intakes', [
            'type' => WaterType::Mineral->value,
            'volume_ml' => 500,
            'notes' => 'Marca preferida',
        ]);
    }

    public function test_water_action_validation(): void
    {
        Livewire::test(Dashboard::class)
            ->callAction('registrarAgua', data: [
                'type' => null,
                'volume_ml' => 0,
                'consumed_at' => null,
            ])
            ->assertHasFormErrors(['type', 'volume_ml', 'consumed_at'])
            ->assertNotNotified();
    }

    public function test_pause_action_uses_default_minutes(): void
    {
        HealthSetting::current()->update(['active_pause_default_minutes' => 7]);

        Livewire::test(Dashboard::class)
            ->callAction('registrarPausa', data: [
                'type' => PauseType::Stretching->value,
                'performed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('active_pauses', [
            'type' => PauseType::Stretching->value,
            'minutes' => 7,
        ]);
    }

    public function test_pause_action_creates_record(): void
    {
        Livewire::test(Dashboard::class)
            ->callAction('registrarPausa', data: [
                'minutes' => 10,
                'type' => PauseType::Passive->value,
                'performed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('active_pauses', [
            'minutes' => 10,
            'type' => PauseType::Passive->value,
        ]);
    }

    public function test_pause_action_validation(): void
    {
        Livewire::test(Dashboard::class)
            ->callAction('registrarPausa', data: [
                'minutes' => 0,
                'type' => null,
                'performed_at' => null,
            ])
            ->assertHasFormErrors(['minutes', 'type', 'performed_at'])
            ->assertNotNotified();
    }

    public function test_food_action_creates_entry_and_calculates_portions(): void
    {
        $food = Food::factory()->create([
            'counts_toward_goal' => true,
            'portions_per_serving' => 2,
        ]);

        Livewire::test(Dashboard::class)
            ->callAction('registrarAlimento', data: [
                'food_id' => $food->id,
                'quantity' => 1.5,
                'consumed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertNotified()
            ->assertHasNoFormErrors();

        $entry = FoodEntry::latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertEquals(3.0, app(HealthMetricsService::class)->portionsForFoodEntry($entry));
    }

    public function test_food_action_validation(): void
    {
        Livewire::test(Dashboard::class)
            ->callAction('registrarAlimento', data: [
                'food_id' => null,
                'quantity' => 0,
                'consumed_at' => null,
            ])
            ->assertHasFormErrors(['food_id', 'quantity', 'consumed_at'])
            ->assertNotNotified();
    }

    public function test_food_action_creates_entry_with_notes(): void
    {
        $food = Food::factory()->create();
        $notes = 'Porción de la media tarde';

        Livewire::test(Dashboard::class)
            ->callAction('registrarAlimento', data: [
                'food_id' => $food->id,
                'quantity' => 2,
                'consumed_at' => now()->format('Y-m-d H:i:s'),
                'notes' => $notes,
            ])
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('food_entries', [
            'food_id' => $food->id,
            'notes' => $notes,
        ]);
    }

    public function test_food_action_quantity_shows_unit_of_selected_food(): void
    {
        $food = Food::factory()->create([
            'name' => 'Avena',
            'serving_unit' => ServingUnit::Cup,
        ]);

        Livewire::test(Dashboard::class)
            ->mountAction('registrarAlimento')
            ->assertMountedActionModalDontSee('tazas')
            ->fillForm([
                'food_id' => $food->id,
                'quantity' => 1.5,
            ])
            ->assertMountedActionModalSee('tazas');
    }

    public function test_food_action_can_create_food_inline_and_register_entry(): void
    {
        $component = Livewire::test(Dashboard::class)
            ->mountAction('registrarAlimento')
            ->callFormComponentAction('food_id', 'createOption', data: [
                'name' => 'Plátano',
                'category' => FoodCategory::Fruit->value,
                'serving_unit' => ServingUnit::Piece->value,
                'counts_toward_goal' => true,
                'portions_per_serving' => 0.25,
            ], formName: 'mountedActionSchema0')
            ->assertHasNoFormErrors();

        $food = Food::query()->where('slug', 'platano')->first();
        $this->assertNotNull($food);
        $this->assertSame(FoodCategory::Fruit, $food->category);
        $this->assertSame(ServingUnit::Piece, $food->serving_unit);
        $this->assertTrue($food->counts_toward_goal);
        $this->assertEquals(0.25, (float) $food->portions_per_serving);

        $component
            ->assertActionDataSet(['food_id' => $food->id])
            ->fillForm([
                'quantity' => 2,
                'consumed_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->callMountedAction()
            ->assertNotified()
            ->assertHasNoFormErrors();

        $entry = FoodEntry::latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame($food->id, $entry->food_id);
    }

    public function test_exercise_action_creates_record(): void
    {
        Livewire::test(Dashboard::class)
            ->callAction('registrarEjercicio', data: [
                'minutes' => 30,
                'performed_at' => now()->format('Y-m-d H:i:s'),
                'type' => ExerciseType::Cardio->value,
                'notes' => 'Nota de prueba',
            ])
            ->assertNotified()
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('exercise_sessions', [
            'minutes' => 30,
            'type' => ExerciseType::Cardio->value,
            'notes' => 'Nota de prueba',
        ]);
    }

    public function test_exercise_action_validation(): void
    {
        Livewire::test(Dashboard::class)
            ->callAction('registrarEjercicio', data: [
                'minutes' => 0,
                'performed_at' => null,
            ])
            ->assertHasFormErrors(['minutes', 'performed_at'])
            ->assertNotNotified();
    }

    public function test_recent_activity_shows_four_types(): void
    {
        $food = Food::factory()->create();

        WaterIntake::factory()->create();
        ActivePause::factory()->create();
        FoodEntry::factory()->create(['food_id' => $food->id]);
        ExerciseSession::factory()->create();

        Livewire::test(RecentActivity::class)
            ->assertSuccessful()
            ->assertSee('Agua')
            ->assertSee('Pausa activa')
            ->assertSee('Alimento')
            ->assertSee('Ejercicio');
    }

    public function test_recent_activity_shows_water_note_when_present(): void
    {
        WaterIntake::factory()->create([
            'type' => WaterType::Mineral,
            'notes' => 'Extra de magnesio',
        ]);

        Livewire::test(RecentActivity::class)
            ->assertSuccessful()
            ->assertSee('Extra de magnesio');
    }
}
