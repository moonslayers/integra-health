<?php

namespace App\Models;

use App\Enums\PauseType;
use Database\Factories\ActivePauseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['minutes', 'type', 'performed_at'])]
class ActivePause extends Model
{
    /** @use HasFactory<ActivePauseFactory> */
    use HasFactory;

    protected $table = 'active_pauses';

    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'type' => PauseType::class,
            'performed_at' => 'datetime',
        ];
    }
}
