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
        Schema::create('national_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('national_assessments')->onDelete('cascade');
            $table->enum('question_type', ['multiple_choice', 'multiple_select', 'true_false', 'essay', 'fill_blank'])->default('multiple_choice');
            $table->text('question_text');
            $table->integer('points')->default(1);
            $table->text('explanation')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->nullable(); // For analytics
            $table->string('topic', 100)->nullable(); // For topic analysis
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['assessment_id', 'order']);
            $table->index('difficulty_level');
            $table->index('topic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('national_questions');
    }
};
