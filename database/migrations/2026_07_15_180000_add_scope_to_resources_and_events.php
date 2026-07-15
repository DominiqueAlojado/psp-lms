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
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->enum('scope', ['organization', 'system'])
                ->default('organization')
                ->after('category');
        });

        DB::table('learning_resources')
            ->whereNull('organization_id')
            ->update(['scope' => 'system']);

        Schema::table('learning_resources', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->change();
            $table->index(['scope', 'is_published'], 'learning_resources_scope_published_index');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->enum('scope', ['organization', 'system'])
                ->default('organization')
                ->after('organization_id');
        });

        DB::table('events')
            ->whereNull('organization_id')
            ->update(['scope' => 'system']);

        Schema::table('events', function (Blueprint $table) {
            $table->index(['scope', 'is_published'], 'events_scope_published_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_scope_published_index');
            $table->dropColumn('scope');
        });

        if (DB::table('learning_resources')->whereNull('organization_id')->exists()) {
            throw new \RuntimeException('Cannot reverse learning resource scope migration while system-wide resources exist.');
        }

        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropIndex('learning_resources_scope_published_index');
            $table->dropColumn('scope');
            $table->foreignId('organization_id')->nullable(false)->change();
        });
    }
};
