<?php

declare(strict_types=1);

namespace App\Enums;

enum TemplateCategory: string
{
    case Ats = 'ats';
    case Creative = 'creative';

    public function label(): string
    {
        return __('enums.template_category.'.$this->value);
    }
}
