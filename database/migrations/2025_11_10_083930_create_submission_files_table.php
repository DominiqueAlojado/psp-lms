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
        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_type'); // pdf, docx, jpg, etc.
            $table->integer('file_size'); // in bytes
            $table->string('mime_type');
            $table->integer('download_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('submission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_files');
    }
};
