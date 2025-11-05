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
        Schema::create('national_assessments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('exam_year');
            $table->string('exam_period', 50)->default('Annual'); // 'Annual', 'Q1', 'Q2', etc.
            $table->integer('duration_minutes')->nullable();
            $table->integer('total_points')->default(0);
            $table->integer('passing_score')->default(0);
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_choices')->default(false);
            $table->boolean('show_results_immediately')->default(false); // Usually false for national
            $table->boolean('allow_review')->default(false);
            $table->boolean('is_published')->default(false);
            $table->boolean('national_ranking_enabled')->default(true);
            $table->boolean('institution_comparison_enabled')->default(true);
            $table->timestamp('scheduled_date')->nullable(); // When all residents take it
            $table->timestamp('results_release_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['exam_year', 'exam_period']);
            $table->index(['is_published', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('national_assessments');
    }
};
