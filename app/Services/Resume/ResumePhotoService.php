<?php

declare(strict_types=1);

namespace App\Services\Resume;

use App\Models\Resume;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resume photos: re-encoded to a 600×600 JPEG on the private disk. Re-encoding strips
 * metadata (EXIF location), neutralises polyglot files and gives mPDF a format it supports.
 */
final class ResumePhotoService
{
    private const SIZE = 600;

    public function __construct(private readonly ResumeEditorService $editor)
    {
    }

    public function store(Resume $resume, UploadedFile $file): string
    {
        $source = $this->load($file);
        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $square = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($square, 0, 0, imagecolorallocate($square, 255, 255, 255));
        imagecopyresampled($square, $source, 0, 0, (int) (($width - $side) / 2), (int) (($height - $side) / 2), self::SIZE, self::SIZE, $side, $side);

        ob_start();
        imagejpeg($square, null, 88);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($square);

        $path = 'resume-photos/'.$resume->user_id.'/'.Str::uuid().'.jpg';
        $this->disk()->put($path, $jpeg);

        $this->deleteFile($resume->data['personal']['photo_path'] ?? null);
        $this->editor->setPhoto($resume, $path);

        return $path;
    }

    public function remove(Resume $resume): void
    {
        $this->deleteFile($resume->data['personal']['photo_path'] ?? null);
        $this->editor->setPhoto($resume, '');
    }

    /**
     * @return \GdImage
     */
    private function load(UploadedFile $file): mixed
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('Unsupported image.');
        }

        return $image;
    }

    private function deleteFile(mixed $path): void
    {
        if (is_string($path) && $path !== '') {
            $this->disk()->delete($path);
        }
    }

    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(config('cv.disks.private', 'local'));
    }
}
