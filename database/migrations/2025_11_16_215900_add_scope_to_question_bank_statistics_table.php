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
        $driver = DB::getDriverName();

        Schema::table('question_bank_statistics', function (Blueprint $table) {
            // If columns already exist (e.g. added in base create migration), skip adding
            if (! Schema::hasColumn('question_bank_statistics', 'scope')) {
                $table->enum('scope', ['national', 'institution'])->default('national')->after('question_id');
            }
            if (! Schema::hasColumn('question_bank_statistics', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->after('scope')->constrained('organizations')->nullOnDelete();
            }
        });

        // Ensure columns exist before creating indexes
        $hasScope = Schema::hasColumn('question_bank_statistics', 'scope');
        $hasInstitutionId = Schema::hasColumn('question_bank_statistics', 'institution_id');

        if ($hasScope && $hasInstitutionId) {
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE question_bank_statistics DROP CONSTRAINT IF EXISTS question_bank_statistics_question_id_unique');
                DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS qbs_question_scope_institution_unique ON question_bank_statistics (question_id, scope, institution_id)');
                DB::statement('CREATE INDEX IF NOT EXISTS qbs_scope_institution_index ON question_bank_statistics (scope, institution_id)');
            } else {
                try {
                    Schema::table('question_bank_statistics', function (Blueprint $table) {
                        $table->dropUnique('question_bank_statistics_question_id_unique');
                    });
                } catch (\Throwable $e) {
                    // Ignore if the unique index does not exist for this driver.
                }

                try {
                    Schema::table('question_bank_statistics', function (Blueprint $table) {
                        $table->unique(
                            ['question_id', 'scope', 'institution_id'],
                            'qbs_question_scope_institution_unique'
                        );
                    });
                } catch (\Throwable $e) {
                    // Ignore if the composite unique already exists.
                }

                try {
                    Schema::table('question_bank_statistics', function (Blueprint $table) {
                        $table->index(['scope', 'institution_id'], 'qbs_scope_institution_index');
                    });
                } catch (\Throwable $e) {
                    // Ignore if the index already exists.
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS qbs_question_scope_institution_unique');
            DB::statement('DROP INDEX IF EXISTS qbs_scope_institution_index');
        } else {
            try {
                Schema::table('question_bank_statistics', function (Blueprint $table) {
                    $table->dropUnique('qbs_question_scope_institution_unique');
                });
            } catch (\Throwable $e) {
                // Ignore if the index does not exist for this driver.
            }

            try {
                Schema::table('question_bank_statistics', function (Blueprint $table) {
                    $table->dropIndex('qbs_scope_institution_index');
                });
            } catch (\Throwable $e) {
                // Ignore if the index does not exist for this driver.
            }
        }

        Schema::table('question_bank_statistics', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank_statistics', 'institution_id')) {
                $table->dropConstrainedForeignId('institution_id');
            }
            if (Schema::hasColumn('question_bank_statistics', 'scope')) {
                $table->dropColumn('scope');
            }
        });

        try {
            Schema::table('question_bank_statistics', function (Blueprint $table) {
                $table->unique(['question_id'], 'question_bank_statistics_question_id_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if the original unique already exists.
        }
    }
};

