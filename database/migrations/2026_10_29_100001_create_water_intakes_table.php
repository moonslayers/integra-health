<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_intakes', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['natural', 'cold_infusion', 'hot_infusion', 'coffee']);
            $table->unsignedInteger('volume_ml')->default(250);
            $table->dateTime('consumed_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_intakes');
    }
};
