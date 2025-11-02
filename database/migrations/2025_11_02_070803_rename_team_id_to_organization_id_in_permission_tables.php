<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename team_id to organization_id in roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'name', 'guard_name']);
            $table->dropIndex('roles_team_foreign_key_index');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->renameColumn('team_id', 'organization_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->index('organization_id', 'roles_team_foreign_key_index');
            $table->unique(['organization_id', 'name', 'guard_name']);
        });

        // Rename team_id to organization_id in model_has_permissions table
        Schema::table('model_has_permissions', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['permission_id']);
            }
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->dropIndex('model_has_permissions_team_foreign_key_index');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->renameColumn('team_id', 'organization_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->index('organization_id', 'model_has_permissions_team_foreign_key_index');
            $table->primary(['organization_id', 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('permission_id')
                    ->references('id')->on('permissions')->onDelete('cascade');
            }
        });

        // Rename team_id to organization_id in model_has_roles table
        Schema::table('model_has_roles', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['role_id']);
            }
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->dropIndex('model_has_roles_team_foreign_key_index');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->renameColumn('team_id', 'organization_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->index('organization_id', 'model_has_roles_team_foreign_key_index');
            $table->primary(['organization_id', 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('role_id')
                    ->references('id')->on('roles')->onDelete('cascade');
            }
        });

        // Clear permission cache
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: Rename organization_id back to team_id in roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'name', 'guard_name']);
            $table->dropIndex('roles_team_foreign_key_index');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->renameColumn('organization_id', 'team_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->index('team_id', 'roles_team_foreign_key_index');
            $table->unique(['team_id', 'name', 'guard_name']);
        });

        // Reverse: Rename organization_id back to team_id in model_has_permissions table
        Schema::table('model_has_permissions', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['permission_id']);
            }
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->dropIndex('model_has_permissions_team_foreign_key_index');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->renameColumn('organization_id', 'team_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->index('team_id', 'model_has_permissions_team_foreign_key_index');
            $table->primary(['team_id', 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('permission_id')
                    ->references('id')->on('permissions')->onDelete('cascade');
            }
        });

        // Reverse: Rename organization_id back to team_id in model_has_roles table
        Schema::table('model_has_roles', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['role_id']);
            }
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->dropIndex('model_has_roles_team_foreign_key_index');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->renameColumn('organization_id', 'team_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->index('team_id', 'model_has_roles_team_foreign_key_index');
            $table->primary(['team_id', 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('role_id')
                    ->references('id')->on('roles')->onDelete('cascade');
            }
        });

        // Clear permission cache
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
