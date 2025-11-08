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
        Schema::create('announcement_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('viewed_at');

            // Ensure one view per user per announcement
            $table->unique(['announcement_id', 'user_id']);
            $table->index('viewed_at');
        });

        // Add views_count column to announcements table
        Schema::table('announcements', function (Blueprint $table) {
            $table->integer('views_count')->default(0)->after('download_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });

        Schema::dropIfExists('announcement_views');
    }
};
