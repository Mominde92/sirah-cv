<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DesignReference;
use App\Models\User;

final class DesignReferencePolicy
{
    public function view(User $user, DesignReference $design): bool
    {
        return $design->user_id === $user->id;
    }

    public function update(User $user, DesignReference $design): bool
    {
        return $design->user_id === $user->id;
    }

    public function delete(User $user, DesignReference $design): bool
    {
        return $design->user_id === $user->id;
    }
}
