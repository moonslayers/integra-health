<?php

namespace Database\Seeders;

use App\Models\HealthSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(FoodSeeder::class);

        HealthSetting::current();
    }
}
