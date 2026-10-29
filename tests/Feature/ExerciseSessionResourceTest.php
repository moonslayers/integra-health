<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Filament\Resources\ExerciseSessions\Pages\CreateExerciseSession;
use App\Filament\Resources\ExerciseSessions\Pages\EditExerciseSession;
use App\Filament\Resources\ExerciseSessions\Pages\ListExerciseSessions;
use App\Models\ExerciseSession;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExerciseSessionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_list_page(): void
    {
        Livewire::test(ListExerciseSessions::class)
            ->assertOk();
    }

    public function test_guest_can_access_create_page(): void
    {
        Livewire::test(CreateExerciseSession::class)
            ->assertOk();
    }

    public function test_guest_can_create_session(): void
    {
        Livewire::test(CreateExerciseSession::class)
            ->fillForm([
                'minutes' => 30,
                'performed_at' => now(),
                'type' => ExerciseType::Cardio->value,
                'notes' => 'Nota de prueba',
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas('exercise_sessions', [
            'minutes' => 30,
            'type' => ExerciseType::Cardio->value,
            'notes' => 'Nota de prueba',
        ]);
    }

    public function test_guest_can_edit_session(): void
    {
        $session = ExerciseSession::factory()->create();

        Livewire::test(EditExerciseSession::class, ['record' => $session->id])
            ->fillForm([
                'minutes' => 45,
            ])
            ->call('save')
            ->assertNotified();

        $this->assertDatabaseHas('exercise_sessions', [
            'id' => $session->id,
            'minutes' => 45,
        ]);
    }

    public function test_guest_can_delete_session(): void
    {
        $session = ExerciseSession::factory()->create();

        Livewire::test(EditExerciseSession::class, ['record' => $session->id])
            ->callAction(DeleteAction::class)
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseMissing('exercise_sessions', [
            'id' => $session->id,
        ]);
    }

    public function test_minutes_validation(): void
    {
        Livewire::test(CreateExerciseSession::class)
            ->fillForm([
                'minutes' => 0,
                'performed_at' => now(),
            ])
            ->call('create')
            ->assertHasFormErrors(['minutes'])
            ->assertNotNotified();
    }

    public function test_list_shows_records(): void
    {
        $sessions = ExerciseSession::factory()->count(3)->create();

        Livewire::test(ListExerciseSessions::class)
            ->assertCanSeeTableRecords($sessions);
    }

    public function test_list_shows_notes_when_present(): void
    {
        $session = ExerciseSession::factory()->create(['notes' => 'Caminata en el parque']);

        Livewire::test(ListExerciseSessions::class)
            ->assertCanSeeTableRecords([$session])
            ->assertSee('Caminata en el parque');
    }
}
