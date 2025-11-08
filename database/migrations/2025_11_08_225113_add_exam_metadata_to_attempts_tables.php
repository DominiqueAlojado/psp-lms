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
        // Add metadata to institution_attempts
        Schema::table('institution_attempts', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->after('status');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->json('browser_metadata')->nullable()->after('user_agent');
            $table->string('connection_type')->nullable()->after('browser_metadata'); // 4g, wifi, ethernet, etc.
            $table->decimal('connection_speed', 8, 2)->nullable()->after('connection_type'); // in Mbps
        });

        // Add metadata to national_attempts
        Schema::table('national_attempts', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->after('status');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->json('browser_metadata')->nullable()->after('user_agent');
            $table->string('connection_type')->nullable()->after('browser_metadata');
            $table->decimal('connection_speed', 8, 2)->nullable()->after('connection_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'ip_address',
                'user_agent',
                'browser_metadata',
                'connection_type',
                'connection_speed',
            ]);
        });

        Schema::table('national_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'ip_address',
                'user_agent',
                'browser_metadata',
                'connection_type',
                'connection_speed',
            ]);
        });
    }
};
