<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | UI and resume languages. Adding a language means adding an entry here and a
    | matching lang/<code> directory. RTL locales switch the document direction.
    |
    */

    'locales' => [
        'en' => ['name' => 'English', 'native' => 'English'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية'],
    ],

    'rtl_locales' => ['ar'],

    /*
    |--------------------------------------------------------------------------
    | Resume fonts
    |--------------------------------------------------------------------------
    |
    | Static TTF files in public/fonts, served to the live preview (@font-face)
    | and embedded by mPDF, so the preview and the PDF use identical glyphs.
    | The key is the CSS family name in both renderers (mPDF needs lowercase).
    |
    */

    'fonts' => [
        'ibmplexsans' => ['label' => 'IBM Plex Sans', 'script' => 'latin', 'category' => 'sans', 'files' => ['R' => 'IBMPlexSans-Regular.ttf', 'B' => 'IBMPlexSans-Bold.ttf']],
        'lato' => ['label' => 'Lato', 'script' => 'latin', 'category' => 'sans', 'files' => ['R' => 'Lato-Regular.ttf', 'B' => 'Lato-Bold.ttf', 'I' => 'Lato-Italic.ttf']],
        'ibmplexserif' => ['label' => 'IBM Plex Serif', 'script' => 'latin', 'category' => 'serif', 'files' => ['R' => 'IBMPlexSerif-Regular.ttf', 'B' => 'IBMPlexSerif-Bold.ttf']],
        'ptserif' => ['label' => 'PT Serif', 'script' => 'latin', 'category' => 'serif', 'files' => ['R' => 'PTSerif-Regular.ttf', 'B' => 'PTSerif-Bold.ttf']],
        'ibmplexsansarabic' => ['label' => 'IBM Plex Sans Arabic', 'script' => 'arabic', 'category' => 'sans', 'files' => ['R' => 'IBMPlexSansArabic-Regular.ttf', 'B' => 'IBMPlexSansArabic-Bold.ttf']],
        'tajawal' => ['label' => 'Tajawal', 'script' => 'arabic', 'category' => 'sans', 'files' => ['R' => 'Tajawal-Regular.ttf', 'B' => 'Tajawal-Bold.ttf']],
        // Arabic serif bundled with mPDF (fully supported by its OpenType engine).
        'xbriyaz' => ['label' => 'XB Riyaz', 'script' => 'arabic', 'category' => 'serif', 'files' => ['R' => 'XBRiyaz-Regular.ttf', 'B' => 'XBRiyaz-Bold.ttf']],
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF rendering
    |--------------------------------------------------------------------------
    |
    | "mpdf" works on any PHP host. "browsershot" needs Node + Chromium and is
    | switched on when the host allows it. Both render the same template HTML.
    |
    */

    'pdf' => [
        'driver' => env('PDF_DRIVER', 'mpdf'),
        'browsershot' => [
            'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
            'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
            'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
            'no_sandbox' => (bool) env('BROWSERSHOT_NO_SANDBOX', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing page
    |--------------------------------------------------------------------------
    */

    'pricing' => [
        // Plan slug shown with the "Most popular" highlight.
        'highlighted_plan' => env('CV_HIGHLIGHTED_PLAN', 'pro'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Files
    |--------------------------------------------------------------------------
    */

    'disks' => [
        // User uploads and generated documents. Never a public disk.
        'private' => env('CV_PRIVATE_DISK', 'local'),
        // Template previews and other non-sensitive assets.
        'public' => env('CV_PUBLIC_DISK', 'public'),
    ],

    'uploads' => [
        'max_kilobytes' => (int) env('CV_UPLOAD_MAX_KB', 10240),
        'import_mimes' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'design_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Initial administrator (used by AdminUserSeeder only)
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue on shared hosting
    |--------------------------------------------------------------------------
    |
    | cPanel cannot keep a long-running `queue:work` process. When enabled, the
    | scheduler (cron every minute) runs the worker until the queue is empty.
    |
    */

    'queue_via_scheduler' => (bool) env('QUEUE_RUN_FROM_SCHEDULER', false),

    'exports' => [
        // Generated files are deleted after this many hours by the cleanup job.
        'retention_hours' => (int) env('CV_EXPORT_RETENTION_HOURS', 24),
        'download_url_ttl_minutes' => (int) env('CV_DOWNLOAD_URL_TTL', 15),
    ],

];
