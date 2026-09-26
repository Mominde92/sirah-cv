<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use App\Services\Resume\ResumeService;
use App\Services\Templates\SampleResume;
use App\Services\Templates\TemplateRegistry;
use Illuminate\Database\Seeder;

/**
 * Local demo data (never run in production): php artisan db:seed --class=DemoSeeder
 * Creates demo@sirah.test / Demo-pass-2026 on Premium with two sample resumes.
 */
final class DemoSeeder extends Seeder
{
    public function run(ResumeService $resumes, SubscriptionService $subscriptions, TemplateRegistry $templates, SampleResume $samples): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoSeeder is disabled in production.');

            return;
        }

        $user = User::query()->firstOrNew(['email' => 'demo@sirah.test']);
        $user->forceFill(['name' => 'Layla Haddad', 'password' => 'Demo-pass-2026', 'locale' => 'en', 'email_verified_at' => now()])->save();

        $subscriptions->grant($user, Plan::where('slug', 'premium')->firstOrFail(), null);

        if ($user->resumes()->doesntExist()) {
            foreach ([['en', 'modern-professional', 'Product Designer — English'], ['ar', 'classic-ats', 'مصممة منتجات — عربي']] as [$language, $slug, $title]) {
                $sample = $samples->data($language);
                $resumes->create($user, $title, $templates->findActiveBySlug($slug), $language, personal: $sample['personal'], sections: $sample['sections']);
            }
        }

        $this->command?->info('Demo user ready: demo@sirah.test / Demo-pass-2026');
    }
}
