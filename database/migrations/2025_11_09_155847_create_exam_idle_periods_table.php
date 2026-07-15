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
        Schema::create('exam_idle_periods', function (Blueprint $table) {
            $table->id();
            $table->string('attempt_type'); // 'institution' or 'national'
            $table->unsignedBigInteger('attempt_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at'); // When idle period started
            $table->timestamp('ended_at'); // When idle period ended
            $table->integer('duration_seconds'); // Duration in seconds
            $table->timestamps();

            // Indexes
            $table->index(['attempt_type', 'attempt_id']);
            $table->index(['user_id', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_idle_periods');
    }
};
