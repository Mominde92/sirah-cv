<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Feature;
use App\Models\Plan;
use App\Services\Billing\PlanResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Starter plans. Idempotent: re-running updates plans by slug and never duplicates.
 * After launch, limits are managed from the admin panel; this seeder only creates them.
 */
final class PlanSeeder extends Seeder
{
    public function run(PlanResolver $resolver): void
    {
        DB::transaction(function (): void {
            foreach ($this->plans() as $definition) {
                $features = $definition['features'];
                unset($definition['features']);

                $plan = Plan::withTrashed()->updateOrCreate(['slug' => $definition['slug']], $definition);

                if ($plan->trashed()) {
                    $plan->restore();
                }

                foreach ($features as $feature => [$enabled, $limit]) {
                    $plan->features()->updateOrCreate(
                        ['feature' => $feature],
                        ['is_enabled' => $enabled, 'limit_value' => $limit],
                    );
                }
            }
        });

        $resolver->flush();
    }

    /**
     * Feature map values: [enabled, limit]. A NULL limit means unlimited.
     *
     * @return list<array<string, mixed>>
     */
    private function plans(): array
    {
        return [
            [
                'slug' => 'free',
                'name' => ['en' => 'Free', 'ar' => 'مجاني'],
                'description' => [
                    'en' => 'Everything you need to create your first professional resume.',
                    'ar' => 'كل ما تحتاجه لإنشاء أول سيرة ذاتية احترافية.',
                ],
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'USD',
                'is_default' => true,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 1,
                'features' => [
                    Feature::Resumes->value => [true, 2],
                    Feature::PremiumTemplates->value => [false, null],
                    Feature::PdfExport->value => [true, 3],
                    Feature::DocxExport->value => [false, null],
                    Feature::AiRequests->value => [false, null],
                    Feature::CvImport->value => [false, null],
                    Feature::DesignReference->value => [false, null],
                    Feature::AdvancedCustomization->value => [false, null],
                    Feature::VersionHistory->value => [false, null],
                ],
            ],
            [
                'slug' => 'pro',
                'name' => ['en' => 'Pro', 'ar' => 'احترافي'],
                'description' => [
                    'en' => 'Unlimited resumes, every template, Word export and AI writing help.',
                    'ar' => 'سير ذاتية غير محدودة، جميع القوالب، تصدير Word ومساعدة الذكاء الاصطناعي في الكتابة.',
                ],
                'price_monthly' => 900,
                'price_yearly' => 7900,
                'currency' => 'USD',
                'is_default' => false,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 2,
                'features' => [
                    Feature::Resumes->value => [true, null],
                    Feature::PremiumTemplates->value => [true, null],
                    Feature::PdfExport->value => [true, null],
                    Feature::DocxExport->value => [true, null],
                    Feature::AiRequests->value => [true, 100],
                    Feature::CvImport->value => [false, null],
                    Feature::DesignReference->value => [false, null],
                    Feature::AdvancedCustomization->value => [false, null],
                    Feature::VersionHistory->value => [true, null],
                ],
            ],
            [
                'slug' => 'premium',
                'name' => ['en' => 'Premium', 'ar' => 'بريميوم'],
                'description' => [
                    'en' => 'The full toolkit: CV import, design matching, advanced customization and more AI.',
                    'ar' => 'الأدوات الكاملة: استيراد السيرة، مطابقة التصاميم، تخصيص متقدم ومساعدة أكبر من الذكاء الاصطناعي.',
                ],
                'price_monthly' => 1900,
                'price_yearly' => 16900,
                'currency' => 'USD',
                'is_default' => false,
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 3,
                'features' => [
                    Feature::Resumes->value => [true, null],
                    Feature::PremiumTemplates->value => [true, null],
                    Feature::PdfExport->value => [true, null],
                    Feature::DocxExport->value => [true, null],
                    Feature::AiRequests->value => [true, 500],
                    Feature::CvImport->value => [true, 50],
                    Feature::DesignReference->value => [true, 30],
                    Feature::AdvancedCustomization->value => [true, null],
                    Feature::VersionHistory->value => [true, null],
                ],
            ],
        ];
    }
}
