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
        Schema::create('cme_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');

            // Source of the credit (polymorphic-like structure)
            $table->enum('source_type', ['event', 'exam_institution', 'exam_national', 'assignment_submission'])->index();
            $table->unsignedBigInteger('source_id')->index(); // event_id, attempt_id, or assignment_id

            // Credit details
            $table->decimal('credits', 5, 2); // CME/CPD credits earned (e.g., 1.5, 2.0)
            $table->string('description')->nullable(); // Description of the activity
            $table->enum('status', ['pending', 'approved', 'revoked'])->default('approved')->index();

            // Metadata
            $table->timestamp('earned_at')->useCurrent(); // When credit was earned
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable(); // Additional notes or verification info

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'earned_at']);
            $table->index(['source_type', 'source_id']);
            $table->index(['organization_id', 'earned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cme_credits');
    }
};
