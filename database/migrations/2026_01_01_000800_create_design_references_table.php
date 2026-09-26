<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_references', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('disk', 30)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedInteger('file_size');
            // Detected structure and style: layout, columns, palette, typography, spacing, header, section order.
            $table->json('analysis')->nullable();
            $table->foreignId('suggested_template_id')->nullable()->constrained('resume_templates')->nullOnDelete();
            // Heuristic layout similarity (0-100) to the suggested template; not an ATS metric.
            $table->unsignedTinyInteger('similarity')->nullable();
            // Styling overrides inspired by the reference, applicable to the suggested template.
            $table->json('generated_styling')->nullable();
            // Resume the generated design was applied to.
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->string('analyzer', 40)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_references');
    }
};
