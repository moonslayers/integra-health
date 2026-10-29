<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('minutes');
            $table->dateTime('performed_at')->index();
            $table->enum('type', ['cardio', 'strength', 'flexibility', 'other'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_sessions');
    }
};
