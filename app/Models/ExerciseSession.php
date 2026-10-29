<?php

namespace App\Models;

use App\Enums\ExerciseType;
use Database\Factories\ExerciseSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['minutes', 'performed_at', 'type', 'notes'])]
class ExerciseSession extends Model
{
    /** @use HasFactory<ExerciseSessionFactory> */
    use HasFactory;

    protected $table = 'exercise_sessions';

    protected function casts(): array
    {
        return [
            'minutes' => 'integer',
            'performed_at' => 'datetime',
            'type' => ExerciseType::class,
        ];
    }
}
