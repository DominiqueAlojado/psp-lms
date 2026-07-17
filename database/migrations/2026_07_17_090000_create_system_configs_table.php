<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('module')->index();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('type')->default('string');
            $table->text('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_editable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('system_configs')->insert([
            'key' => 'ui.resident_demo_notice_enabled',
            'module' => 'User Experience',
            'label' => 'Resident Demo Environment Notice',
            'description' => 'Show the resident portal demo-data notice modal after sign in.',
            'type' => 'boolean',
            'value' => '1',
            'is_public' => true,
            'is_editable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_configs');
    }
};
