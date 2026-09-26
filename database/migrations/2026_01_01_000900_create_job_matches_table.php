<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->string('job_title', 200)->nullable();
            $table->string('company', 200)->nullable();
            $table->mediumText('job_description');
            $table->string('status', 20)->default('pending');
            // keywords_found, keywords_missing, relevant_skills, suggestions.
            $table->json('analysis')->nullable();
            $table->unsignedInteger('resume_version')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['resume_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_matches');
    }
};
