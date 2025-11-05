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
        Schema::create('national_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('national_assessments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade'); // For institution comparison
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->integer('total_points')->default(0);
            $table->integer('national_rank')->nullable(); // Updated after all submit
            $table->integer('institution_rank')->nullable(); // Rank within their institution
            $table->decimal('percentile', 5, 2)->nullable(); // National percentile
            $table->enum('status', ['in_progress', 'completed', 'graded'])->default('in_progress');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['assessment_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'score']);
            $table->index('national_rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('national_attempts');
    }
};
