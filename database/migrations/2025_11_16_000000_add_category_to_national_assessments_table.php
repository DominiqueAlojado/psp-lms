<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('national_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('national_assessments', 'category')) {
                $table->enum('category', [
                    'anatomic-pathology-theoretical',
                    'anatomic-pathology-projection',
                    'clinical-pathology-theoretical',
                    'clinical-pathology-projection',
                ])->nullable()->after('exam_period');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('national_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('national_assessments', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
