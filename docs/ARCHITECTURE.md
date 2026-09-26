# CV Builder SaaS — Architecture

> Phase 1 deliverable. It describes the target architecture for every phase.
> Later phases implement it; they should not redesign it.

## 1. Goals and constraints

| Concern | Decision |
|---|---|
| Runtime | PHP 8.3+, Laravel 12, MySQL 8 (SQLite for tests) |
| UI | Blade + Livewire 3 + Alpine.js + Tailwind CSS 4 (Vite) |
| Async | Laravel Queues (database driver in dev, Redis in production) |
| Files | Laravel Storage: `local` (private) for user files, `public` only for non-sensitive assets such as template previews |
| PDF | `PdfRenderer` contract with two drivers, selected by `PDF_DRIVER`. **mPDF** (pure PHP) is the default, because the current production host cannot install Chromium. It has native Arabic shaping, RTL and font embedding, which Dompdf lacks. **Browsershot** (headless Chromium) is ready to switch on when the host allows it. Both drivers receive the same HTML from the Template Engine (see §3 "Rendering constraints"). |
| DOCX | PhpOffice/PHPWord, built from the resume *data model* (not by converting HTML) |
| i18n | `en` + `ar` at launch; adding a locale means adding a `lang/<code>` folder and one entry in `config/cv.php` |
| AI | Optional. `AiProvider` contract with Anthropic, OpenAI and Gemini drivers. When no provider is configured, AI features are hidden and nothing else breaks. |

## 2. Layered structure

```
HTTP (Controllers / Livewire)  ->  thin: authorize, validate, call a service, return a response
        |
Form Requests / Policies        ->  validation + authorization
        |
Services (application layer)    ->  business rules and orchestration; the only place that changes state
        |
Models (Eloquent) + Query scopes
        |
Jobs / Events / Listeners       ->  slow or side-effect work (exports, parsing, analysis, notifications)
```

Repositories are **not** added by default. Eloquent already is a data mapper with query scopes, and a
repository layer over it would duplicate the API. A repository is introduced only where a query is
complex and reused (for example admin reporting: `App\Repositories\Reports\UsageReportRepository`).

### Directory layout

```
app/
  Contracts/              Interfaces: AiProvider, PdfRenderer, DocumentTextExtractor, ResumeParser, DesignAnalyzer
  Data/                   Immutable DTOs (readonly classes): ResumeDocument, StylingOptions, ParsedResume, DesignAnalysis, JobMatchResult...
  Enums/                  Backed enums used as model casts (statuses, section types, features...)
  Events/  Listeners/     ResumeExported, ResumeImported, SubscriptionChanged...
  Exceptions/             Domain exceptions (FeatureLimitReached, TemplateNotAvailable...)
  Http/
    Controllers/          App/*, Admin/*, Auth/*, Api/V1/*
    Middleware/           SetLocale, EnsureUserIsAdmin, EnsureAccountIsActive
    Requests/             One Form Request per write action
    Resources/            API Resources (JSON for the editor and the REST API)
  Jobs/                   GenerateResumeExport, ParseResumeImport, AnalyzeDesignReference, PruneExpiredFiles
  Livewire/               Editor/*, Dashboard/*, Admin/*
  Models/
  Notifications/          Localized mail notifications (VerifyEmail, ResetPassword, ExportReady...)
  Policies/               ResumePolicy, ResumeExportPolicy, ResumeImportPolicy, DesignReferencePolicy, TemplatePolicy
  Services/
    Resume/               ResumeService, ResumeSectionService, ResumeDuplicator, ResumeVersioning
    Templates/            TemplateRegistry, TemplateRenderer, StyleResolver, SectionRegistry  (Template Engine)
    Export/               ExportService, Pdf/{MpdfRenderer, BrowsershotRenderer}, Docx/DocxBuilder + per-section writers
    Import/               ImportService, Extractors/{PdfTextExtractor, DocxTextExtractor}, HeuristicResumeParser, AiResumeParser
    Design/               DesignReferenceService, ColorPaletteExtractor, LayoutHeuristics, TemplateMatcher
    Ai/                   AiManager (driver manager), Providers/{Anthropic, OpenAi, Gemini}, Prompts/*, AiUsageRecorder
    JobMatching/          JobMatchService, KeywordExtractor
    Billing/              SubscriptionService, PlanService, PaymentGateway contract (+ Manual driver; Stripe later)
    Usage/                FeatureGate (can the user do X?), UsageMeter (count X)
resources/views/
  resume-templates/
    layouts/              single-column, sidebar-left, sidebar-right, split-header   (structural skeletons)
    sections/             one partial per section type, shared by all templates
    headers/              header variants: classic, centered, banner, split, compact
    templates/<slug>/     OPTIONAL per-template overrides (a section or header partial)
lang/{en,ar}/             UI strings, validation, emails, section names
```

## 3. Template Engine

The editor never knows which template is active. Templates are **data + small reusable views**:

```
Resume ──> ResumeDocument (DTO: personal + ordered visible sections + styling)
             │
ResumeTemplate (DB row: layout key + configuration JSON)
             │
StyleResolver:  template.configuration.defaults  ⊕  resume.styling (user overrides, clamped to allowed ranges)
             │        └─> CSS custom properties (--cv-primary, --cv-font-body, --cv-section-gap ...)
TemplateRenderer
   ├─ layout view      (resume-templates/layouts/{layout})
   ├─ header view      (templates/{slug}/header  ??  headers/{configuration.header.style})
   └─ section views    (templates/{slug}/sections/{type}  ??  sections/{type})
             │
         HTML string  ──>  Live preview (iframe)   ──>  PdfRenderer (A4)   ──>  print
ResumeDocument ───────────────────────────────────────>  DocxBuilder (Word)
```

Lookup rule: a per-template override view wins, otherwise the shared partial is used. So:

* **Template 9** that only restyles means one DB row (seeder or admin form) with a new `configuration`. No PHP, no Blade.
* **Template 10** with a new header means that row plus `templates/template-10/header.blade.php`.
* **Template 11** with a new structure means one new layout view. The editor and the exporters are untouched.

### Rendering constraints (one HTML for preview, mPDF and Chromium)

The PDF must match the preview whichever driver is active, so template CSS is written for the subset that
mPDF renders reliably. Anything outside it would look right in the browser and wrong in the PDF.

* Columns (sidebar layouts) use a two-cell `<table>` layout wrapper, **not** flexbox or grid. mPDF does not
  support flex/grid. ATS templates are single-column, so they never use a layout table.
* Page size and margins come from `@page` / mPDF config (A4). Page breaks use `page-break-inside: avoid` on entries.
* Colours, borders, padding and web fonts (TTF embedded) are allowed. No CSS variables *inside* the PDF HTML:
  `StyleResolver` inlines the resolved values when rendering for export, and the preview uses CSS variables for
  instant updates.
* Arabic: `dir="rtl"` on the document, an Arabic font from the allowed list (Cairo, Tajawal, IBM Plex Sans Arabic,
  Noto Naskh Arabic), and mPDF `autoScriptToLang` / `autoLangToFont` for mixed Arabic and English lines.
* Each template has a visual regression test that renders a fixture resume with both drivers once Chromium is available.

### `resume_templates.configuration` schema (validated by `TemplateConfiguration` DTO)

```json
{
  "layout": "sidebar-left",
  "header": { "style": "banner", "show_photo": true, "alignment": "start" },
  "columns": { "sidebar_width": 32, "sidebar_sections": ["skills", "languages", "certifications"] },
  "defaults": {
    "font_body": "Inter", "font_heading": "Inter", "font_arabic": "Cairo",
    "font_size": 10.5, "heading_scale": 1.35, "line_height": 1.45,
    "colors": { "primary": "#1F3A5F", "secondary": "#4B5563", "text": "#111827", "accent": "#2563EB", "background": "#FFFFFF" },
    "page_margin_mm": 16, "section_spacing": 14, "item_spacing": 8,
    "section_title_style": "underline", "date_format": "MMM YYYY"
  },
  "allowed": {
    "fonts": ["Inter", "Roboto", "Lato", "Source Serif 4", "Merriweather"],
    "fonts_arabic": ["Cairo", "Tajawal", "IBM Plex Sans Arabic", "Noto Naskh Arabic"],
    "font_size": [9, 12], "line_height": [1.2, 1.8], "page_margin_mm": [10, 25],
    "layouts": ["single-column", "sidebar-left"], "photo": true
  },
  "ats": { "single_column_text_flow": true, "icons_decorative_only": true, "uses_tables": false }
}
```

`allowed` stops a user from turning an **ATS Safe** template into something that no longer parses. For
example, ATS templates only allow `single-column` and never render text inside images or SVG.

### ATS classification (no invented percentages)

| Status | Meaning shown to the user |
|---|---|
| `ats_safe` | Single column, real selectable text, standard headings ("Experience", "Education"...), no tables, icons or graphics carrying information. Designed to be read correctly by common ATS parsers. |
| `ats_optimized` | Everything above, plus conservative typography and spacing tuned for parsing, with contact details in the body text rather than a header/footer. |
| `creative` | Visual layouts (columns, sidebars, colour blocks). Text is still real text, but some ATS systems may read the order of multi-column content incorrectly. Recommended for direct sharing, not portal uploads. |

The UI explains these rules. It never claims "98% ATS score".

### Starter templates (8)

| Slug | Category | ATS status | Layout |
|---|---|---|---|
| classic-ats | ats | ats_optimized | single-column, classic header |
| modern-ats | ats | ats_safe | single-column, accent rule header |
| executive-ats | ats | ats_safe | single-column, serif, centered header |
| minimal-ats | ats | ats_optimized | single-column, compact header |
| professional-ats | ats | ats_safe | single-column, split header |
| modern-professional | creative | creative | sidebar-left |
| elegant | creative | creative | sidebar-right, serif |
| designer | creative | creative | split-header + sidebar-left, colour banner |

## 4. Resume data model

A resume is a **document**. Its data is split so that each part is stored in the form that fits it:

* `resumes` — metadata you filter and sort by (owner, title, template, language, status, timestamps), plus
  * `data` JSON — personal information block (name, headline, contacts, links, photo reference);
  * `styling` JSON — the user's overrides of the template defaults (the same keys as `configuration.defaults`).
* `resume_sections` — one row per section. Ordering, visibility and per-section autosave are row
  operations, so drag & drop only rewrites `position` values and does not rewrite the whole document.
  Each row has an `items` JSON array (the entries of that section). Entries are never queried individually,
  are always loaded together and have a type-specific shape, so JSON is the right fit here.
* `resume_versions` — immutable snapshots (data + sections + styling + template) for "restore previous version".

Optimistic locking: `resumes.version` increments on every save. The editor sends the version it loaded,
and a mismatch (another tab saved in the meantime) returns 409 instead of silently overwriting.

### Section item shapes (validated per type by Form Requests)

| Type | Item fields |
|---|---|
| summary | `content` |
| experience | `position, employer, location, start_date, end_date, is_current, description, highlights[]` |
| education | `degree, field, institution, location, start_date, end_date, grade, description` |
| skills | `name, level (0–5, optional), group` |
| languages | `name, proficiency (native, fluent, professional, intermediate, basic)` |
| certifications | `name, issuer, issued_at, expires_at, credential_id, url` |
| projects | `name, role, url, start_date, end_date, description, highlights[]` |
| achievements / awards | `title, issuer, date, description` |
| volunteer | `role, organization, location, start_date, end_date, description` |
| publications | `title, publisher, date, url, description` |
| courses | `name, institution, date, description` |
| references | `name, position, company, email, phone, relationship` (or `available_on_request`) |
| custom | `title, subtitle, date, url, description` |

Every item carries a client-generated `id` (UUID) so drag & drop and Livewire diffing stay stable.

## 5. SaaS: plans, limits, usage

* `plans` — Free / Pro / Premium seeded; admins can add more. Prices are stored as integer minor units.
* `plan_features` — one row per `(plan, feature)`: `is_enabled` + `limit_value` (`NULL` = unlimited).
  Every limit is editable from the admin panel. Nothing is hard-coded in the code.
* `App\Enums\Feature` lists the feature keys and says whether each one is **counted** (e.g. `resumes`: count
  of live rows) or **metered monthly** (e.g. `pdf_export`, `ai_requests`: `feature_usages` counters).
* `FeatureGate::authorize($user, Feature::PdfExport)` is the single entry point. It throws
  `FeatureLimitReached`, which is rendered as an upgrade prompt. Controllers never compare numbers.
* `subscriptions` keeps the history. `users.subscription_id` points at the current one. A user with no active
  subscription falls back to the plan flagged `is_default` (Free).
* Payments go through a `PaymentGateway` contract. A `manual` driver (admin-granted plans) ships first, and Stripe
  or Paddle drivers plug in later without schema changes (`provider`, `provider_*_id` columns already exist).

## 6. Async pipelines

| Flow | HTTP request does | Job does | User sees |
|---|---|---|---|
| Export PDF/DOCX | gate check, create `resume_exports` row (queued) | render → store on private disk → mark completed | polling / Livewire event → download via **signed temporary URL** |
| Import CV | validate & store file, create `resume_imports` row | extract text → parse → `parsed_data` + `review_flags` | review screen: confirmed fields plus a "Needs review" list; nothing is saved to a resume until the user confirms |
| Design reference | validate & store image/PDF | palette extraction, layout heuristics (+ AI vision if enabled) → `analysis`, `suggested_template_id`, `generated_styling` | suggested template + "apply this style" |
| Cleanup | — | scheduled `PruneExpiredFiles` deletes expired export files and abandoned uploads | — |

Small single-page ATS PDFs *may* render synchronously when the queue driver is `sync`. The code path is the same.

### Import parsing rules

* The original file and the full extracted text are always kept (`resume_imports.path`, `extracted_text`).
* The parser only fills a field when it finds evidence in the text. Anything ambiguous goes to `review_flags`
  (`needs_review`) with the source snippet, and is never guessed.

### Design reference rules

* Only structure and style are extracted: layout, columns, palette, typography class (serif/sans), spacing, header style and section order.
* Logos, photos, brand marks and text content of the reference are never copied into the generated design.

## 7. AI integration

```php
interface AiProvider {
    public function complete(AiRequest $request): AiResponse; // text in, text/JSON out, token usage
    public function supportsVision(): bool;
}
```

* `AiManager` resolves the driver from `config('ai.default')` (`anthropic` | `openai` | `gemini`).
* Feature services (`SummaryGenerator`, `BulletRewriter`, `JobMatcher`...) depend on the contract only.
* Every prompt includes a grounding rule: *use only facts present in the supplied resume, never invent employers,
  dates, degrees or certifications*. The job matcher returns found / missing keywords computed deterministically
  first (`KeywordExtractor`), and AI only phrases suggestions.
* Each call writes one `ai_usage_logs` row (tokens, latency, status) and increments the `ai_requests` meter.

## 8. Security

* Every resume-owned model is resolved through its **UUID** (`uuid` column) in routes, so IDs cannot be enumerated.
  Policies check `user_id` ownership and admins can only view.
* Uploads: `mimetypes` + extension + max size validation, files renamed to random names, stored on the **private** disk,
  served only through controllers or signed URLs. The PDF parser runs inside a queued job with a timeout.
* Rich text in descriptions is sanitized to a small allow-list (`b, i, u, ul, ol, li, a[href], br, p`) on write, and
  Blade escapes everything else.
* PDF drivers only render HTML produced by our own templates. mPDF has remote file fetching restricted to our font and asset
  paths, and Chromium (when enabled) runs with JavaScript disabled.
* Rate limiters: `login`, `register`, `password-reset`, `ai` (per user), `exports` (per user), `uploads` (per user).
* Admin routes: `auth + verified + can:access-admin` middleware, plus an audit trail via events.

## 9. Performance

* Indexes on every foreign key and on the list/sort paths (`resumes(user_id, updated_at)`, `resume_sections(resume_id, position)`...).
* Dashboard lists eager-load `template` and never load section JSON. Thumbnails are generated asynchronously after save (debounced job).
* Templates and plans (with features) are cached (`Cache::rememberForever` + invalidation on admin save).
* Style changes in the editor update CSS variables client-side without a round-trip. Content edits are debounced
  Livewire updates that re-render only the preview fragment.

## 10. Database schema

```
users ─┬─< subscriptions >── plans ──< plan_features
       │        ▲
       │        └── users.subscription_id (current)
       ├─< payments
       ├─< resumes >── resume_templates
       │      ├─< resume_sections
       │      ├─< resume_versions
       │      ├─< resume_exports
       │      └─< job_matches
       ├─< resume_imports      (→ resumes, nullable)
       ├─< design_references   (→ resume_templates suggested, → resumes applied)
       ├─< ai_usage_logs
       └─< feature_usages
settings (key/value, admin)
```

| Table | Purpose | Key indexes / constraints |
|---|---|---|
| users | accounts, locale, role, suspension | unique email, index role, FK subscription_id → subscriptions (null on delete) |
| plans | sellable plans | unique slug, (is_active, sort_order) |
| plan_features | per-plan limits | unique (plan_id, feature) |
| subscriptions | subscription history | (user_id, status), (status, current_period_ends_at), provider id |
| payments | financial records (kept if user deleted) | unique (provider, provider_payment_id), (status, paid_at) |
| resume_templates | template catalogue | unique slug, (is_active, category, sort_order) |
| resumes | resume documents | unique uuid, (user_id, updated_at), (user_id, status), FK template restrict |
| resume_sections | ordered sections + items JSON | (resume_id, position) |
| resume_versions | snapshots | unique (resume_id, version) |
| resume_exports | PDF/DOCX jobs and download stats | unique uuid, (user_id, created_at), (status, expires_at), (format, created_at) |
| resume_imports | uploaded CVs + extracted/parsed data | unique uuid, (user_id, created_at), status |
| design_references | uploaded designs + analysis | unique uuid, (user_id, created_at), status |
| job_matches | job-description analyses | unique uuid, (resume_id, created_at) |
| ai_usage_logs | per-call AI accounting | (user_id, created_at), (feature, created_at), (provider, created_at) |
| feature_usages | monthly counters | unique (user_id, feature, period_start) |
| settings | admin key/value config | unique (group, key) |

JSON columns are used only for document-shaped or schema-flexible data: `resumes.data/styling`,
`resume_sections.items/settings`, `resume_templates.configuration/supported_languages`, translatable `name/description`,
parser output and analysis results, gateway metadata. Everything that is filtered, joined, counted or
constrained is a real column.

## 11. Phase plan

| Phase | Scope |
|---|---|
| 1 | Architecture, schema, migrations, enums, models ✅ |
| 2 | Auth (register/login/verify/reset/profile), locales + RTL, layouts, landing page, plan seeding, FeatureGate ✅ |
| 3 | Resume CRUD, dashboard, duplicate/rename/delete, policies ✅ |
| 4 | Template engine + 8 templates + template gallery ✅ |
| 5 | Livewire editor: sections, drag & drop, design panel, live preview, autosave, versions ✅ |
| 6 | PDF export (mPDF default + Browsershot driver, Arabic fonts, queue, signed downloads) ✅ |
| 7 | DOCX export (PHPWord) ✅ |
| 8 | Import PDF/DOCX + review screen, design reference upload & analysis ✅ |
| 9 | AI providers + features + job matching ✅ |
| 10 | Subscriptions, pricing, payments gateway contract, usage metering UI ✅ |
| 11 | Admin dashboard ✅ |
| 12 | Test suite hardening, security review, performance pass, README/deployment ✅ |

## 12. Implementation decisions (made while building)

* **Columns use floats, never tables.** mPDF shrinks a table row that is taller than a page onto one page, so
  sidebar layouts are two floated blocks (verified: the main column continues on page 2+). Full-height sidebar
  colour comes from an `@page` background image in mPDF, a CSS gradient in the preview, and the root background in
  Chromium print (`TemplateRenderer::MODE_PRINT`).
* **One render path.** The live preview re-renders server-side on every save (Livewire) and is written into the
  iframe without reloading. Rendered values are literals (no CSS variables), so preview and PDF share the same CSS.
* **Fonts.** Static TTFs in `public/fonts` are used by both the preview (`@font-face`) and mPDF (`fontdata` with
  `useOTL`). Amiri and Noto Naskh were rejected: mPDF cannot apply their GPOS tables. The Arabic serif is XB Riyaz
  (bundled with mPDF). Word exports map to fonts every Word installation has (Calibri, Cambria, Arial, Times New Roman).
* **Descriptions are plain text + bullet lists**, never HTML: nothing to sanitise, ATS-friendly, and trivially
  exported to DOCX. Blade escapes everything.
* **Styling stores only overrides** (the difference from the template defaults), so template design updates still
  reach resumes that never changed them. Advanced keys (`StyleSettings::ADVANCED`) require the Premium feature.
* **Quota is checked before work and counted after success** (exports, imports, AI), so failures never cost the user.
* **AI is optional and grounded.** Providers: Anthropic (official PHP SDK, `claude-opus-5`, server-side refusal
  fallback), OpenAI and Gemini over HTTP. Admins can switch AI off globally (Settings).