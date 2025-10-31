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
        Schema::connection('landlord')->create('residents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('level')->nullable(); // Residency level (e.g., "PGY-1", "PGY-2", "R1", "R2", etc.)
            $table->string('specialty')->nullable(); // Medical specialty (e.g., "Internal Medicine", "Surgery")
            $table->string('registration_number')->nullable(); // Professional registration/license number
            $table->string('department')->nullable(); // Department assignment
            $table->string('phone')->nullable(); // Contact phone number
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null'); // Supervising consultant
            $table->date('start_date')->nullable(); // Residency start date
            $table->date('end_date')->nullable(); // Residency end date
            $table->enum('status', ['active', 'inactive', 'completed', 'suspended'])->default('active');
            $table->text('notes')->nullable(); // Additional notes
            $table->timestamps();

            // A user can have one resident profile per tenant (hospital)
            $table->unique(['user_id', 'tenant_id']);
            $table->index('tenant_id');
            $table->index('status');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('residents');
    }
};
