<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ResumeExport;
use App\Models\User;

final class ResumeExportPolicy
{
    public function view(User $user, ResumeExport $export): bool
    {
        return $export->user_id === $user->id;
    }

    public function download(User $user, ResumeExport $export): bool
    {
        return $export->user_id === $user->id;
    }
}
