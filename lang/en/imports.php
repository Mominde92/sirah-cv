<?php

return [
    'title' => 'Import a CV',
    'heading' => 'Start from the CV you already have',
    'subtitle' => 'Upload a PDF or Word file. We extract your details, show you everything before saving, and mark anything uncertain for your review.',
    'drop' => 'Drop your CV here or click to choose a file',
    'formats' => 'PDF or DOCX, up to :size MB. Scanned images without text cannot be read.',
    'privacy' => 'Your file is stored privately and is never shared. You can delete it at any time.',
    'submit' => 'Import my CV',
    'recent' => 'Recent imports',
    'created' => 'Your resume was created from the import. Review the design and details.',
    'deleted' => 'The import and its file were deleted.',
    'completed' => 'This import has already been turned into a resume.',

    'how' => [
        'title' => 'How it works',
        'upload' => 'Upload your existing PDF or Word CV.',
        'extract' => 'We read the text and organise it into sections.',
        'review' => 'You check every field. Uncertain items are marked "Needs review".',
        'edit' => 'Pick a template and continue in the editor.',
    ],

    'status' => [
        'pending' => 'Queued',
        'processing' => 'Reading',
        'needs_review' => 'Needs review',
        'completed' => 'Imported',
        'failed' => 'Failed',
    ],

    'processing' => [
        'title' => 'Reading your CV…',
        'body' => 'We are extracting the text from ":file". This page refreshes automatically.',
    ],

    'failed' => [
        'title' => 'We could not read this file',
        'retry' => 'Try another file',
        'manual' => 'Start from a template',
    ],

    'review' => [
        'title' => 'Review your imported CV',
        'intro_title' => 'Nothing is saved until you confirm.',
        'intro_body' => 'Check each field below and correct anything that was placed in the wrong spot. We never fill in information that was not in your file.',
        'needs_review' => 'Needs review (:count)',
        'check' => 'Check',
        'include' => 'Include',
        'create' => 'Create the resume',
        'submit' => 'Create resume',
        'original_text' => 'Original text',
        'original_hint' => 'Everything we read from your file, kept unchanged.',
        'discard' => 'Discard this import and delete the file',
    ],

    'flags' => [
        'name_position' => 'The name was not on the first line. Please confirm it.',
        'name_not_found' => 'We could not identify your name.',
        'unassigned' => 'This line from the header was not placed in any field.',
        'unknown_heading' => 'This heading was not recognised; its text was kept as a custom section.',
        'long_skill' => 'This skill looked too long and was skipped.',
        'missing_dates' => 'No dates were found for this entry.',
        'check_order' => 'Check that the title and the organisation are in the right fields.',
        'month_assumed' => 'Only a year was found; the month was set to January.',
        'ai_uncertain' => 'The assistant was not sure about this field.',
        'unplaced' => 'This text could not be placed in a section.',
    ],

    'errors' => [
        'unreadable' => 'This file could not be opened. Make sure it is a valid PDF or DOCX that is not password-protected.',
        'no_text' => 'No text was found in this file. It may be a scanned image; please upload a PDF with selectable text or a Word file.',
    ],
];
