<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Filled from nutrition labels so standard products are counted exactly instead of estimated.
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->decimal('kcal_100g', 7, 1);
            $table->decimal('protein_100g', 6, 1);
            $table->decimal('fat_100g', 6, 1);
            $table->decimal('carbs_100g', 6, 1);
            $table->decimal('default_portion_g', 7, 1)->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
