<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Billing\SubscriptionService;
use Illuminate\Console\Command;

final class ExpireSubscriptions extends Command
{
    protected $signature = 'cv:expire-subscriptions';

    protected $description = 'Expire subscriptions whose paid period has ended';

    public function handle(SubscriptionService $subscriptions): int
    {
        $this->info('Subscriptions expired: '.$subscriptions->expireDue());

        return self::SUCCESS;
    }
}
