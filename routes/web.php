<?php

use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignReferenceController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\JobMatchController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TemplateController;
use App\Livewire\Editor\ResumeEditor;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', LandingController::class)->name('home');

Route::post('/locale/{locale}', LocaleController::class)
    ->middleware('throttle:20,1')
    ->name('locale.update');

Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
Route::get('/templates/{template:slug}/preview', [PreviewController::class, 'template'])
    ->middleware('throttle:120,1')
    ->name('templates.preview');

// Signed, short-lived download links work without a session so the mobile app can hand them
// to the system browser / download manager. Signed-in users are still checked against the owner.
Route::get('/exports/{export}/download', [ExportController::class, 'download'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('exports.download');

Route::post('/webhooks/stripe', StripeWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.stripe');

/*
|--------------------------------------------------------------------------
| Signed-in, verified users
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Resumes
    Route::get('/resumes', [ResumeController::class, 'index'])->name('resumes.index');
    Route::get('/resumes/create', [ResumeController::class, 'create'])->name('resumes.create');
    Route::post('/resumes', [ResumeController::class, 'store'])->middleware('throttle:30,1')->name('resumes.store');
    Route::patch('/resumes/{resume}', [ResumeController::class, 'update'])->name('resumes.update');
    Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy'])->name('resumes.destroy');
    Route::post('/resumes/{resume}/duplicate', [ResumeController::class, 'duplicate'])->middleware('throttle:30,1')->name('resumes.duplicate');
    Route::get('/resumes/{resume}/edit', ResumeEditor::class)->name('resumes.edit');
    Route::get('/resumes/{resume}/preview', [PreviewController::class, 'resume'])->name('resumes.preview');
    Route::get('/resumes/{resume}/photo', [PreviewController::class, 'photo'])->name('resumes.photo');

    // Job description matching
    Route::get('/resumes/{resume}/optimize', [JobMatchController::class, 'create'])->name('resumes.optimize');
    Route::post('/resumes/{resume}/optimize', [JobMatchController::class, 'store'])->middleware('throttle:20,1')->name('resumes.optimize.store');
    Route::get('/job-matches/{jobMatch}', [JobMatchController::class, 'show'])->name('job-matches.show');

    // Exports (PDF / DOCX)
    Route::post('/resumes/{resume}/exports', [ExportController::class, 'store'])->middleware('throttle:exports')->name('resumes.exports.store');
    Route::get('/exports/{export}', [ExportController::class, 'show'])->name('exports.show');

    // Import an existing CV
    Route::get('/imports/create', [ImportController::class, 'create'])->name('imports.create');
    Route::post('/imports', [ImportController::class, 'store'])->middleware('throttle:uploads')->name('imports.store');
    Route::get('/imports/{import}', [ImportController::class, 'show'])->name('imports.show');
    Route::post('/imports/{import}/confirm', [ImportController::class, 'confirm'])->name('imports.confirm');
    Route::delete('/imports/{import}', [ImportController::class, 'destroy'])->name('imports.destroy');

    // Match an uploaded design
    Route::get('/designs/create', [DesignReferenceController::class, 'create'])->name('designs.create');
    Route::post('/designs', [DesignReferenceController::class, 'store'])->middleware('throttle:uploads')->name('designs.store');
    Route::get('/designs/{design}', [DesignReferenceController::class, 'show'])->name('designs.show');
    Route::get('/designs/{design}/preview', [DesignReferenceController::class, 'preview'])->name('designs.preview');
    Route::get('/designs/{design}/file', [DesignReferenceController::class, 'file'])->name('designs.file');
    Route::post('/designs/{design}/apply', [DesignReferenceController::class, 'apply'])->name('designs.apply');
    Route::delete('/designs/{design}', [DesignReferenceController::class, 'destroy'])->name('designs.destroy');

    // Billing
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->middleware('throttle:10,1')->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->middleware('throttle:10,1')->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->middleware('throttle:10,1')->name('billing.resume');

    // Account
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::delete('/account', [ProfileController::class, 'destroy'])
        ->middleware('throttle:6,1')
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', Admin\OverviewController::class)->name('overview');

    Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [Admin\UserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/suspend', [Admin\UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/unsuspend', [Admin\UserController::class, 'unsuspend'])->name('users.unsuspend');
    Route::post('/users/{user}/plan', [Admin\UserController::class, 'grantPlan'])->name('users.plan');
    Route::patch('/users/{user}/role', [Admin\UserController::class, 'updateRole'])->name('users.role');

    Route::get('/resumes', Admin\ResumeController::class)->name('resumes.index');

    Route::get('/templates', [Admin\TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [Admin\TemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [Admin\TemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{template}/edit', [Admin\TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{template}', [Admin\TemplateController::class, 'update'])->name('templates.update');
    Route::post('/templates/{template}/toggle', [Admin\TemplateController::class, 'toggle'])->name('templates.toggle');

    Route::get('/plans', [Admin\PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [Admin\PlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [Admin\PlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [Admin\PlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [Admin\PlanController::class, 'update'])->name('plans.update');

    Route::get('/subscriptions', Admin\SubscriptionController::class)->name('subscriptions.index');
    Route::get('/payments', Admin\PaymentController::class)->name('payments.index');
    Route::get('/ai-usage', Admin\AiUsageController::class)->name('ai-usage.index');
    Route::get('/downloads', Admin\DownloadController::class)->name('downloads.index');
    Route::get('/reports', Admin\ReportController::class)->name('reports.index');

    Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
});
