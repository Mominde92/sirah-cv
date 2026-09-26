<?php

return [
    'free' => 'Free',
    'per_month' => '/ month',
    'per_year' => '/ year',
    'see_plans' => 'Compare plans',

    'interval' => [
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
    ],

    'page' => [
        'title' => 'Plan & billing',
        'current_plan' => 'Current plan',
        'current' => 'Current',
        'trial' => 'Trial',
        'past_due' => 'Payment overdue',
        'ends_on' => 'Ends on :date',
        'change_plan' => 'Change plan',
        'cancel' => 'Cancel subscription',
        'cancel_confirm' => 'Cancel your subscription? You keep your plan until the end of the paid period.',
        'resume' => 'Keep my subscription',
        'canceled' => 'Your subscription was cancelled. You keep access until :date.',
        'resumed' => 'Your subscription will continue.',
        'thanks' => 'Thank you! Your plan will be active as soon as the payment is confirmed (usually a few seconds).',
        'checkout_failed' => 'We could not start the checkout. Please try again or contact support.',
        'action_failed' => 'That did not work. Please try again or contact support.',
        'manual_notice' => 'Online payment is not enabled yet. To upgrade, contact us and your plan will be activated manually.',
        'contact_sales' => 'Online payment is not enabled yet. Please contact us to upgrade your plan.',
        'payments' => 'Payment history',
        'date' => 'Date',
        'amount' => 'Amount',
        'status' => 'Status',
        'payment_status' => ['pending' => 'Pending', 'succeeded' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'],
    ],

    'limit_reached' => 'You have reached your plan limit for :feature (:limit). Upgrade to continue.',
    'feature_unavailable' => ':feature is not included in your current plan. Upgrade to unlock it.',

    'lines' => [
        'resumes' => [
            'name' => 'Resumes',
            'limited' => 'Resumes: up to :count',
            'unlimited' => 'Unlimited resumes',
        ],
        'premium_templates' => [
            'enabled' => 'All templates, including premium designs',
            'disabled' => 'Free ATS templates',
        ],
        'pdf_export' => [
            'name' => 'PDF download',
            'limited' => 'PDF downloads: :count per month',
            'unlimited' => 'Unlimited PDF downloads',
        ],
        'docx_export' => [
            'name' => 'Word (DOCX) download',
            'limited' => 'Word downloads: :count per month',
            'unlimited' => 'Unlimited Word (DOCX) downloads',
        ],
        'ai_requests' => [
            'name' => 'AI writing assistant',
            'limited' => 'AI writing assistant: :count requests per month',
            'unlimited' => 'Unlimited AI writing assistant',
        ],
        'cv_import' => [
            'name' => 'Import an existing CV (PDF / DOCX)',
            'limited' => 'CV import: :count per month',
            'unlimited' => 'Unlimited CV import',
        ],
        'design_reference' => [
            'name' => 'Match a design you upload',
            'limited' => 'Design matching: :count per month',
            'unlimited' => 'Unlimited design matching',
        ],
        'advanced_customization' => [
            'name' => 'Advanced design customization',
        ],
        'version_history' => [
            'name' => 'Version history',
        ],
    ],
];
