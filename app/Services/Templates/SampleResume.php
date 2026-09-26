<?php

declare(strict_types=1);

namespace App\Services\Templates;

/**
 * Fictional resume content used for template previews and thumbnails.
 */
final class SampleResume
{
    /**
     * @return array{personal: array<string, string>, sections: list<array<string, mixed>>}
     */
    public function data(string $language): array
    {
        return $language === 'ar' ? $this->arabic() : $this->english();
    }

    /**
     * @return array{personal: array<string, string>, sections: list<array<string, mixed>>}
     */
    private function english(): array
    {
        return [
            'personal' => [
                'first_name' => 'Layla',
                'last_name' => 'Haddad',
                'headline' => 'Senior Product Designer',
                'email' => 'layla.haddad@example.com',
                'phone' => '+971 50 123 4567',
                'location' => 'Dubai, UAE',
                'linkedin' => 'https://linkedin.com/in/laylahaddad',
            ],
            'sections' => [
                ['type' => 'summary', 'items' => [['content' => 'Product designer with 8 years of experience shaping fintech and e-commerce products used by millions. Leads research-driven design, builds scalable design systems and partners closely with engineering to ship accessible, bilingual experiences.']]],
                ['type' => 'experience', 'items' => [
                    ['position' => 'Senior Product Designer', 'employer' => 'Northwind Pay', 'location' => 'Dubai', 'start_date' => '2021-03', 'is_current' => true,
                        'highlights' => ['Led the redesign of the mobile onboarding flow, raising completion by 23%.', 'Built a bilingual (EN/AR) design system adopted by 6 product teams.', 'Mentored 4 designers and introduced a weekly design critique.']],
                    ['position' => 'Product Designer', 'employer' => 'Souq Market', 'location' => 'Amman', 'start_date' => '2017-06', 'end_date' => '2021-02',
                        'highlights' => ['Designed checkout improvements that reduced cart abandonment by 11%.', 'Ran 40+ usability studies with customers across the region.']],
                ]],
                ['type' => 'education', 'items' => [
                    ['degree' => 'B.Sc.', 'field' => 'Human-Computer Interaction', 'institution' => 'American University of Sharjah', 'start_date' => '2012-09', 'end_date' => '2016-06'],
                ]],
                ['type' => 'skills', 'items' => [
                    ['name' => 'Product strategy', 'level' => 5], ['name' => 'User research', 'level' => 5], ['name' => 'Design systems', 'level' => 4],
                    ['name' => 'Figma', 'level' => 5], ['name' => 'Prototyping', 'level' => 4], ['name' => 'Accessibility', 'level' => 4],
                ]],
                ['type' => 'languages', 'items' => [['name' => 'Arabic', 'proficiency' => 'native'], ['name' => 'English', 'proficiency' => 'fluent']]],
                ['type' => 'certifications', 'items' => [['name' => 'Certified Usability Analyst', 'issuer' => 'Human Factors International', 'issued_at' => '2019-05']]],
            ],
        ];
    }

    /**
     * @return array{personal: array<string, string>, sections: list<array<string, mixed>>}
     */
    private function arabic(): array
    {
        return [
            'personal' => [
                'first_name' => 'ليلى',
                'last_name' => 'حداد',
                'headline' => 'مصممة منتجات أولى',
                'email' => 'layla.haddad@example.com',
                'phone' => '+971 50 123 4567',
                'location' => 'دبي، الإمارات',
                'linkedin' => 'https://linkedin.com/in/laylahaddad',
            ],
            'sections' => [
                ['type' => 'summary', 'items' => [['content' => 'مصممة منتجات بخبرة 8 سنوات في تصميم منتجات التقنية المالية والتجارة الإلكترونية التي يستخدمها الملايين. أقود التصميم المبني على البحث، وأبني أنظمة تصميم قابلة للتوسع، وأعمل بشكل وثيق مع فرق التطوير لإطلاق تجارب ثنائية اللغة وسهلة الوصول.']]],
                ['type' => 'experience', 'items' => [
                    ['position' => 'مصممة منتجات أولى', 'employer' => 'نورثويند باي', 'location' => 'دبي', 'start_date' => '2021-03', 'is_current' => true,
                        'highlights' => ['قدت إعادة تصميم رحلة التسجيل في التطبيق، ما رفع نسبة الإكمال بنسبة 23%.', 'بنيت نظام تصميم ثنائي اللغة اعتمدته 6 فرق منتجات.', 'أشرفت على 4 مصممين وأطلقت جلسة مراجعة تصميم أسبوعية.']],
                    ['position' => 'مصممة منتجات', 'employer' => 'سوق ماركت', 'location' => 'عمّان', 'start_date' => '2017-06', 'end_date' => '2021-02',
                        'highlights' => ['صممت تحسينات على صفحة الدفع خفّضت التخلي عن السلة بنسبة 11%.', 'أجريت أكثر من 40 دراسة قابلية استخدام مع عملاء في المنطقة.']],
                ]],
                ['type' => 'education', 'items' => [
                    ['degree' => 'بكالوريوس', 'field' => 'التفاعل بين الإنسان والحاسوب', 'institution' => 'الجامعة الأمريكية في الشارقة', 'start_date' => '2012-09', 'end_date' => '2016-06'],
                ]],
                ['type' => 'skills', 'items' => [
                    ['name' => 'استراتيجية المنتج', 'level' => 5], ['name' => 'أبحاث المستخدمين', 'level' => 5], ['name' => 'أنظمة التصميم', 'level' => 4],
                    ['name' => 'Figma', 'level' => 5], ['name' => 'النماذج الأولية', 'level' => 4], ['name' => 'إمكانية الوصول', 'level' => 4],
                ]],
                ['type' => 'languages', 'items' => [['name' => 'العربية', 'proficiency' => 'native'], ['name' => 'الإنجليزية', 'proficiency' => 'fluent']]],
                ['type' => 'certifications', 'items' => [['name' => 'محلل قابلية استخدام معتمد', 'issuer' => 'Human Factors International', 'issued_at' => '2019-05']]],
            ],
        ];
    }
}
