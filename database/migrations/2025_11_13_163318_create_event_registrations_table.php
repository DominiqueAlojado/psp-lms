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
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->enum('registration_status', [
                'pending',
                'approved',
                'confirmed',
                'cancelled',
                'waitlisted',
            ])->default('pending');
            $table->enum('payment_status', [
                'not_required',
                'pending',
                'paid',
                'refunded',
                'failed',
            ])->default('not_required'); // For Phase 2
            $table->decimal('payment_amount', 10, 2)->default(0);
            $table->string('stripe_payment_intent_id')->nullable(); // For Phase 2
            $table->string('stripe_customer_id')->nullable(); // For Phase 2
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('checked_in_at')->nullable(); // For attendance tracking
            $table->json('custom_fields')->nullable(); // Store custom registration form data
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['event_id', 'user_id']);
            $table->index(['event_id', 'registration_status']);
            $table->index(['user_id', 'registration_status']);

            // Unique constraint - one registration per user per event
            $table->unique(['event_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
