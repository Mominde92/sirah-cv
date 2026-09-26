# Sirah: CV Builder SaaS

Bilingual (English / Arabic, LTR / RTL) SaaS for building ATS-friendly resumes with a live editor, 8 templates,
PDF and Word export, CV import, design matching, optional AI writing help, subscriptions and an admin panel.
Architecture and design decisions: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Stack

PHP 8.3+, Laravel 12, MySQL 8, Blade + Livewire 4 (bundles Alpine.js), Tailwind CSS 4 (Vite), Laravel Fortify,
mPDF (PDF, pure PHP) with an optional Browsershot driver, PHPWord (DOCX), smalot/pdfparser (import),
anthropic-ai/sdk (optional AI), spatie/laravel-translatable, blade-heroicons.

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# Create the MySQL database named in DB_DATABASE (utf8mb4), set ADMIN_EMAIL / ADMIN_PASSWORD in .env, then:
php artisan migrate --seed          # plans, 8 templates, admin user
php artisan db:seed --class=DemoSeeder   # optional: demo@sirah.test / Demo-pass-2026 on Premium
php artisan storage:link
npm run build
```

> On Laragon the default `php` may be 7.4. Use the PHP 8.3 binary explicitly, or switch the Laragon PHP version.

## Running

Three processes are needed:

```bash
php artisan serve                                     # web app  → http://127.0.0.1:8000
php artisan queue:work --queue=exports,imports,default # PDF/DOCX exports, CV imports, design analysis
php artisan schedule:work                             # hourly: prune expired files, expire subscriptions
```

Emails (verification, password reset) use the `log` mailer by default. Links are written to `storage/logs/laravel.log`.

## Tests

```bash
php artisan test
```

126 tests cover authentication, resume CRUD, authorization, the editor, the template engine, PDF and Word generation,
import, design matching, job matching, AI (fake provider), billing webhooks, plan limits and admin permissions.
Tests use in-memory SQLite and a synchronous queue.

## Optional integrations

| Feature | Configuration |
|---|---|
| AI writing help | `AI_PROVIDER=anthropic` + `ANTHROPIC_API_KEY` (or `openai` / `gemini` with their keys and models). Empty = AI buttons hidden, everything else works. Admins can also switch AI off in Admin → Settings. |
| Online payments | `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, plus Stripe price IDs per plan (Admin → Plans). Webhook URL: `/webhooks/stripe` (events: `checkout.session.completed`, `customer.subscription.*`, `invoice.*`). Without Stripe, admins grant plans manually. |
| Chromium PDFs | `PDF_DRIVER=browsershot` once Node, Puppeteer and Chromium are installed on the server (`BROWSERSHOT_*` variables for paths). |

## Production checklist

* `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, a real `MAIL_*` configuration.
* `QUEUE_CONNECTION=redis` (or database) with a supervised `queue:work` process, and cron: `* * * * * php artisan schedule:run`.
* `php artisan config:cache route:cache view:cache` and `npm run build` on deploy.
* The private disk (`storage/app/private`) must not be web-accessible. Only `storage/app/public` is linked.
* Add real Terms of Service and Privacy Policy pages (the registration form asks users to accept them).

## Adding a template

A template is a database row: Admin → Templates → New, or add an entry to `TemplateSeeder`. Its configuration picks
a layout, header style, defaults and allowed options. For a new visual element, add an override partial in
`resources/views/resume-templates/templates/{slug}/`. The editor and exporters need no changes.

## Adding a language

1. Add the locale to `config/cv.php` → `locales` (and to `rtl_locales` if it is right-to-left).
2. Copy `lang/en` to `lang/<code>` and translate. Add `lang/<code>.json` for framework email strings.
