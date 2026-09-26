<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccountDeleted;
use Illuminate\Support\Facades\Storage;

/**
 * Database rows cascade with the user; stored files are removed here.
 */
final class DeleteUserFiles
{
    private const DIRECTORIES = ['exports', 'imports', 'designs', 'resume-photos'];

    public function handle(AccountDeleted $event): void
    {
        $disk = Storage::disk(config('cv.disks.private', 'local'));

        foreach (self::DIRECTORIES as $directory) {
            $disk->deleteDirectory($directory.'/'.$event->userId);
        }
    }
}
