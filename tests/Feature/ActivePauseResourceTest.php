<?php

namespace Tests\Feature;

use App\Enums\PauseType;
use App\Filament\Resources\ActivePauses\Pages\CreateActivePause;
use App\Filament\Resources\ActivePauses\Pages\EditActivePause;
use App\Filament\Resources\ActivePauses\Pages\ListActivePauses;
use App\Models\ActivePause;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivePauseResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_list_page(): void
    {
        Livewire::test(ListActivePauses::class)
            ->assertOk();
    }

    public function test_guest_can_access_create_page(): void
    {
        Livewire::test(CreateActivePause::class)
            ->assertOk();
    }

    public function test_guest_can_create_active_pause(): void
    {
        Livewire::test(CreateActivePause::class)
            ->fillForm([
                'minutes' => 10,
                'type' => PauseType::Passive->value,
                'performed_at' => now(),
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas('active_pauses', [
            'minutes' => 10,
            'type' => PauseType::Passive->value,
        ]);
    }

    public function test_default_minutes_are_applied_when_omitted(): void
    {
        Livewire::test(CreateActivePause::class)
            ->fillForm([
                'type' => PauseType::Stretching->value,
                'performed_at' => now(),
            ])
            ->call('create')
            ->assertNotified()
            ->assertRedirect();

        $this->assertDatabaseHas('active_pauses', [
            'type' => PauseType::Stretching->value,
            'minutes' => 5,
        ]);
    }

    public function test_guest_can_edit_active_pause(): void
    {
        $pause = ActivePause::factory()->create();

        Livewire::test(EditActivePause::class, ['record' => $pause->id])
            ->fillForm([
                'minutes' => 15,
            ])
            ->call('save')
            ->assertNotified();

        $this->assertDatabaseHas('active_pauses', [
            'id' => $pause->id,
            'minutes' => 15,
        ]);
    }

    public function test_minutes_validation(): void
    {
        Livewire::test(CreateActivePause::class)
            ->fillForm([
                'minutes' => 0,
                'type' => PauseType::Stretching->value,
                'performed_at' => now(),
            ])
            ->call('create')
            ->assertHasFormErrors(['minutes'])
            ->assertNotNotified();
    }

    public function test_list_shows_records(): void
    {
        $pauses = ActivePause::factory()->count(3)->create();

        Livewire::test(ListActivePauses::class)
            ->assertCanSeeTableRecords($pauses);
    }
}
