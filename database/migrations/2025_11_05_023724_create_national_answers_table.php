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
        Schema::create('national_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('national_attempts')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('national_questions')->onDelete('cascade');
            $table->json('answer_data')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 8, 2)->default(0);
            $table->text('grader_feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['attempt_id', 'question_id']);
            $table->index('is_correct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('national_answers');
    }
};
