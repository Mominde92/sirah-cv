<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AtsStatus;
use App\Enums\TemplateCategory;
use App\Models\ResumeTemplate;
use App\Services\Templates\TemplateRegistry;
use Illuminate\Database\Seeder;

/**
 * The eight starter templates. Each is pure configuration on top of the shared
 * layouts, headers and section partials; no template needs its own code.
 * Idempotent: updates by slug.
 */
final class TemplateSeeder extends Seeder
{
    private const ATS_ALLOWED = [
        'layouts' => ['single-column'],
        'header_styles' => ['classic', 'centered', 'accent', 'compact'],
        'section_title_styles' => ['underline', 'plain', 'bar', 'caps', 'filled'],
        'fonts' => ['ibmplexsans', 'lato', 'ibmplexserif', 'ptserif'],
        'fonts_arabic' => ['ibmplexsansarabic', 'tajawal', 'xbriyaz'],
        'font_size' => [9, 12],
        'line_height' => [1.2, 1.7],
        'page_margin_mm' => [12, 25],
        'photo' => false,
    ];

    private const CREATIVE_ALLOWED = [
        'layouts' => ['single-column', 'sidebar-left', 'sidebar-right'],
        'header_styles' => ['classic', 'centered', 'accent', 'split', 'compact', 'banner'],
        'section_title_styles' => ['underline', 'plain', 'bar', 'caps', 'filled'],
        'fonts' => ['ibmplexsans', 'lato', 'ibmplexserif', 'ptserif'],
        'fonts_arabic' => ['ibmplexsansarabic', 'tajawal', 'xbriyaz'],
        'font_size' => [8.5, 12],
        'line_height' => [1.2, 1.7],
        'page_margin_mm' => [10, 22],
        'photo' => true,
    ];

    private const ATS_RULES = ['single_column_text_flow' => true, 'icons_decorative_only' => true, 'uses_tables' => false];

    public function run(TemplateRegistry $registry): void
    {
        foreach ($this->templates() as $index => $template) {
            ResumeTemplate::withTrashed()->updateOrCreate(
                ['slug' => $template['slug']],
                $template + ['supported_languages' => ['en', 'ar'], 'is_active' => true, 'sort_order' => $index + 1],
            )->restore();
        }

        $registry->flush();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'slug' => 'classic-ats',
                'name' => ['en' => 'Classic ATS', 'ar' => 'كلاسيكي ATS'],
                'description' => ['en' => 'A timeless single-column layout with a ruled header. The safest choice for online applications.', 'ar' => 'تصميم كلاسيكي بعمود واحد وترويسة مسطّرة. الخيار الأكثر أماناً للتقديم عبر الإنترنت.'],
                'category' => TemplateCategory::Ats, 'ats_status' => AtsStatus::AtsOptimized, 'layout' => 'single-column', 'is_premium' => false,
                'configuration' => [
                    'layout' => 'single-column',
                    'header' => ['style' => 'classic', 'show_photo' => false],
                    'defaults' => ['font_body' => 'ibmplexsans', 'font_heading' => 'ibmplexsans', 'font_arabic' => 'ibmplexsansarabic', 'font_size' => 10.5, 'heading_scale' => 1.25, 'line_height' => 1.4,
                        'color_primary' => '#1f2533', 'color_secondary' => '#4a5364', 'color_text' => '#121722', 'color_accent' => '#1f2533', 'page_margin_mm' => 18, 'section_spacing' => 13, 'item_spacing' => 8, 'section_title_style' => 'underline'],
                    'allowed' => self::ATS_ALLOWED,
                    'ats' => self::ATS_RULES,
                ],
            ],
            [
                'slug' => 'modern-ats',
                'name' => ['en' => 'Modern ATS', 'ar' => 'عصري ATS'],
                'description' => ['en' => 'Clean and contemporary, with a single accent colour and bar-style headings.', 'ar' => 'تصميم عصري ونظيف بلون مميز واحد وعناوين بخط جانبي.'],
                'category' => TemplateCategory::Ats, 'ats_status' => AtsStatus::AtsSafe, 'layout' => 'single-column', 'is_premium' => false,
                'configuration' => [
                    'layout' => 'single-column',
                    'header' => ['style' => 'accent', 'show_photo' => false],
                    'defaults' => ['font_body' => 'lato', 'font_heading' => 'lato', 'font_arabic' => 'tajawal', 'font_size' => 10.5, 'heading_scale' => 1.3, 'line_height' => 1.45,
                        'color_primary' => '#3a48e3', 'color_secondary' => '#4a5364', 'color_text' => '#121722', 'color_accent' => '#3a48e3', 'page_margin_mm' => 16, 'section_spacing' => 14, 'item_spacing' => 8, 'section_title_style' => 'bar'],
                    'allowed' => self::ATS_ALLOWED,
                    'ats' => self::ATS_RULES,
                ],
            ],
            [
                'slug' => 'executive-ats',
                'name' => ['en' => 'Executive ATS', 'ar' => 'تنفيذي ATS'],
                'description' => ['en' => 'A refined serif layout with a centered header for senior and leadership roles.', 'ar' => 'تصميم راقٍ بخط مذيّل وترويسة في المنتصف للمناصب القيادية.'],
                'category' => TemplateCategory::Ats, 'ats_status' => AtsStatus::AtsSafe, 'layout' => 'single-column', 'is_premium' => false,
                'configuration' => [
                    'layout' => 'single-column',
                    'header' => ['style' => 'centered', 'show_photo' => false],
                    'defaults' => ['font_body' => 'ptserif', 'font_heading' => 'ptserif', 'font_arabic' => 'xbriyaz', 'font_size' => 10.5, 'heading_scale' => 1.2, 'line_height' => 1.45,
                        'color_primary' => '#1e3a5f', 'color_secondary' => '#475467', 'color_text' => '#101828', 'color_accent' => '#7c5a2e', 'page_margin_mm' => 20, 'section_spacing' => 15, 'item_spacing' => 9, 'section_title_style' => 'caps'],
                    'allowed' => self::ATS_ALLOWED,
                    'ats' => self::ATS_RULES,
                ],
            ],
            [
                'slug' => 'minimal-ats',
                'name' => ['en' => 'Minimal ATS', 'ar' => 'بسيط ATS'],
                'description' => ['en' => 'Compact and quiet. Fits more content on one page without feeling crowded.', 'ar' => 'مضغوط وهادئ، يتسع لمحتوى أكثر في صفحة واحدة دون ازدحام.'],
                'category' => TemplateCategory::Ats, 'ats_status' => AtsStatus::AtsOptimized, 'layout' => 'single-column', 'is_premium' => false,
                'configuration' => [
                    'layout' => 'single-column',
                    'header' => ['style' => 'compact', 'show_photo' => false],
                    'defaults' => ['font_body' => 'lato', 'font_heading' => 'lato', 'font_arabic' => 'ibmplexsansarabic', 'font_size' => 10, 'heading_scale' => 1.15, 'line_height' => 1.35,
                        'color_primary' => '#111827', 'color_secondary' => '#667085', 'color_text' => '#111827', 'color_accent' => '#475467', 'page_margin_mm' => 14, 'section_spacing' => 11, 'item_spacing' => 6, 'section_title_style' => 'plain'],
                    'allowed' => self::ATS_ALLOWED,
                    'ats' => self::ATS_RULES,
                ],
            ],
            [
                'slug' => 'professional-ats',
                'name' => ['en' => 'Professional ATS', 'ar' => 'مهني ATS'],
                'description' => ['en' => 'Confident teal headings on soft bands. Structured and easy to scan.', 'ar' => 'عناوين بلون أخضر مزرق على خلفية هادئة. منظم وسهل القراءة السريعة.'],
                'category' => TemplateCategory::Ats, 'ats_status' => AtsStatus::AtsSafe, 'layout' => 'single-column', 'is_premium' => false,
                'configuration' => [
                    'layout' => 'single-column',
                    'header' => ['style' => 'classic', 'show_photo' => false],
                    'defaults' => ['font_body' => 'ibmplexsans', 'font_heading' => 'ibmplexsans', 'font_arabic' => 'ibmplexsansarabic', 'font_size' => 10.5, 'heading_scale' => 1.2, 'line_height' => 1.45,
                        'color_primary' => '#0f766e', 'color_secondary' => '#475467', 'color_text' => '#101828', 'color_accent' => '#0f766e', 'page_margin_mm' => 16, 'section_spacing' => 14, 'item_spacing' => 8, 'section_title_style' => 'filled'],
                    'allowed' => self::ATS_ALLOWED,
                    'ats' => self::ATS_RULES,
                ],
            ],
            [
                'slug' => 'modern-professional',
                'name' => ['en' => 'Modern Professional', 'ar' => 'مهني عصري'],
                'description' => ['en' => 'A bold full-height sidebar with photo and contact details beside a spacious main column.', 'ar' => 'شريط جانبي كامل الارتفاع بالصورة وبيانات التواصل بجانب عمود رئيسي واسع.'],
                'category' => TemplateCategory::Creative, 'ats_status' => AtsStatus::Creative, 'layout' => 'sidebar-left', 'is_premium' => true,
                'configuration' => [
                    'layout' => 'sidebar-left',
                    'header' => ['style' => 'accent', 'show_photo' => true],
                    'columns' => ['sidebar_width' => 32, 'sidebar_style' => 'solid', 'sidebar_sections' => ['skills', 'languages', 'certifications', 'courses', 'references']],
                    'defaults' => ['font_body' => 'ibmplexsans', 'font_heading' => 'ibmplexsans', 'font_arabic' => 'ibmplexsansarabic', 'font_size' => 10, 'heading_scale' => 1.25, 'line_height' => 1.45,
                        'color_primary' => '#262f7d', 'color_secondary' => '#4a5364', 'color_text' => '#121722', 'color_accent' => '#4c63f2', 'page_margin_mm' => 14, 'section_spacing' => 14, 'item_spacing' => 8, 'section_title_style' => 'underline'],
                    'allowed' => self::CREATIVE_ALLOWED,
                    'ats' => ['single_column_text_flow' => false],
                ],
            ],
            [
                'slug' => 'elegant',
                'name' => ['en' => 'Elegant', 'ar' => 'أنيق'],
                'description' => ['en' => 'Serif typography, a soft tinted side column and understated burgundy accents.', 'ar' => 'خطوط مذيّلة، وعمود جانبي بلون هادئ، ولمسات عنابية راقية.'],
                'category' => TemplateCategory::Creative, 'ats_status' => AtsStatus::Creative, 'layout' => 'sidebar-right', 'is_premium' => true,
                'configuration' => [
                    'layout' => 'sidebar-right',
                    'header' => ['style' => 'classic', 'show_photo' => false],
                    'columns' => ['sidebar_width' => 30, 'sidebar_style' => 'tint', 'sidebar_sections' => ['skills', 'languages', 'certifications', 'courses', 'awards']],
                    'defaults' => ['font_body' => 'ibmplexserif', 'font_heading' => 'ibmplexserif', 'font_arabic' => 'xbriyaz', 'font_size' => 10, 'heading_scale' => 1.2, 'line_height' => 1.45,
                        'color_primary' => '#9f1239', 'color_secondary' => '#57534e', 'color_text' => '#1c1917', 'color_accent' => '#9f1239', 'page_margin_mm' => 16, 'section_spacing' => 14, 'item_spacing' => 8, 'section_title_style' => 'caps'],
                    'allowed' => self::CREATIVE_ALLOWED,
                    'ats' => ['single_column_text_flow' => false],
                ],
            ],
            [
                'slug' => 'designer',
                'name' => ['en' => 'Designer', 'ar' => 'مصمم'],
                'description' => ['en' => 'A colour banner header and a light sidebar for portfolios and creative roles.', 'ar' => 'ترويسة ملونة وشريط جانبي خفيف للأعمال الإبداعية والمحافظ.'],
                'category' => TemplateCategory::Creative, 'ats_status' => AtsStatus::Creative, 'layout' => 'sidebar-left', 'is_premium' => true,
                'configuration' => [
                    'layout' => 'sidebar-left',
                    'header' => ['style' => 'banner', 'show_photo' => true],
                    'columns' => ['sidebar_width' => 30, 'sidebar_style' => 'tint', 'sidebar_sections' => ['skills', 'languages', 'certifications', 'courses', 'references']],
                    'defaults' => ['font_body' => 'lato', 'font_heading' => 'lato', 'font_arabic' => 'tajawal', 'font_size' => 10, 'heading_scale' => 1.25, 'line_height' => 1.45,
                        'color_primary' => '#6d28d9', 'color_secondary' => '#4a5364', 'color_text' => '#121722', 'color_accent' => '#6d28d9', 'page_margin_mm' => 14, 'section_spacing' => 14, 'item_spacing' => 8, 'section_title_style' => 'bar'],
                    'allowed' => self::CREATIVE_ALLOWED,
                    'ats' => ['single_column_text_flow' => false],
                ],
            ],
        ];
    }
}
