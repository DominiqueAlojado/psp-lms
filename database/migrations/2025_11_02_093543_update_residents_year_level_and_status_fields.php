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
        Schema::table('residents', function (Blueprint $table) {
            // Change year_level from integer to string
            $table->string('year_level')->default('First Year')->change();

            // Update status default
            $table->string('status')->default('active')->change();
        });

        // Update existing year_level data (convert 1-4 to First Year, etc.)
        DB::table('residents')->update([
            'year_level' => DB::raw("CASE 
                WHEN year_level = '1' THEN 'First Year'
                WHEN year_level = '2' THEN 'Second Year'
                WHEN year_level = '3' THEN 'Third Year'
                WHEN year_level = '4' THEN 'Fourth Year'
                ELSE year_level
            END"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->integer('year_level')->default(1)->change();
        });
    }
};
