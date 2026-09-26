<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Resume;
use App\Models\User;

/**
 * Resumes are private to their owner. Administrators manage metadata through the
 * admin panel's own gate and never gain access to resume content through this policy.
 */
final class ResumePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Resume $resume): bool
    {
        return $resume->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return ! $user->isSuspended();
    }

    public function update(User $user, Resume $resume): bool
    {
        return $resume->isOwnedBy($user);
    }

    public function duplicate(User $user, Resume $resume): bool
    {
        return $resume->isOwnedBy($user);
    }

    public function export(User $user, Resume $resume): bool
    {
        return $resume->isOwnedBy($user);
    }

    public function delete(User $user, Resume $resume): bool
    {
        return $resume->isOwnedBy($user);
    }
}
