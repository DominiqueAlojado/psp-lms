<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration documents the cascade delete behavior for exam_session_changes.
     * Since exam_session_changes uses a polymorphic-like relationship (attempt_type + attempt_id),
     * we cannot use traditional foreign key constraints.
     *
     * Instead, cascade delete is handled at the model level in:
     * - App\Models\Institution\InstitutionAttempt::boot()
     * - App\Models\National\NationalAttempt::boot()
     *
     * When an attempt is deleted, all related session changes are automatically deleted.
     */
    public function up(): void
    {
        // No schema changes needed - cascade delete is handled at the model level
        // This migration serves as documentation for the cascade delete behavior
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No schema changes to reverse - cascade delete is handled at the model level
    }
};
