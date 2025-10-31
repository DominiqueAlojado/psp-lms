<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration ensures users table exists in the landlord database.
     * If users are already in tenant database, you may need to migrate them.
     */
    public function up(): void
    {
        // Check if users table exists in landlord database, if not create it
        if (! Schema::connection('landlord')->hasTable('users')) {
            Schema::connection('landlord')->create('users', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Ensure password_reset_tokens table exists
        if (! Schema::connection('landlord')->hasTable('password_reset_tokens')) {
            Schema::connection('landlord')->create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Ensure sessions table exists
        if (! Schema::connection('landlord')->hasTable('sessions')) {
            Schema::connection('landlord')->create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Be careful with this - only run if you want to drop user tables
        // Schema::connection('landlord')->dropIfExists('sessions');
        // Schema::connection('landlord')->dropIfExists('password_reset_tokens');
        // Schema::connection('landlord')->dropIfExists('users');
    }
};
