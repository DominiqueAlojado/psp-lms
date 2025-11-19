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
        Schema::table('meeting_attendances', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['event_registration_id']);
            
            // Make the column nullable
            $table->foreignId('event_registration_id')->nullable()->change();
            
            // Re-add the foreign key constraint
            $table->foreign('event_registration_id')
                ->references('id')
                ->on('event_registrations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meeting_attendances', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['event_registration_id']);
            
            // Make the column NOT NULL (this will fail if there are null values)
            $table->foreignId('event_registration_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('event_registration_id')
                ->references('id')
                ->on('event_registrations')
                ->onDelete('cascade');
        });
    }
};
