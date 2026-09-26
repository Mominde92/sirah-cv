<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Data\TemplateConfiguration;
use App\Enums\AtsStatus;
use App\Enums\TemplateCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class TemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('access-admin');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $template = $this->route('template');

        return [
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('resume_templates', 'slug')->ignore($template?->id)],
            'name_en' => ['required', 'string', 'max:120'],
            'name_ar' => ['required', 'string', 'max:120'],
            'description_en' => ['nullable', 'string', 'max:500'],
            'description_ar' => ['nullable', 'string', 'max:500'],
            'category' => ['required', Rule::enum(TemplateCategory::class)],
            'ats_status' => ['required', Rule::enum(AtsStatus::class)],
            'is_premium' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'supported_languages' => ['required', 'array', 'min:1'],
            'supported_languages.*' => ['string', Rule::in(array_keys(config('cv.locales')))],
            'configuration' => ['required', 'json'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $config = json_decode((string) $this->input('configuration'), true);

            if (! is_array($config)) {
                $validator->errors()->add('configuration', __('admin.templates.invalid_json'));

                return;
            }

            if (! in_array($config['layout'] ?? null, TemplateConfiguration::LAYOUTS, true)) {
                $validator->errors()->add('configuration', __('admin.templates.invalid_layout', ['layouts' => implode(', ', TemplateConfiguration::LAYOUTS)]));
            }

            // An ATS-labelled template must stay single-column: the label is a promise to users.
            if ($this->input('ats_status') !== AtsStatus::Creative->value && ($config['layout'] ?? null) !== 'single-column') {
                $validator->errors()->add('ats_status', __('admin.templates.ats_single_column'));
            }
        }];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForModel(): array
    {
        $config = json_decode((string) $this->input('configuration'), true);

        return [
            'slug' => $this->input('slug'),
            'name' => ['en' => $this->input('name_en'), 'ar' => $this->input('name_ar')],
            'description' => ['en' => (string) $this->input('description_en'), 'ar' => (string) $this->input('description_ar')],
            'category' => $this->input('category'),
            'ats_status' => $this->input('ats_status'),
            'layout' => $config['layout'],
            'supported_languages' => array_values($this->input('supported_languages')),
            'configuration' => $config,
            'is_premium' => $this->boolean('is_premium'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->integer('sort_order'),
        ];
    }
}
