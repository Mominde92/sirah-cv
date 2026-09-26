<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ResumeImport;
use App\Models\User;

final class ResumeImportPolicy
{
    public function view(User $user, ResumeImport $import): bool
    {
        return $import->user_id === $user->id;
    }

    public function update(User $user, ResumeImport $import): bool
    {
        return $import->user_id === $user->id;
    }

    public function delete(User $user, ResumeImport $import): bool
    {
        return $import->user_id === $user->id;
    }
}
