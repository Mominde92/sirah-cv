<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Design\DesignReferenceService;
use App\Services\Export\ExportService;
use App\Services\Import\ImportService;
use Illuminate\Console\Command;

/**
 * Deletes generated exports past their retention window and uploads that were
 * never confirmed. Scheduled hourly in routes/console.php.
 */
final class PruneExpiredFiles extends Command
{
    protected $signature = 'cv:prune-files';

    protected $description = 'Delete expired export files and abandoned uploads';

    public function handle(ExportService $exports, ImportService $imports, DesignReferenceService $designs): int
    {
        $this->info('Expired exports removed: '.$exports->pruneExpired());
        $this->info('Abandoned imports removed: '.$imports->pruneAbandoned());
        $this->info('Abandoned design uploads removed: '.$designs->pruneAbandoned());

        return self::SUCCESS;
    }
}
