<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_rating');
            $table->unsignedTinyInteger('content_rating');
            $table->unsignedTinyInteger('support_rating');
            $table->unsignedTinyInteger('usability_rating');
            $table->string('context')->nullable();
            $table->string('module_name')->nullable();
            $table->string('page_url')->nullable();
            $table->text('comment');
            $table->boolean('would_recommend')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_entries');
    }
};
