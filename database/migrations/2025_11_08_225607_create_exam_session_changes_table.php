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
        Schema::create('exam_session_changes', function (Blueprint $table) {
            $table->id();
            $table->string('attempt_type'); // 'institution' or 'national'
            $table->unsignedBigInteger('attempt_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('change_type', ['ip_address', 'browser', 'both']);
            $table->string('previous_ip_address')->nullable();
            $table->string('new_ip_address')->nullable();
            $table->text('previous_user_agent')->nullable();
            $table->text('new_user_agent')->nullable();
            $table->json('browser_info')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();

            // Indexes
            $table->index(['attempt_type', 'attempt_id']);
            $table->index(['user_id', 'detected_at']);
        });

        // Add change counters to attempts tables
        Schema::table('institution_attempts', function (Blueprint $table) {
            $table->integer('ip_changes_count')->default(0)->after('connection_speed');
            $table->integer('browser_changes_count')->default(0)->after('ip_changes_count');
        });

        Schema::table('national_attempts', function (Blueprint $table) {
            $table->integer('ip_changes_count')->default(0)->after('connection_speed');
            $table->integer('browser_changes_count')->default(0)->after('ip_changes_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_attempts', function (Blueprint $table) {
            $table->dropColumn(['ip_changes_count', 'browser_changes_count']);
        });

        Schema::table('national_attempts', function (Blueprint $table) {
            $table->dropColumn(['ip_changes_count', 'browser_changes_count']);
        });

        Schema::dropIfExists('exam_session_changes');
    }
};
