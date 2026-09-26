<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe seeders only. Demo data belongs in a separate DemoSeeder.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            TemplateSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
