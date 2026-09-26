<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Verifies the mail configuration: php artisan cv:mail-test you@example.com
 */
final class SendTestMail extends Command
{
    protected $signature = 'cv:mail-test {to : Recipient email address}';

    protected $description = 'Send a test email with the current MAIL_* configuration';

    public function handle(): int
    {
        $to = (string) $this->argument('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');

            return self::INVALID;
        }

        $this->line('Mailer: '.config('mail.default').' · From: '.config('mail.from.address'));

        try {
            Mail::raw('This is a test email from '.config('app.name').'. If you can read this, email delivery works.', function ($message) use ($to): void {
                $message->to($to)->subject(config('app.name').' — test email');
            });
        } catch (Throwable $exception) {
            $this->error('Sending failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Test email sent to {$to}.");

        return self::SUCCESS;
    }
}
