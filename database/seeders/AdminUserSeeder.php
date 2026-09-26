<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the first administrator from ADMIN_EMAIL / ADMIN_PASSWORD.
 * Skipped when they are not set, so no default credentials ever exist.
 */
final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('cv.admin.email');
        $password = config('cv.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set: skipping admin user.');

            return;
        }

        $admin = User::query()->firstOrNew(['email' => mb_strtolower($email)]);

        $admin->forceFill([
            'name' => $admin->name ?? config('cv.admin.name'),
            'password' => $admin->exists ? $admin->password : $password,
            'role' => UserRole::Admin,
            'locale' => $admin->locale ?? config('app.locale'),
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ])->save();

        $this->command?->info("Admin user ready: {$admin->email}");
    }
}
