<?php

return [
    'title' => 'Optimize for a job',
    'heading' => 'Compare your resume with a job description',
    'subtitle' => 'Paste the job ad to see which of its keywords ":resume" already covers and which are missing.',
    'submit' => 'Analyse',
    'untitled' => 'Job description',
    'history' => 'Previous analyses',
    'back_to_resume' => 'Resume being analysed',
    'honesty' => 'Only add a missing keyword if it truthfully describes your experience. We never suggest claiming skills, jobs or certificates you do not have.',

    'fields' => [
        'job_title' => 'Job title',
        'company' => 'Company',
        'job_description' => 'Job description',
        'placeholder' => 'Paste the full job description here…',
    ],

    'result' => [
        'title' => 'Job match',
        'for_resume' => 'Resume: :resume',
        'again' => 'New analysis',
        'edit' => 'Edit resume',
        'coverage' => 'Keyword coverage',
        'coverage_note' => ':found of :total important terms from the job description appear in your resume. This measures wording overlap, not your chances of being hired.',
        'suggestions' => 'Suggested improvements',
        'keywords_found' => 'Keywords found',
        'keywords_missing' => 'Missing keywords',
        'relevant_skills' => 'Your relevant skills',
        'none' => 'None',
    ],

    'rules' => [
        'add_summary' => 'Add a professional summary that states your most relevant experience for this role.',
        'add_skills' => 'Add a skills section listing the tools and competencies you actually use.',
        'missing' => 'If they genuinely reflect your experience, mention these terms in your summary, experience or skills: :keywords.',
        'mirror' => 'Use the same wording as the job description for skills you have (for example the exact tool or certification name), so applicant tracking systems recognise them.',
    ],
];
