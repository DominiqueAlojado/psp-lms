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
        Schema::table('institution_answers', function (Blueprint $table) {
            $table->integer('answer_change_count')->default(0)->after('answer_data');
        });

        Schema::table('national_answers', function (Blueprint $table) {
            $table->integer('answer_change_count')->default(0)->after('answer_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_answers', function (Blueprint $table) {
            $table->dropColumn('answer_change_count');
        });

        Schema::table('national_answers', function (Blueprint $table) {
            $table->dropColumn('answer_change_count');
        });
    }
};
