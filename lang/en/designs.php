<?php

return [
    'title' => 'Match a design',
    'heading' => 'Upload a resume design you like',
    'subtitle' => 'We analyse its layout, columns, colours, header and spacing, then build an original design from our closest template.',
    'drop' => 'Drop an image or PDF here, or click to choose',
    'formats' => 'JPG, PNG or PDF, up to :size MB.',
    'submit' => 'Analyse design',
    'recent' => 'Recent designs',
    'processing' => 'Analysing the design…',
    'failed' => 'We could not analyse this file',
    'originality' => 'We only reuse structure and style. Logos, photos, brand marks and text from the reference are never copied into your resume.',
    'resume_title' => 'Resume (new design)',
    'applied' => 'The design was applied. You can fine-tune fonts, colours and spacing in the design panel.',
    'deleted' => 'The design upload was deleted.',
    'delete' => 'Delete this upload',

    'detects' => [
        'title' => 'What we detect',
        'layout' => 'Single column or sidebar, and its side',
        'colors' => 'Primary, accent and text colours',
        'header' => 'Header style, such as a colour banner',
        'typography' => 'Serif or sans-serif typography',
        'spacing' => 'Overall density and spacing',
    ],

    'result' => [
        'title' => 'Design analysis',
        'reference' => 'Your reference',
        'layout' => 'Layout',
        'header' => 'Header',
        'typography' => 'Typography',
        'density' => 'Density',
        'palette' => 'Detected colours',
        'unknown' => 'Not detected',
        'typography_values' => ['serif' => 'Serif', 'sans' => 'Sans-serif', 'unknown' => 'Not detected'],
        'density_values' => ['compact' => 'Compact', 'balanced' => 'Balanced', 'airy' => 'Airy'],
        'colors' => ['primary' => 'Primary', 'accent' => 'Accent', 'text' => 'Text', 'background' => 'Background'],
        'pdf_note' => 'For PDFs we can read fonts and section order, but not colours or columns. Upload an image (JPG/PNG) of the page for a fuller analysis.',
        'suggestion' => 'Suggested design',
        'suggestion_body' => 'Based on ":template", with colours and typography inspired by your reference.',
        'closeness' => 'Structural match :percent%',
    ],

    'apply' => [
        'new' => 'Create a new resume',
        'new_hint' => 'Start a fresh resume with this design.',
        'existing' => 'Apply to an existing resume',
        'existing_hint' => 'Keep the content, change the design.',
        'choose_resume' => 'Resume',
        'submit' => 'Use this design',
    ],

    'errors' => [
        'unreadable' => 'This file could not be analysed. Please try a clear JPG, PNG or PDF of a single resume page.',
    ],
];
