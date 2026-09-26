<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * The chosen template is inactive, premium-only for this user, or does not support the language.
 */
final class TemplateNotAvailable extends ValidationException
{
    public static function premium(): self
    {
        return self::withMessages(['template_id' => __('resumes.errors.template_premium')]);
    }

    public static function inactive(): self
    {
        return self::withMessages(['template_id' => __('resumes.errors.template_inactive')]);
    }

    public static function language(): self
    {
        return self::withMessages(['language' => __('resumes.errors.template_language')]);
    }
}
