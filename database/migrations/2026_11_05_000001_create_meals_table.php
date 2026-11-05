<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('eaten_at')->index();
            $table->string('title', 200);
            $table->json('items');
            $table->decimal('kcal_est', 8, 1);
            // Upper bound of the estimate; all totals use it so estimation error leans towards a deficit.
            $table->decimal('kcal_max', 8, 1);
            $table->decimal('protein_g', 7, 1);
            $table->decimal('fat_g', 7, 1);
            $table->decimal('carbs_g', 7, 1);
            // Nullable on purpose: null = "not estimated", which is not the same as 0.
            $table->decimal('sugar_g', 7, 1)->nullable();
            $table->decimal('fiber_g', 7, 1)->nullable();
            $table->decimal('sat_fat_g', 7, 1)->nullable();
            $table->decimal('salt_g', 7, 2)->nullable();
            $table->string('confidence', 10);
            $table->string('source', 10);
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->nullable();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
