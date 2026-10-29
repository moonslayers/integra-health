<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_entries', function (Blueprint $table): void {
            $table->text('notes')->nullable()->after('consumed_at');
        });
    }

    public function down(): void
    {
        Schema::table('food_entries', function (Blueprint $table): void {
            $table->dropColumn('notes');
        });
    }
};
