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
        Schema::table('question_bank_statistics', function (Blueprint $table) {
            // Columns for scoping stats
            $table->enum('scope', ['national', 'institution'])->default('national')->after('question_id');
            $table->foreignId('institution_id')->nullable()->after('scope')->constrained('organizations')->nullOnDelete();

            // Drop old unique and add new composite unique
            $table->dropUnique('question_bank_statistics_question_id_unique');
            $table->unique(['question_id', 'scope', 'institution_id'], 'qbs_question_scope_institution_unique');
            $table->index(['scope', 'institution_id'], 'qbs_scope_institution_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank_statistics', function (Blueprint $table) {
            // Revert indexes
            $table->dropUnique('qbs_question_scope_institution_unique');
            $table->dropIndex('qbs_scope_institution_index');

            // Drop added columns
            $table->dropConstrainedForeignId('institution_id');
            $table->dropColumn('scope');

            // Restore original unique
            $table->unique('question_id');
        });
    }
};


