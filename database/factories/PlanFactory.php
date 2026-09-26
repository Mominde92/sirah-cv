<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Feature;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'name' => ['en' => $name, 'ar' => $name],
            'description' => ['en' => fake()->sentence(), 'ar' => fake()->sentence()],
            'price_monthly' => 900,
            'price_yearly' => 7900,
            'currency' => 'USD',
            'is_active' => true,
            'is_public' => true,
            'is_default' => false,
            'sort_order' => 10,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'price_monthly' => 0,
            'price_yearly' => 0,
        ]);
    }

    /**
     * @param  array<string, array{0: bool, 1: int|null}>  $features  feature value => [enabled, limit]
     */
    public function withFeatures(array $features): static
    {
        return $this->afterCreating(function (Plan $plan) use ($features): void {
            foreach ($features as $feature => [$enabled, $limit]) {
                $plan->features()->create([
                    'feature' => Feature::from($feature),
                    'is_enabled' => $enabled,
                    'limit_value' => $limit,
                ]);
            }
        });
    }
}
