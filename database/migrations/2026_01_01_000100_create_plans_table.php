<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            // Prices in minor units (cents) to avoid floating point errors.
            $table->unsignedInteger('price_monthly')->default(0);
            $table->unsignedInteger('price_yearly')->default(0);
            $table->char('currency', 3)->default('USD');
            $table->unsignedSmallInteger('trial_days')->default(0);
            // Gateway price identifiers, e.g. {"stripe": {"monthly": "price_...", "yearly": "price_..."}}.
            $table->json('provider_price_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 40);
            $table->boolean('is_enabled')->default(true);
            // NULL means unlimited. Ignored for boolean features.
            $table->unsignedInteger('limit_value')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
