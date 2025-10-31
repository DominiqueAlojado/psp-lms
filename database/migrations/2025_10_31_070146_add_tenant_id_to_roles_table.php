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
        Schema::table('roles', function (Blueprint $table) {
            // tenant_id is nullable - system admin roles have null (global), others have tenant_id
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();

            // Update unique constraint to include tenant_id
            // Drop existing unique constraint first
            $table->dropUnique(['name', 'guard_name']);
        });

        // Add new unique constraint with tenant_id
        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['tenant_id', 'name', 'guard_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'name', 'guard_name']);
            $table->unique(['name', 'guard_name']);
            $table->dropColumn('tenant_id');
        });
    }
};
