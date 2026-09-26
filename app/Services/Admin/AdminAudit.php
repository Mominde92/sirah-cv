<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Records who changed what in the admin panel (application log, "admin" context).
 */
final class AdminAudit
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(User $admin, string $action, array $context = []): void
    {
        Log::info('admin.'.$action, ['admin_id' => $admin->id, 'admin_email' => $admin->email, 'ip' => request()->ip()] + $context);
    }
}
