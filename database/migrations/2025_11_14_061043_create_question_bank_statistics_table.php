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
        Schema::create('question_bank_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('question_bank')->onDelete('cascade');

            // Usage tracking
            $table->integer('times_used_in_exams')->default(0); // How many exams include this question
            $table->integer('times_answered')->default(0); // Total attempts by students
            $table->integer('times_correct')->default(0); // How many got it right
            $table->integer('times_incorrect')->default(0); // How many got it wrong
            $table->decimal('success_rate', 5, 2)->default(0); // Percentage (0-100)

            // Performance metrics
            $table->decimal('average_time_seconds', 8, 2)->nullable(); // Avg time to answer
            $table->string('computed_difficulty')->nullable(); // easy, medium, hard (based on success_rate)

            // Quality indicators
            $table->decimal('discrimination_index', 5, 2)->nullable(); // How well it separates high/low performers
            $table->integer('skip_count')->default(0); // How many students skipped it

            // Last updated
            $table->timestamp('last_used_at')->nullable(); // Last time used in an exam
            $table->timestamp('statistics_updated_at')->nullable(); // Last stats calculation

            $table->timestamps();

            // Unique - one stats record per question
            $table->unique('question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_bank_statistics');
    }
};
