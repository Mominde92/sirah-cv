<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Events\AccountDeleted;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Permanently deletes an account. Database rows cascade through foreign keys;
 * stored files are removed by listeners of AccountDeleted, so each feature
 * (exports, imports, design references...) cleans up what it owns.
 */
final class AccountDeletionService
{
    public function __construct(private readonly AvatarService $avatars)
    {
    }

    public function delete(User $user): void
    {
        $userId = $user->id;
        $email = $user->email;

        DB::transaction(function () use ($user): void {
            $this->avatars->remove($user);
            // Break the users <-> subscriptions cycle before the cascade runs.
            $user->forceFill(['subscription_id' => null])->save();
            $user->delete();
        });

        AccountDeleted::dispatch($userId, $email);
    }
}
