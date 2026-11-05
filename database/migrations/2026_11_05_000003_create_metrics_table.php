<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Key-value per day: a new metric type (steps, sleep_h, bp_sys, ...) needs no migration.
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('type', 40);
            $table->decimal('value', 10, 2);
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
