<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('resume_templates')->restrictOnDelete();
            // The resume this one was duplicated from, if any.
            $table->foreignId('parent_id')->nullable()->constrained('resumes')->nullOnDelete();
            $table->string('title', 150);
            $table->string('language', 10)->default('en');
            $table->string('status', 20)->default('draft');
            $table->string('source', 20)->default('scratch');
            // Personal information block (name, headline, contacts, links, photo reference).
            $table->json('data');
            // User overrides of the template defaults.
            $table->json('styling')->nullable();
            // Optimistic-lock counter, incremented on every save.
            $table->unsignedInteger('version')->default(1);
            $table->string('thumbnail_path')->nullable();
            $table->timestamp('last_edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'updated_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('resume_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            // Custom heading; NULL falls back to the standard translated name.
            $table->string('title', 120)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            // Column placement override for multi-column layouts ("main" / "sidebar").
            $table->string('column', 20)->nullable();
            // Ordered entries of the section, each with its own client-generated "id".
            $table->json('items');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['resume_id', 'position']);
        });

        Schema::create('resume_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('label', 120)->nullable();
            // Full document: template_id, data, styling, sections.
            $table->json('snapshot');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['resume_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_versions');
        Schema::dropIfExists('resume_sections');
        Schema::dropIfExists('resumes');
    }
};
