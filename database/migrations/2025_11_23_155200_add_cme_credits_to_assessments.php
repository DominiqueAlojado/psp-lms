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
        // Add CME credits to institution assessments
        Schema::table('institution_assessments', function (Blueprint $table) {
            $table->decimal('cme_credits', 5, 2)->nullable()->after('passing_score');
        });

        // Add CME credits to national assessments
        Schema::table('national_assessments', function (Blueprint $table) {
            $table->decimal('cme_credits', 5, 2)->nullable()->after('passing_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_assessments', function (Blueprint $table) {
            $table->dropColumn('cme_credits');
        });

        Schema::table('national_assessments', function (Blueprint $table) {
            $table->dropColumn('cme_credits');
        });
    }
};
