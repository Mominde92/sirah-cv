<?php

declare(strict_types=1);

namespace App\Http\Requests\Import;

use Illuminate\Foundation\Http\FormRequest;

final class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Extension and real MIME type (from file contents) must both match.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'extensions:pdf,docx',
                'mimetypes:'.implode(',', config('cv.uploads.import_mimes')),
                'max:'.config('cv.uploads.max_kilobytes'),
            ],
        ];
    }
}
