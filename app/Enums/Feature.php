<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Plan feature keys. Limits live in plan_features and are editable by admins.
 *
 * Counted features are checked against the number of existing records.
 * Metered features are checked against a per-month counter in feature_usages.
 */
enum Feature: string
{
    case Resumes = 'resumes';
    case PremiumTemplates = 'premium_templates';
    case PdfExport = 'pdf_export';
    case DocxExport = 'docx_export';
    case AiRequests = 'ai_requests';
    case CvImport = 'cv_import';
    case DesignReference = 'design_reference';
    case AdvancedCustomization = 'advanced_customization';
    case VersionHistory = 'version_history';

    public function isMetered(): bool
    {
        return match ($this) {
            self::PdfExport, self::DocxExport, self::AiRequests, self::CvImport, self::DesignReference => true,
            default => false,
        };
    }

    /**
     * Whether the feature is a plain on/off switch with no numeric limit.
     */
    public function isBoolean(): bool
    {
        return match ($this) {
            self::PremiumTemplates, self::AdvancedCustomization, self::VersionHistory => true,
            default => false,
        };
    }

    public function label(): string
    {
        return __('enums.feature.'.$this->value);
    }
}
