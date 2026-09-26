<?php

declare(strict_types=1);

namespace App\Http\Requests\Resume;

use Illuminate\Foundation\Http\FormRequest;

final class RenameResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('resume'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
        ];
    }
}
