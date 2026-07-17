<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permission_category_options') && ! Schema::hasTable('permission_module_options')) {
            Schema::rename('permission_category_options', 'permission_module_options');
        }

        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'category') && ! Schema::hasColumn('permissions', 'module')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->renameColumn('category', 'module');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'module') && ! Schema::hasColumn('permissions', 'category')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->renameColumn('module', 'category');
            });
        }

        if (Schema::hasTable('permission_module_options') && ! Schema::hasTable('permission_category_options')) {
            Schema::rename('permission_module_options', 'permission_category_options');
        }
    }
};
