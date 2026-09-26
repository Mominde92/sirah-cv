<?php

declare(strict_types=1);

namespace App\Http\Requests\Import;

use App\Services\Localization\LocaleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Only structure is validated here; every value is sanitised by SectionSchema /
 * PersonalInfoSchema when the resume is created.
 */
final class ConfirmImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('import'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'template_id' => ['required', 'integer', Rule::exists('resume_templates', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'language' => ['required', Rule::in(app(LocaleService::class)->codes())],
            'personal' => ['array'],
            'personal.*' => ['nullable', 'string', 'max:255'],
            'sections' => ['array', 'max:30'],
            'sections.*.type' => ['required', 'string'],
            'sections.*.title' => ['nullable', 'string', 'max:120'],
            'sections.*.include' => ['sometimes', 'boolean'],
            'sections.*.items' => ['array', 'max:50'],
        ];
    }
}
