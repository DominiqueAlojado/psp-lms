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
        // Add credit_type to institution_assessments
        Schema::table('institution_assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('institution_assessments', 'credit_type')) {
                $table->enum('credit_type', ['cme', 'cpd'])->nullable()->after('cme_credits');
            }
        });

        // Add credit_type to national_assessments
        Schema::table('national_assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('national_assessments', 'credit_type')) {
                $table->enum('credit_type', ['cme', 'cpd'])->nullable()->after('cme_credits');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('institution_assessments', 'credit_type')) {
                $table->dropColumn('credit_type');
            }
        });

        Schema::table('national_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('national_assessments', 'credit_type')) {
                $table->dropColumn('credit_type');
            }
        });
    }
};
