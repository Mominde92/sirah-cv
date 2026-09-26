<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('category', 20);
            $table->string('ats_status', 20);
            // Structural skeleton view key, e.g. "single-column", "sidebar-left".
            $table->string('layout', 40);
            $table->string('preview_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->json('supported_languages');
            // Defaults, allowed ranges and layout options; see docs/ARCHITECTURE.md §3.
            $table->json('configuration');
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'category', 'sort_order']);
            $table->index('ats_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_templates');
    }
};
