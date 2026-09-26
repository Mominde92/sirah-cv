<?php

return [
    'meta_description' => 'Create professional, ATS-friendly resumes in English and Arabic. Live preview, beautiful templates, PDF and Word downloads.',

    'nav' => [
        'templates' => 'Templates',
        'features' => 'Features',
        'ats' => 'ATS',
        'pricing' => 'Pricing',
        'faq' => 'FAQ',
    ],

    'cta' => [
        'start_free' => 'Create my resume',
    ],

    'hero' => [
        'eyebrow' => 'Bilingual resume builder',
        'title' => 'A resume that gets read,',
        'title_highlight' => 'by people and by ATS.',
        'subtitle' => 'Write once, preview live and download a polished PDF or an editable Word file. English and Arabic, left-to-right and right-to-left, with templates designed to pass applicant tracking systems.',
        'browse_templates' => 'Browse templates',
        'chip_ats' => 'Template check',
        'points' => [
            'ats' => 'ATS-friendly templates',
            'bilingual' => 'English & Arabic (RTL)',
            'formats' => 'PDF and Word downloads',
        ],
    ],

    'templates' => [
        'eyebrow' => 'Templates',
        'title' => 'Start from a design built for your goal',
        'subtitle' => 'Conservative ATS layouts for online applications, and creative layouts for sending directly to people. Switch at any time without retyping anything.',
        'items' => [
            'classic-ats' => 'Classic ATS',
            'modern-ats' => 'Modern ATS',
            'executive-ats' => 'Executive ATS',
            'minimal-ats' => 'Minimal ATS',
            'professional-ats' => 'Professional ATS',
            'modern-professional' => 'Modern Professional',
            'elegant' => 'Elegant',
            'designer' => 'Designer',
        ],
    ],

    'features' => [
        'eyebrow' => 'Features',
        'title' => 'Everything a serious job search needs',
        'subtitle' => 'A focused editor, honest guidance and exports that look exactly like the preview.',
        'items' => [
            'live_preview' => ['title' => 'Live preview', 'body' => 'See every change on an A4 page as you type. What you see is what you download.'],
            'ats' => ['title' => 'ATS-friendly by design', 'body' => 'Real text, standard headings and parser-safe layouts, with a clear label on every template.'],
            'export' => ['title' => 'PDF and Word', 'body' => 'A pixel-accurate PDF for sending and an editable DOCX for recruiters who ask for Word.'],
            'bilingual' => ['title' => 'Arabic and English', 'body' => 'Proper right-to-left layout, Arabic fonts and a separate resume per language.'],
            'import' => ['title' => 'Import your old CV', 'body' => 'Upload a PDF or Word file. We extract what we can and flag anything uncertain for your review.'],
            'design_match' => ['title' => 'Match a design you like', 'body' => 'Upload a resume design. We analyse its layout and style and suggest the closest original template.'],
            'ai' => ['title' => 'AI writing help', 'body' => 'Sharper summaries and bullet points based only on what you wrote. Never invented experience.'],
            'job_match' => ['title' => 'Optimize for a job', 'body' => 'Paste a job description to see matching and missing keywords from your own experience.'],
            'versions' => ['title' => 'Versions and copies', 'body' => 'Keep one resume per role, duplicate in a click and restore earlier versions.'],
        ],
    ],

    'ats' => [
        'eyebrow' => 'Applicant tracking systems',
        'title' => 'Designed to be parsed, not just admired',
        'body' => 'Most companies screen applications with software before a person reads them. Our ATS templates follow the practices that keep your details readable.',
        'rules' => [
            'real_text' => 'All text is real, selectable text. Nothing important lives inside images or SVG.',
            'headings' => 'Standard section names such as "Work Experience" and "Education".',
            'single_column' => 'Single-column reading order, with no tables or text boxes.',
            'no_graphics' => 'Icons and colour are decoration only. They never carry information.',
            'typography' => 'Controlled fonts, sizes, line height, margins and page breaks.',
        ],
        'honesty' => 'We label templates as ATS Safe, ATS Optimized or Creative based on these rules. We do not show a percentage "ATS score": each ATS vendor parses differently, and no single number can honestly predict it.',
    ],

    'how' => [
        'eyebrow' => 'How it works',
        'title' => 'From blank page to ready to send in minutes',
        'steps' => [
            'choose' => ['title' => 'Choose a template', 'body' => 'Pick an ATS or creative design, in English or Arabic.'],
            'fill' => ['title' => 'Add your details', 'body' => 'Guided sections, or import an existing CV to start faster.'],
            'design' => ['title' => 'Make it yours', 'body' => 'Reorder sections, adjust fonts, colours and spacing.'],
            'download' => ['title' => 'Download', 'body' => 'Export a PDF or Word file that matches the preview.'],
        ],
    ],

    'pricing' => [
        'eyebrow' => 'Pricing',
        'title' => 'Simple plans that grow with your search',
        'subtitle' => 'Start free. Upgrade when you need more templates, formats or AI help.',
        'billing_period' => 'Billing period',
        'save_up_to' => 'Save up to :percent%',
        'popular' => 'Most popular',
        'choose' => 'Choose :plan',
        'not_included' => 'Not included',
        'empty' => 'Plans will be available soon.',
    ],

    'faq' => [
        'eyebrow' => 'FAQ',
        'title' => 'Questions, answered',
        'subtitle' => 'Anything else? Reach out from your dashboard and we will help.',
        'items' => [
            'ats_score' => [
                'q' => 'Why don\'t you show an ATS score?',
                'a' => 'Each applicant tracking system parses resumes differently, so a single percentage would be a guess. Instead, every template is labelled ATS Safe, ATS Optimized or Creative according to concrete, checkable rules.',
            ],
            'arabic' => [
                'q' => 'Does it work properly in Arabic?',
                'a' => 'Yes. Arabic resumes use a right-to-left layout, Arabic fonts and correctly shaped letters in the preview, the PDF and the Word file.',
            ],
            'word' => [
                'q' => 'Will the Word file look like the PDF?',
                'a' => 'As close as Word allows. For complex creative layouts we produce a clean, editable Word version instead of a broken copy of the design.',
            ],
            'import' => [
                'q' => 'Can I import the CV I already have?',
                'a' => 'Yes, from a PDF or DOCX. We show you everything we extracted before saving, and anything uncertain is marked "Needs review" instead of being guessed.',
            ],
            'ai' => [
                'q' => 'Is AI required?',
                'a' => 'No. You can build a complete resume without it. When you use it, suggestions are based only on the information you provided. It never invents jobs, dates or certificates.',
            ],
            'free' => [
                'q' => 'Is the free plan really free?',
                'a' => 'Yes. You can create resumes with the free ATS templates and download PDFs every month without entering a card.',
            ],
            'privacy' => [
                'q' => 'Who can see my resume?',
                'a' => 'Only you. Files are stored privately, downloads use short-lived secure links, and you can delete your account and data at any time.',
            ],
        ],
    ],

    'final_cta' => [
        'title' => 'Your next role starts with a better resume',
        'body' => 'Create a professional, ATS-friendly resume in English or Arabic today.',
        'note' => 'Free plan available. No credit card required.',
    ],

    'footer' => [
        'about' => 'A bilingual resume builder focused on clarity, ATS compatibility and beautiful design.',
        'product' => 'Product',
        'resources' => 'Resources',
        'account' => 'Account',
        'rights' => 'All rights reserved.',
    ],
];
