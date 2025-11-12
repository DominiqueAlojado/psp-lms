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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('assignment_type', [
                'case_report',
                'procedure_log',
                'journal_review',
                'presentation',
                'research_paper',
                'reflection',
                'other',
            ])->default('other');
            $table->json('target_year_levels')->nullable(); // e.g., ["PGY-1", "PGY-2"]
            $table->unsignedBigInteger('course_id')->nullable(); // Will add foreign key when courses table exists
            $table->integer('max_score')->default(100);
            $table->timestamp('due_date')->nullable();
            $table->boolean('allow_late_submission')->default(false);
            $table->timestamp('late_submission_until')->nullable();
            $table->integer('late_penalty_percent')->default(0); // Percentage deduction for late submissions
            $table->boolean('allow_resubmission')->default(false);
            $table->integer('max_submissions')->default(1);
            $table->json('allowed_file_types')->nullable(); // e.g., ["pdf", "docx", "jpg", "png"]
            $table->integer('max_file_size_mb')->default(10);
            $table->integer('max_files')->default(5);
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['organization_id', 'is_published']);
            $table->index('due_date');
            $table->index('assignment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
