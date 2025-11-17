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
            // Drop foreign key constraint first
            $table->dropForeign(['organization_id']);
        });

        // Modify column to be nullable
        Schema::table('question_bank', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->change();
        });

        // Re-add foreign key constraint
        Schema::table('question_bank', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['organization_id']);
        });

        // Make column not nullable again
        Schema::table('question_bank', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable(false)->change();
        });

        // Re-add foreign key constraint
        Schema::table('question_bank', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }
};
