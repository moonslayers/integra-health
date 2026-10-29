<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('active_pauses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('minutes')->default(5);
            $table->enum('type', ['stretching', 'passive']);
            $table->dateTime('performed_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_pauses');
    }
};
