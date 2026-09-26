<?php

declare(strict_types=1);

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'extensions:pdf,jpg,jpeg,png',
                'mimetypes:'.implode(',', config('cv.uploads.design_mimes')),
                'max:'.config('cv.uploads.max_kilobytes'),
            ],
        ];
    }
}
