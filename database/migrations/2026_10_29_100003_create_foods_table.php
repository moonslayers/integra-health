<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('category', ['fruit', 'vegetable', 'legume', 'protein', 'grain', 'dairy', 'other'])->index();
            $table->enum('serving_unit', ['piece', 'cup', 'portion', 'gram']);
            $table->decimal('portions_per_serving', 6, 2)->nullable();
            $table->boolean('counts_toward_goal')->default(false);
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
