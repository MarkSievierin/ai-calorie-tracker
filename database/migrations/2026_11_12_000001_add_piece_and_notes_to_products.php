<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // piece_g: weight of one piece (a cracker stick, an egg, a candy) so "10 pieces" is counted exactly.
        // notes: short facts about the product (how it was weighed) that the agent gets with find_products.
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('piece_g', 7, 2)->nullable()->after('default_portion_g');
            $table->string('notes', 500)->nullable()->after('piece_g');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['piece_g', 'notes']);
        });
    }
};
