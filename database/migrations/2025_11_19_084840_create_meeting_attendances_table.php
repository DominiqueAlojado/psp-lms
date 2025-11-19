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
        Schema::create('meeting_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('event_registration_id')->nullable()->constrained('event_registrations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->enum('status', ['joined', 'active', 'left', 'timeout'])->default('joined');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['event_id', 'user_id']);
            $table->index(['event_id', 'status']);
            $table->index(['user_id', 'event_id']);
            $table->index('joined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_attendances');
    }
};
