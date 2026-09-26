<?php

declare(strict_types=1);

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores profile pictures under random names on the public-assets disk.
 * Resume photos are not avatars: they live on the private disk (see Phase 5).
 */
final class AvatarService
{
    private const DIRECTORY = 'avatars';

    public function replace(User $user, UploadedFile $file): string
    {
        $path = $file->store(self::DIRECTORY, $this->disk());

        $this->deleteFile($user->avatar_path);

        $user->forceFill(['avatar_path' => $path])->save();

        return $path;
    }

    public function remove(User $user): void
    {
        $this->deleteFile($user->avatar_path);

        $user->forceFill(['avatar_path' => null])->save();
    }

    public function url(User $user): ?string
    {
        return $user->avatar_path !== null ? Storage::disk($this->disk())->url($user->avatar_path) : null;
    }

    private function deleteFile(?string $path): void
    {
        if ($path !== null) {
            Storage::disk($this->disk())->delete($path);
        }
    }

    private function disk(): string
    {
        return (string) config('cv.disks.public', 'public');
    }
}
