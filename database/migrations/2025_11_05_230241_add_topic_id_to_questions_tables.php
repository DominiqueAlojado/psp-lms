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
        Schema::table('institution_questions', function (Blueprint $table) {
            $table->foreignId('topic_id')->nullable()->after('question_type')->constrained('topics')->onDelete('set null');
        });

        Schema::table('national_questions', function (Blueprint $table) {
            $table->foreignId('topic_id')->nullable()->after('difficulty_level')->constrained('topics')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_questions', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->dropColumn('topic_id');
        });

        Schema::table('national_questions', function (Blueprint $table) {
            $table->dropForeign(['topic_id']);
            $table->dropColumn('topic_id');
        });
    }
};
