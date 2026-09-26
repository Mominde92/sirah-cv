<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

final class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        // Quiet save: a login is not a profile change and must not fire model events.
        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
    }
}
