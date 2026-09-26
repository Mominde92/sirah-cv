# Deployment guide: cPanel, email, mobile app

## 1. Email (Resend)

1. Create an account at resend.com → **Domains → Add domain** and add the DNS records it shows (SPF, DKIM) in
   cPanel → **Zone Editor**. Wait until the domain shows "Verified".
2. **API Keys → Create** (permission: "Sending access").
3. In the server `.env`:
   ```
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtps
   MAIL_HOST=smtp.resend.com
   MAIL_PORT=465
   MAIL_USERNAME=resend
   MAIL_PASSWORD=re_your_api_key
   MAIL_FROM_ADDRESS="no-reply@your-domain.com"
   ```
4. Test: `php artisan config:clear && php artisan cv:mail-test you@example.com`

Brevo works the same way (smtp-relay.brevo.com, port 587, `MAIL_SCHEME=null`).

## 2. cPanel requirements

* **PHP 8.3** (cPanel → MultiPHP Manager) with extensions: `gd`, `intl`, `zip`, `mbstring`, `pdo_mysql`, `fileinfo`,
  `bcmath`, `xml`, `dom` (cPanel → Select PHP Version → Extensions).
* In **MultiPHP INI Editor**: `memory_limit = 256M`, `upload_max_filesize = 12M`, `post_max_size = 14M`, `max_execution_time = 120`.
* **Terminal** (or SSH) makes deployment easy. Without it, use the one-time cron trick in step 6.

## 3. Build the release (on your PC)

```powershell
powershell -ExecutionPolicy Bypass -File deploy\build-release.ps1
```

This creates `release\sirah-release-YYYYMMDD-HHMM.zip` with `vendor/` and the built assets included, so the
server needs neither Composer nor Node.

## 4. Upload

1. cPanel → **File Manager** → create the folder `/home/USER/sirah` (outside `public_html`).
2. Upload the zip there and **Extract**.
3. Point the domain at the app's `public` folder. Either:
   * cPanel → **Domains** → your domain → **Document Root** = `sirah/public` (best), or
   * rename `public_html` to `public_html_old` and, in Terminal: `ln -s ~/sirah/public ~/public_html`

Only `public/` is reachable from the web. User files stay in `storage/app/private`.

## 5. Database

1. cPanel → **MySQL Databases**: create a database (e.g. `USER_sirah`) and a user with a strong password, then
   **Add User To Database** with **ALL PRIVILEGES**.
2. Copy `.env.production.example` to `.env` inside `/home/USER/sirah` and fill in:
   `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, and the mail settings.

## 6. First run

With Terminal:
```bash
cd ~/sirah
bash deploy/post-deploy.sh
```
It generates `APP_KEY`, runs migrations, seeds plans, templates and the admin account, links storage and caches the config.

Without Terminal: cPanel → **Cron Jobs**, add a one-time job running every minute, wait two minutes, then delete it:
```
cd /home/USER/sirah && /usr/local/bin/php artisan key:generate --force && /usr/local/bin/php artisan migrate --force --seed && /usr/local/bin/php artisan storage:link && /usr/local/bin/php artisan optimize
```
(Check the PHP path in cPanel; it is often `/usr/local/bin/php` or `/opt/cpanel/ea-php83/root/usr/bin/php`.)

## 7. Cron (required)

cPanel → **Cron Jobs** → every minute:
```
* * * * * cd /home/USER/sirah && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```
Because `.env` has `QUEUE_RUN_FROM_SCHEDULER=true`, this also processes PDF/Word exports, imports and design
analysis every minute (users wait a few seconds at most). It also prunes expired files and expires subscriptions.

## 8. Updating

Build a new zip, upload and extract it over the old files (keep `.env` and `storage/`), then run
`bash deploy/post-deploy.sh` (or the cron trick with `migrate --force && optimize`).

## 9. Backups

* cPanel → **Backup** (or JetBackup): the database plus `/home/USER/sirah/storage/app` (uploads, photos, originals).
* Everything else can be rebuilt from Git.

---

## 10. Mobile app (Android and iOS)

The app in `mobile/` is a Capacitor shell that opens the live site. It is identical to the web app and updates the
moment the site is updated; you only re-publish the app when changing its icon, name or native settings.

**Set your domain** (once), from `mobile/`:
```bash
npm install
set SIRAH_URL=https://your-domain.com      # PowerShell: $env:SIRAH_URL="https://your-domain.com"
npx cap sync
```

**Android (on Windows):**
1. Install Android Studio (it brings the Android SDK).
2. `npx cap open android` → Build → **Generate Signed Bundle / APK** → Android App Bundle (for Google Play) or APK (for testing).
3. Keep the signing key (`.jks`) safe. You need the same key for every future update.

**iOS:** requires a Mac with Xcode and an Apple Developer account ($99/year):
`npx cap sync ios && npx cap open ios` → set the signing team → Product → Archive → upload to App Store Connect.

**Testing against your PC** (Android emulator): `SIRAH_URL=http://10.0.2.2:8000` then `npx cap sync android`.

Notes:
* The app ID is `com.sirah.cvbuilder` (`mobile/capacitor.config.ts`). Change it before the first store upload, because it cannot change afterwards.
* Icons and splash screens are generated from `mobile/assets/icon-only.png` (`npm run assets`).
* PDF/Word downloads open in the system browser, which saves the file. Upload fields (CV import, photo) use the phone's file picker.
* The site is also installable as a PWA (manifest and icons are in `public/`).
