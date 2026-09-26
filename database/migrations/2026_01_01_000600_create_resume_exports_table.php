<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_exports', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Kept after the resume is deleted so download statistics stay intact.
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->string('format', 10);
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('resume_version')->nullable();
            $table->string('disk', 30)->default('local');
            $table->string('path')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->json('options')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'expires_at']);
            $table->index(['format', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_exports');
    }
};
