<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('water_goal_ml')->default(2000);
            $table->decimal('fruit_veg_goal_portions', 5, 2)->default(1);
            $table->unsignedInteger('active_pause_goal_count')->default(2);
            $table->unsignedInteger('active_pause_default_minutes')->default(5);
            $table->unsignedInteger('exercise_weekly_minutes_goal')->default(60);
            $table->unsignedInteger('exercise_weekly_days_goal')->default(3);
            $table->unsignedInteger('default_water_serving_ml')->default(250);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_settings');
    }
};
