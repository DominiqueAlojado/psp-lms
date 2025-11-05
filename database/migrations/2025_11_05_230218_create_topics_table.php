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
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->boolean('is_global')->default(false); // Global topics available to all organizations
            $table->timestamps();

            // Indexes
            $table->index(['organization_id', 'name']);
            $table->index('is_global');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
