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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('event_category', [
                'convention',
                'workshop',
                'seminar',
                'cme',
                'conference',
                'symposium',
                'training',
                'other',
            ])->default('other');
            $table->enum('event_type', ['in-person', 'virtual', 'hybrid'])->default('in-person');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->timestamp('registration_deadline')->nullable();
            $table->string('location')->nullable(); // Physical location
            $table->string('virtual_link')->nullable(); // Zoom/Teams/etc link
            $table->integer('capacity')->nullable(); // Max attendees
            $table->string('image_path')->nullable();
            $table->decimal('cme_credits', 5, 2)->nullable(); // CME/CPD credits
            $table->json('target_year_levels')->nullable(); // ['PGY-1', 'PGY-2', etc.]
            $table->text('requirements')->nullable(); // Prerequisites or requirements
            $table->boolean('requires_approval')->default(false); // Admin must approve registration
            $table->boolean('is_published')->default(false);
            $table->json('speakers')->nullable(); // [{ name, title, bio, organization, email, photo_path }]
            $table->json('agenda_items')->nullable(); // [{ day, start_time, end_time, session_title, description, speaker, location }]
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['organization_id', 'is_published']);
            $table->index(['start_date', 'end_date']);
            $table->index('event_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
