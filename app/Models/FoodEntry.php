<?php

namespace App\Models;

use Database\Factories\FoodEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['food_id', 'quantity', 'consumed_at'])]
class FoodEntry extends Model
{
    /** @use HasFactory<FoodEntryFactory> */
    use HasFactory;

    protected $table = 'food_entries';

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'consumed_at' => 'datetime',
        ];
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    public function getPortionsAttribute(): float
    {
        $food = $this->food;

        if ($food === null) {
            return 0.0;
        }

        return (float) $this->quantity * (float) ($food->portions_per_serving ?? 0);
    }

    public function portions(): float
    {
        return $this->portions;
    }
}
