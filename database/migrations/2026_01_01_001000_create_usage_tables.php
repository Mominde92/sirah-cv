<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 40);
            // First day of the metering month.
            $table->date('period_start');
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'feature', 'period_start']);
            $table->index(['feature', 'period_start']);
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            // Kept after account deletion for cost accounting.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature', 40);
            $table->string('provider', 30);
            $table->string('model', 100);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            // Estimated cost in millionths of a USD.
            $table->unsignedBigInteger('cost_micros')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('status', 20);
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['feature', 'created_at']);
            $table->index(['provider', 'created_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);
            $table->string('key', 100);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('feature_usages');
    }
};
