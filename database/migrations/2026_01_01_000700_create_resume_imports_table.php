<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Resume created from this import once the user confirms the review.
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            // Original upload, always kept on the private disk.
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedInteger('file_size');
            $table->longText('extracted_text')->nullable();
            // Fields the parser found evidence for.
            $table->json('parsed_data')->nullable();
            // Fields the parser was unsure about, with the source snippet ("Needs Review").
            $table->json('review_flags')->nullable();
            $table->string('parser', 40)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_imports');
    }
};
