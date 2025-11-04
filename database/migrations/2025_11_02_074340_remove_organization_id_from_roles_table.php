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
        // Check if organization_id column exists
        if (Schema::hasColumn('roles', 'organization_id')) {
            // Drop existing unique constraint and index using try-catch
            try {
                \DB::statement('ALTER TABLE roles DROP CONSTRAINT roles_organization_id_name_guard_name_unique');
            } catch (\Exception $e) {
                //
            }

            try {
                \DB::statement('DROP INDEX roles_team_foreign_key_index');
            } catch (\Exception $e) {
                //
            }

            // Drop organization_id column
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('organization_id');
            });

            // Add back unique constraint on just name and guard_name
            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['name', 'guard_name']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add organization_id back
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable()->after('id');
            $table->index('organization_id', 'roles_team_foreign_key_index');
        });

        // Drop the simple unique constraint
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name', 'guard_name']);
        });

        // Add back the compound unique constraint
        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['organization_id', 'name', 'guard_name']);
        });
    }
};
