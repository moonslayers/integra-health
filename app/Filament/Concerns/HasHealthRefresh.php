<?php

namespace App\Filament\Concerns;

use Livewire\Attributes\On;

trait HasHealthRefresh
{
    #[On('health-data-updated')]
    public function refreshData(): void {}
}
