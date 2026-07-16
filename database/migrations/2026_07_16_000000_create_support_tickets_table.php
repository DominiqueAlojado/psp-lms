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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->nullable()->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category');
            $table->string('priority')->default('medium');
            $table->string('status')->default('open');
            $table->string('module_name')->nullable();
            $table->string('page_url')->nullable();
            $table->text('details');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_replied_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index(['category', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
