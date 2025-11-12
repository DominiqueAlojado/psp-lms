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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('year_level')->nullable(); // Resident's year level at time of submission
            $table->text('submission_text')->nullable(); // Optional text/notes from resident
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->integer('max_score')->default(100);
            $table->enum('status', ['draft', 'submitted', 'graded', 'returned'])->default('draft');
            $table->text('grader_feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('graded_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->integer('late_days')->default(0);
            $table->integer('submission_number')->default(1); // For resubmissions
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['assignment_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index('organization_id');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
