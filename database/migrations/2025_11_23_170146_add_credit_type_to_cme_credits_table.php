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
        Schema::table('cme_credits', function (Blueprint $table) {
            if (! Schema::hasColumn('cme_credits', 'credit_type')) {
                $table->enum('credit_type', ['cme', 'cpd'])->default('cme')->after('source_id')->index();
            }
        });

        // Update existing records to default to 'cme' for backward compatibility
        if (Schema::hasColumn('cme_credits', 'credit_type')) {
            \DB::table('cme_credits')->whereNull('credit_type')->update(['credit_type' => 'cme']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cme_credits', function (Blueprint $table) {
            if (Schema::hasColumn('cme_credits', 'credit_type')) {
                $table->dropColumn('credit_type');
            }
        });
    }
};
