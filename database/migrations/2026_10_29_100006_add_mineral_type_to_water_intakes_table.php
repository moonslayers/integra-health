<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            Schema::dropIfExists('water_intakes_rebuild');

            Schema::create('water_intakes_rebuild', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['natural', 'cold_infusion', 'hot_infusion', 'coffee', 'mineral']);
                $table->unsignedInteger('volume_ml')->default(250);
                $table->dateTime('consumed_at');
                $table->timestamps();
            });

            DB::table('water_intakes_rebuild')->insertUsing(
                ['id', 'type', 'volume_ml', 'consumed_at', 'created_at', 'updated_at'],
                DB::table('water_intakes')
                    ->select('id', 'type', 'volume_ml', 'consumed_at', 'created_at', 'updated_at')
            );

            Schema::drop('water_intakes');
            DB::statement('ALTER TABLE "water_intakes_rebuild" RENAME TO "water_intakes"');
            DB::statement('CREATE INDEX "water_intakes_consumed_at_index" ON "water_intakes" ("consumed_at")');
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            Schema::dropIfExists('water_intakes_rebuild');

            Schema::create('water_intakes_rebuild', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['natural', 'cold_infusion', 'hot_infusion', 'coffee']);
                $table->unsignedInteger('volume_ml')->default(250);
                $table->dateTime('consumed_at');
                $table->timestamps();
            });

            DB::table('water_intakes_rebuild')->insertUsing(
                ['id', 'type', 'volume_ml', 'consumed_at', 'created_at', 'updated_at'],
                DB::table('water_intakes')
                    ->select('id', 'type', 'volume_ml', 'consumed_at', 'created_at', 'updated_at')
                    ->whereIn('type', ['natural', 'cold_infusion', 'hot_infusion', 'coffee'])
            );

            Schema::drop('water_intakes');
            DB::statement('ALTER TABLE "water_intakes_rebuild" RENAME TO "water_intakes"');
            DB::statement('CREATE INDEX "water_intakes_consumed_at_index" ON "water_intakes" ("consumed_at")');
        });
    }
};
