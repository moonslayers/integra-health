<?php

namespace App\Models;

use App\Enums\WaterType;
use Database\Factories\WaterIntakeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'volume_ml', 'consumed_at', 'notes'])]
class WaterIntake extends Model
{
    /** @use HasFactory<WaterIntakeFactory> */
    use HasFactory;

    protected $table = 'water_intakes';

    protected function casts(): array
    {
        return [
            'type' => WaterType::class,
            'volume_ml' => 'integer',
            'consumed_at' => 'datetime',
        ];
    }
}
