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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            // Reference to tenant (hospital) - using unsignedBigInteger since tenants are in landlord DB
            // If you're using a single database, you might want to use foreignId instead
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('code')->nullable(); // Course code (e.g., "RES101")
            $table->text('content')->nullable(); // Course content/curriculum
            $table->integer('duration_hours')->nullable(); // Course duration in hours
            $table->integer('credit_hours')->nullable(); // Credit hours
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            // Unique code per tenant
            $table->unique(['tenant_id', 'code']);
            $table->index('status');
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
