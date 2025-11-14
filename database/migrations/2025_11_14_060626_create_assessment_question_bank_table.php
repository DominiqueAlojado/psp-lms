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
        Schema::create('assessment_question_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('institution_assessments')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('question_bank')->onDelete('cascade');
            $table->integer('order')->default(0); // Order in the specific exam
            $table->integer('points_override')->nullable(); // Override points for this exam
            $table->timestamps();

            // Unique constraint - same question can't appear twice in same exam
            $table->unique(['assessment_id', 'question_id']);

            // Indexes
            $table->index(['assessment_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_question_bank');
    }
};
