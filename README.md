# your tutor

Online tutoring marketplace, Pakistan first (KSA later). Laravel 13 + MySQL. Brand name is a setting
(`APP_NAME`), so it can change without code changes.

**New here (or a new AI chat)? Start with [`docs/HANDOFF.md`](docs/HANDOFF.md).**

| Document | What it is |
|---|---|
| [`docs/HANDOFF.md`](docs/HANDOFF.md) | Current status, where things are, how the owner works, next steps |
| [`CLAUDE.md`](CLAUDE.md) | Project rules (one step at a time, security, portability) |
| [`docs/PLAN.md`](docs/PLAN.md) | Business plan and phases |
| [`docs/SITEMAP.md`](docs/SITEMAP.md) | All pages |
| [`docs/DEPLOY.md`](docs/DEPLOY.md) | Deploying, launch switches, temporary items to remove |
| [`docs/LAUNCH_CHECKLIST.md`](docs/LAUNCH_CHECKLIST.md) | Must be fully ticked before going live |

## Status (24 Oct 2026)
- **Public pages (design, dummy data):** Home, Find a Tutor, Tutor Profile, Book Free Demo, Subjects,
  How it works, Pricing, Become a Tutor, About, Contact, FAQs, Terms / Privacy / Refund (drafts), 404/403/500.
- **Accounts (working):** register as parent / student / tutor, email verification, log in with email or
  phone, log out, password reset, role dashboards (`/dashboard`), admin two-step login (email code),
  parents manage their children.
- **SEO and security:** unique titles and descriptions, canonical, Open Graph, schema, sitemap.xml,
  robots.txt (site is noindex until launch), security headers with a strict CSP (no inline JavaScript),
  CSRF, validation and rate limits on every form.
- **Not built yet:** tutor application + approval, real tutor search, booking, classes (Google Meet links),
  reviews, admin tools. Payments, WhatsApp and SMS OTP are planned for later, behind replaceable interfaces.
- **Hosting:** Hostinger shared hosting; moving the Laravel app there (Step 1f). A static HTML export of the
  pages lives on branch `static-site`.

## Run locally
```
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # sample accounts: student|parent|tutor|admin@example.test, password "password"
php artisan serve
php artisan test                # must pass before every commit
```
Locally emails are written to `storage/logs/laravel.log` (`MAIL_MAILER=log`).

## Moving to another server or domain
Everything server-specific (URL, database, mail, keys) is in `.env` only. Copy the files, fill in `.env`,
run `php artisan migrate --force`. No code changes. See [`docs/DEPLOY.md`](docs/DEPLOY.md).
