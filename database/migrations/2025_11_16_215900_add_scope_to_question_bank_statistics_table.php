<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('question_bank_statistics', function (Blueprint $table) {
            // If columns already exist (e.g. added in base create migration), skip adding
            if (! Schema::hasColumn('question_bank_statistics', 'scope')) {
                $table->enum('scope', ['national', 'institution'])->default('national')->after('question_id');
            }
            if (! Schema::hasColumn('question_bank_statistics', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->after('scope')->constrained('organizations')->nullOnDelete();
            }

            // Safely replace unique/index using DB statements to support IF EXISTS/IF NOT EXISTS (postgres)
            DB::statement('ALTER TABLE question_bank_statistics DROP CONSTRAINT IF EXISTS question_bank_statistics_question_id_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS qbs_question_scope_institution_unique ON question_bank_statistics (question_id, scope, institution_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS qbs_scope_institution_index ON question_bank_statistics (scope, institution_id)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank_statistics', function (Blueprint $table) {
            // Revert indexes
            // Drop indexes if they exist
            DB::statement('DROP INDEX IF EXISTS qbs_question_scope_institution_unique');
            DB::statement('DROP INDEX IF EXISTS qbs_scope_institution_index');

            // Drop added columns
            if (Schema::hasColumn('question_bank_statistics', 'institution_id')) {
                $table->dropConstrainedForeignId('institution_id');
            }
            if (Schema::hasColumn('question_bank_statistics', 'scope')) {
                $table->dropColumn('scope');
            }

            // Restore original unique
            try {
                DB::statement('ALTER TABLE question_bank_statistics ADD CONSTRAINT question_bank_statistics_question_id_unique UNIQUE (question_id)');
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        });
    }
};


