<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20); // observation | suggestion | question
            $table->text('text');
            $table->json('related')->nullable(); // metric types and/or dates the note is about
            $table->string('author', 10)->default('claude'); // claude | user
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
