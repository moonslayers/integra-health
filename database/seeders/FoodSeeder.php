<?php

namespace Database\Seeders;

use App\Enums\FoodCategory;
use App\Enums\ServingUnit;
use App\Models\Food;
use Illuminate\Database\Seeder;

class FoodSeeder extends Seeder
{
    public function run(): void
    {
        $foods = require __DIR__.'/data/foods.php';

        foreach ($foods as $food) {
            Food::updateOrCreate(
                ['slug' => $food['slug']],
                [
                    'name' => $food['name'],
                    'category' => FoodCategory::from($food['category']),
                    'serving_unit' => ServingUnit::from($food['serving_unit']),
                    'portions_per_serving' => $food['portions_per_serving'],
                    'counts_toward_goal' => $food['counts_toward_goal'],
                    'source' => $food['source'],
                    'notes' => $food['notes'],
                ]
            );
        }
    }
}
