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
- **Live (temporary domain):** https://cornflowerblue-tarsier-769699.hostingersite.com/ runs this Laravel app on
  Hostinger shared hosting with MySQL. Emails are not sent yet (they start when the real domain and its mailbox
  are set up), so sign-up cannot be completed on the live site yet.
- **Public pages (design, dummy data):** Home, Find a Tutor, Tutor Profile, Book Free Demo, Subjects,
  How it works, Pricing, Become a Tutor, About, Contact, FAQs, Terms / Privacy / Refund (drafts), 404/403/500.
- **Accounts (working):** register as parent / student / tutor, email verification, log in with email or
  phone, log out, password reset, role dashboards (`/dashboard`), admin two-step login (email code),
  parents manage their children.
- **Tutor application (working):** tutors fill in subjects, levels, rate, availability, education, photo,
  CNIC and degree; save a draft and submit for review. Documents are stored privately.
- **SEO and security:** unique titles and descriptions, canonical, Open Graph, schema, sitemap.xml,
  robots.txt (site is noindex until launch), security headers with a strict CSP (no inline JavaScript),
  CSRF, validation and rate limits on every form, private document storage.
- **Tutor approval (working):** admins review applications, view documents privately, record the subject
  test and interview (Google Meet link), then approve, reject or ask for documents; tutors are emailed.
- **Find a Tutor and profiles (working):** search, filters (subject, level, price, rating), sort and pages
  read approved tutors from the database. Until launch, 8 sample tutors (flagged demo) fill the pages;
  `php artisan demo:remove` deletes them.
- **Tutor profile editing (working):** approved tutors update rate, bio and times live; new subjects,
  education, photo or documents wait for admin approval.
- **Free demo requests (working):** logged-in parents and students request a free demo for a child or
  themselves; requests show on their dashboard and can be cancelled while waiting.
- **Tutors answer demo requests (working):** accept with a time and Google Meet link, or decline with
  a reason; the family sees the answer on their dashboard.
- **Join demo class (working):** the family gets a "Join class" button shortly before the class; the tutor
  then marks it done or "student did not join".
- **Next:** admin view of all demos, classes (Google Meet links),
  reviews, admin tools.
  Payments, WhatsApp and SMS OTP come later, behind replaceable interfaces.
- A static HTML export of the public pages lives on branch `static-site` (old preview).

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
