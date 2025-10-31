<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('landlord')->create('year_levels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->unique(); // e.g., "Pre-Resident", "1st Year", "2nd Year"
            $table->string('slug')->unique(); // e.g., "pre-resident", "1st-year", "2nd-year"
            $table->integer('order')->unique(); // Order for sorting (1, 2, 3, etc.)
            $table->text('description')->nullable(); // Optional description
            $table->timestamps();

            $table->index('order');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('year_levels');
    }
};
