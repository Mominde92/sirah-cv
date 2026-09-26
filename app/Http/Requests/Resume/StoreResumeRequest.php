<?php

declare(strict_types=1);

namespace App\Http\Requests\Resume;

use App\Models\Resume;
use App\Services\Localization\LocaleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Resume::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'template_id' => ['required', 'integer', Rule::exists('resume_templates', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'language' => ['required', 'string', Rule::in(app(LocaleService::class)->codes())],
        ];
    }
}
