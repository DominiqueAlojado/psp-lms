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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            // Reference to tenant (hospital)
            $table->unsignedBigInteger('tenant_id')->index();
            // Reference to user (resident) - stored in landlord DB but referenced here
            $table->unsignedBigInteger('user_id')->index();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->enum('status', ['pending', 'enrolled', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('enrolled_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->decimal('grade', 5, 2)->nullable(); // Grade/score (e.g., 95.50)
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();

            // Prevent duplicate enrollments per tenant
            $table->unique(['tenant_id', 'user_id', 'course_id']);
            $table->index('status');
            $table->index('enrolled_at');
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
