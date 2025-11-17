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
        Schema::table('question_bank', function (Blueprint $table) {
            // Check if column doesn't exist before adding (for PostgreSQL safety)
            if (!Schema::hasColumn('question_bank', 'owner_type')) {
                $table->enum('owner_type', ['national', 'institution'])
                    ->default('institution')
                    ->after('organization_id');

                // Add index for better query performance
                $table->index('owner_type');
                $table->index(['owner_type', 'organization_id']);
            }
        });

        // Update existing records - set owner_type based on organization_id
        // If organization_id exists, it's institution; otherwise national
        // For now, all existing questions are institution
        \DB::statement("UPDATE question_bank SET owner_type = 'institution' WHERE owner_type IS NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank', 'owner_type')) {
                $table->dropColumn('owner_type');
            }
        });
    }
};
