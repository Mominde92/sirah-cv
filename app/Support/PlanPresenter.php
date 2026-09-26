<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BillingInterval;
use App\Enums\Feature;
use App\Models\Plan;
use Illuminate\Support\Number;

/**
 * Human-readable prices and feature lines for pricing tables, derived from the
 * admin-editable plan_features rows (never hard-coded per plan).
 */
final class PlanPresenter
{
    public function price(Plan $plan, BillingInterval $interval): string
    {
        $amount = $interval === BillingInterval::Yearly ? $plan->price_yearly : $plan->price_monthly;

        // Prices keep Western digits in every locale so they read consistently next to currency codes.
        return (string) Number::currency(
            $amount / 100,
            in: $plan->currency,
            locale: 'en',
            precision: $amount % 100 === 0 ? 0 : 2,
        );
    }

    /**
     * Yearly saving compared to twelve monthly payments, as a whole percentage.
     */
    public function yearlySavingPercent(Plan $plan): int
    {
        $twelveMonths = $plan->price_monthly * 12;

        if ($twelveMonths === 0 || $plan->price_yearly >= $twelveMonths) {
            return 0;
        }

        return (int) round((1 - $plan->price_yearly / $twelveMonths) * 100);
    }

    /**
     * @return list<array{text: string, included: bool}>
     */
    public function featureLines(Plan $plan): array
    {
        $lines = [];

        foreach (Feature::cases() as $feature) {
            $planFeature = $plan->feature($feature);
            $enabled = $planFeature !== null && $planFeature->is_enabled;
            $limit = $planFeature?->limit_value;

            $lines[] = match (true) {
                // Without premium templates a plan still includes the free ones.
                $feature === Feature::PremiumTemplates => [
                    'text' => __('billing.lines.premium_templates.'.($enabled ? 'enabled' : 'disabled')),
                    'included' => true,
                ],
                ! $enabled => ['text' => __('billing.lines.'.$feature->value.'.name'), 'included' => false],
                $feature->isBoolean() => ['text' => __('billing.lines.'.$feature->value.'.name'), 'included' => true],
                $limit === null => ['text' => __('billing.lines.'.$feature->value.'.unlimited'), 'included' => true],
                default => ['text' => __('billing.lines.'.$feature->value.'.limited', ['count' => $limit]), 'included' => true],
            };
        }

        return $lines;
    }
}
