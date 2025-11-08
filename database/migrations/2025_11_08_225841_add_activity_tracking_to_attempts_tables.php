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
        Schema::table('institution_attempts', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('browser_changes_count');
            $table->integer('total_idle_time')->default(0)->after('last_activity_at'); // Total idle seconds
            $table->integer('idle_periods_count')->default(0)->after('total_idle_time');
            $table->integer('max_idle_duration')->default(0)->after('idle_periods_count'); // Longest idle in seconds
        });

        Schema::table('national_attempts', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('browser_changes_count');
            $table->integer('total_idle_time')->default(0)->after('last_activity_at');
            $table->integer('idle_periods_count')->default(0)->after('total_idle_time');
            $table->integer('max_idle_duration')->default(0)->after('idle_periods_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'last_activity_at',
                'total_idle_time',
                'idle_periods_count',
                'max_idle_duration',
            ]);
        });

        Schema::table('national_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'last_activity_at',
                'total_idle_time',
                'idle_periods_count',
                'max_idle_duration',
            ]);
        });
    }
};
