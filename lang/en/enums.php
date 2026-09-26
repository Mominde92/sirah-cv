<?php

return [
    'resume_status' => [
        'draft' => 'Draft',
        'completed' => 'Completed',
        'archived' => 'Archived',
    ],

    'template_category' => [
        'ats' => 'ATS templates',
        'creative' => 'Creative templates',
    ],

    'ats_status' => [
        'ats_safe' => [
            'label' => 'ATS Safe',
            'short' => 'ATS Safe',
            'description' => 'Single column, real selectable text and standard section headings. No tables, text in images or graphics that carry information. Built to be read correctly by common applicant tracking systems.',
        ],
        'ats_optimized' => [
            'label' => 'ATS Optimized',
            'short' => 'ATS Optimized',
            'description' => 'Everything in ATS Safe, plus conservative fonts, sizes and spacing, and contact details in the body text instead of a header or footer, which some parsers skip.',
        ],
        'creative' => [
            'label' => 'Creative',
            'short' => 'Creative',
            'description' => 'Visual layouts with columns, sidebars and colour. Text stays real text, but some ATS systems may read multi-column content in the wrong order. Best for sending directly to people.',
        ],
    ],

    'feature' => [
        'resumes' => 'Resumes',
        'premium_templates' => 'Premium templates',
        'pdf_export' => 'PDF downloads',
        'docx_export' => 'Word downloads',
        'ai_requests' => 'AI requests',
        'cv_import' => 'CV import',
        'design_reference' => 'Design matching',
        'advanced_customization' => 'Advanced customization',
        'version_history' => 'Version history',
    ],
];
