<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_attempts', function (Blueprint $table) {
            $table->string('active_session_id')->nullable()->after('status');
        });

        Schema::table('national_attempts', function (Blueprint $table) {
            $table->string('active_session_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('institution_attempts', function (Blueprint $table) {
            $table->dropColumn('active_session_id');
        });

        Schema::table('national_attempts', function (Blueprint $table) {
            $table->dropColumn('active_session_id');
        });
    }
};
