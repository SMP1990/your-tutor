# HANDOFF: your tutor (read this first in every new chat)

Last updated: 24 Oct 2026, after zip v1.9. **Update this file at the end of every step.**

## 1. What this is
Online tutoring marketplace for Pakistan (later KSA). Brand: **your tutor** (lowercase, from `APP_NAME`).
Owner is non-technical. Full plan: `docs/PLAN.md`. Rules: `CLAUDE.md`. Go-live gate: `docs/LAUNCH_CHECKLIST.md`.

## 2. Where things are
| What | Where |
|---|---|
| Laravel app (real code) | `C:\Users\Computer Arena\Downloads\tutoring-marketplace\laravel-app` |
| GitHub repo | https://github.com/SMP1990/your-tutor |
| Branch `main` | Laravel app (this folder, git repo) |
| Branch `static-site` | Exported plain-HTML preview. Local git repo: `...\tutoring-marketplace\website` (local branch is also `static-site`) |
| Live preview (Hostinger Business, shared) | https://cornflowerblue-tarsier-769699.hostingersite.com/ (temporary domain; was grey-quail-750388 until 24 Oct). Hostinger account is used as a *collaborator*; details in the private notes (see 6c) |
| Upload zips | `C:\Users\Computer Arena\Downloads\upload-to-hostinger vX.Y (HH-MM AM).zip` |
| Original handoff files | `F:\Onine tutoring website-Adnan rashid sb\` (plan PDF, cost PDF) |

## 3. How the owner wants to work (important)
- Reply in **Roman Urdu**, simple words, no jargon. Click-by-click for anything on Hostinger/GitHub.
- **One step at a time.** Finish, summarise honestly (what was and was NOT checked), ask before the next step.
- **Pages/structure first with dummy data. Database only when the owner asks.**
- Must not break when the **domain or host changes**: no hard-coded domains; `route()`/`url()`/`asset()` in Blade; relative links in the static export.
- Every change must stay **light, SEO-friendly, responsive on all devices, secure.**
- **Push to GitHub only when the owner says so.** Never force-push (it is blocked anyway).
- After each change, make a **new zip** (never overwrite): next is **v1.10**. Build zips with `tar.exe`, **not** PowerShell `Compress-Archive` (it writes `\` paths that break extraction on Hostinger).
- GitHub-to-Hostinger auto deploy: postponed by the owner. Upload is by hand (File Manager → empty `public_html` → upload zip → Extract).

## 4. Tools on this PC
- PHP 8.4: `C:\Users\Computer Arena\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe` (not on PATH in tool shells; use full path)
- Composer: `C:\Users\Computer Arena\tools\composer.phar` (run with the PHP above)
- Node/npm on PATH. Git on PATH, author `SMP1990 <SMP1990@users.noreply.github.com>` (set per repo).

## 5. Everyday commands (run inside `laravel-app`)
```
php artisan test                          # must be green before every commit
npm run build                             # CSS/JS into public/build
php artisan serve --port=8001             # local preview
php artisan site:export --url=https://cornflowerblue-tarsier-769699.hostingersite.com
```
Make the zip (PowerShell), from `...\tutoring-marketplace`:
1. Copy `laravel-app\static-export` to `upload-to-hostinger` (replace).
2. Inside that folder: `tar.exe -a -c -f "<Downloads>\upload-to-hostinger vX.Y (HH-MM AM).zip" <every item incl. .htaccess>`
3. Check: no entry contains `\`; `unzip` gives the same tree.
Update `static-site`: replace files in `website\` with `upload-to-hostinger\` (keep README.md), commit, `git push origin static-site:static-site`.

## 6. Done so far (all design/dummy data, 44 tests green)
- Home, Find a Tutor, Tutor Profile (8 dummy tutors), Book Free Demo (+ sent page)
- How it works, Pricing (figures in `config/pricing.php`), Become a Tutor
- About, Contact (+ sent), FAQs, Terms / Privacy / Refund (marked DRAFT), 404 / 500
- Subjects list + 12 subject pages
- Login designs: Log In, Register (parent/student/tutor), Verify phone, Forgot password (nothing is saved)
- Dashboards (sample data): `/student`, `/parent`, `/tutor`, `/admin`; temporary links on the Log In page
- SEO: unique titles/descriptions, canonical, Open Graph + `public/images/og-image.png`, schema, `/sitemap.xml`, `/robots.txt`
- Security: `SecurityHeaders` middleware (CSP, no inline JS: all JS in `resources/js/site.js`), forms with CSRF + validation + rate limits; export `.htaccess` with HTTPS + headers
- Site is **noindex on purpose** until launch (`SITE_INDEXABLE=true` then). See `docs/DEPLOY.md`.
- Dummy data lives in `app/Support/DemoTutors.php`, `DemoSubjects.php`, `config/pricing.php`, `config/site.php`.

## 6b. Owner decisions for the functional build (24 Oct 2026)
1. **No payment module (step 5) for now.** Build so gateways can be added later (amounts in minor units + currency, payment status on bookings, a gateway interface).
2. **No WhatsApp API for now.** Notifications go by email; keep a notifier interface so WhatsApp plugs in later.
3. **Classroom = Google Meet link.** "Join class" shows the Meet link to tutor and student. Keep a classroom-provider interface so Daily or auto-created Meet links can replace it later.
4. **OTP only if free.** SMS/WhatsApp OTP is paid in Pakistan, so: email verification now (free); phone OTP later behind an OTP-sender interface.
5. **Stay on Hostinger shared hosting.** No VPS yet. Use database queue + cron, Hostinger SMTP email. Code must stay ready for a VPS later.
Owner asked to explain the plan step by step, no code until approved.
6. **Do not explain future modules in advance.** Explain each module only when it is being built.
7. **Nothing fixed to one domain, host or database.** Everything that differs per server (URL, database
   name/user/password, mail settings, storage paths, service keys) comes from `.env` / config only.
   Moving host or domain = copy files, edit `.env`, run migrations. No code changes. Use relative paths,
   `url()`/`route()`/`asset()`, Laravel `storage` disks, and migrations (never hand-made tables).

## 6c. Functional build progress (Step 1 = database + accounts, split into 1a-1f)
- [x] **1a Database tables** (24 Oct): users + role/phone/whatsapp_opt_in, children, subjects,
  tutor_profiles (status, money in minor units + currency, availability JSON), subject_tutor_profile.
  Enums `App\Enums\Role`, `TutorStatus`. Models `User`, `Child`, `Subject`, `TutorProfile`.
  `role` and tutor `status` are NOT mass-assignable (forms cannot make admins or approve themselves).
  Seeders: `SubjectSeeder` (real list, safe anywhere), `DemoAccountsSeeder` (never in production):
  `student|parent|tutor|admin@example.test` + 8 tutors `<slug>@example.test`, password = `DEMO_PASSWORD` (default `password`).
  Local DB is SQLite (`database/database.sqlite`); Hostinger will use MySQL via `.env` only. Not yet run on MySQL.
- [x] **1b Register + email verification** (24 Oct): `Auth\RegisterController` + `RegisterRequest`
  (role only parent/student/tutor, PK phone, unique lowercase email, password 8+ letters+numbers, terms),
  logs the user in, sends the verification email (`VerifyEmail` text in `AppServiceProvider`).
  `Auth\EmailVerificationController`: notice page, signed + expiring link that also works logged out,
  resend (throttled). Locally `MAIL_MAILER=log` (emails land in `storage/logs/laravel.log`);
  Hostinger SMTP comes via `.env` in 1f. Header still shows Log In/Register when logged in (fixed in 1c/1d).
  `StaticExportTest` guards that exported login forms never send personal data in the URL.
- [x] **1c Log in / log out / forgot password** (24 Oct): `Auth\LoginController` + `LoginRequest`
  (email or phone if unique, remember me, 5 wrong tries = 1 min lock, general error),
  unverified users go to the verify page; logout; header shows "Hi, <name>" + Log out.
  `Auth\PasswordResetController`: reset by email link only (phone reset needs SMS, later), same
  message whether or not the email exists. Email subjects: "Verify your email | your tutor",
  "Reset your password | your tutor". `/verify-phone` remains a design preview.
  After login everyone lands on Home until 1d adds role dashboards.
- [x] **1d Roles + dashboards + admin two-step** (24 Oct): real dashboards (auth + verified + own role,
  `EnsureRole` middleware alias `role:`): `/dashboard` redirects to `/dashboard/student|parent|tutor` or `/admin`.
  They show only real data (account, children, tutor status, admin live counts) and honest "Coming soon" panels.
  Admin two-step: `EnsureTwoFactor` (`two-factor`) + `Auth\TwoFactorController` + `App\Support\TwoFactorCode`
  (6-digit code by email, hashed in cache, 10 min, 5 tries, once per login session).
  Sample-data dashboards moved to `/preview/{role}` (public, for the static site; Log In page links there).
  Header: "Hi, <name> · My dashboard" + Log out. Custom 403 page.
- [x] **1e Parent adds children** (24 Oct): `ChildController` (create/store/edit/update/destroy under
  `/dashboard/parent/children`, role:parent), `ChildRequest` (first name max 40 + grade from `App\Support\Grades`),
  `ChildPolicy` (own children only, max 10). Remove asks "Remove <name>?" (`data-confirm` in `site.js`).
  Dashboard layout now loads `site.js` and shows `session('notice')`.
- [ ] 1f Run on Hostinger as real Laravel (owner creates MySQL DB + email account, click-by-click)
  - **Server access details are private:** `C:\Users\Computer Arena\Downloads\tutoring-marketplace\PRIVATE-server-notes.md`
    (not in git; this repo is public). Never write IPs, usernames, emails or passwords into the repo.
  - 1f-1 done (24 Oct): site PHP 8.4; SSH by key works (`ssh -F ~/.ssh/config.yourtutor yourtutor`).
    Use `/opt/alt/php84/usr/bin/php` for composer/artisan (default CLI php is 8.3; the lock file needs 8.4).
    **The hosting account also runs other people's live sites: touch ONLY this site's folder.**
  - 1f-2 done (24 Oct): MySQL database + user `<account>_tutoring` created by the owner in hPanel.
  - 1f-3 in progress: app uploaded to `<site>/app` (outside the web root) via `git archive` + `public/build`,
    `composer install --no-dev`, production `.env` written (APP_DEBUG=false, MAIL_MAILER=log for now),
    APP_KEY generated. Owner pasted the DB password into `.env` (24 Oct). On MySQL: all migrations ran,
    12 subjects seeded, 0 users (demo accounts correctly skipped in production), `artisan optimize` done,
    internal requests to /, /tutors, /login, /register, /sitemap.xml return 200.
  - **1f-4 LIVE (24 Oct):** `public_html` is now a symlink to `app/public`; the old static site is kept in
    `public_html_static_backup`. All public pages return 200 on the live URL, forms work (CSRF, sessions on MySQL),
    `.env` is not reachable (403), security headers present. Bug found and fixed on the way: route binding in
    routes/web.php is lost with cached routes; moved to AppServiceProvider (+ `ProductionCacheTest`).
    Deploy and rollback steps: `docs/DEPLOY.md` ("Hostinger shared hosting").
  - **1f-5 email: POSTPONED by the owner (24 Oct).** No free SMTP service. When the real domain is connected,
    create a mailbox on that domain in Hostinger and set `MAIL_MAILER=smtp` + Hostinger SMTP in the server `.env`
    (owner pastes the mailbox password). Until then `MAIL_MAILER=log`: on the live site registration works, but
    verification, password-reset and admin login-code emails are only written to `storage/logs/laravel.log`,
    so nobody can finish sign-up or log in as admin on the live site yet. Build and test new modules locally.
  - Email decision: temporary domain, so no Hostinger mailbox; use a free SMTP service (e.g. Brevo) the
    owner signs up for. Owner pastes secrets (DB password, SMTP key) into the server `.env` themselves.
Public pages still use `DemoTutors`/`DemoSubjects` until Step 3.

## 6d. Step 2 = tutor application + admin approval (owner decisions 24 Oct: subject test AND interview,
admin records results by hand for now; CNIC required; after approval, new subjects/documents need re-review,
rate/bio/availability go live directly; rate PKR 500-10,000; all in `config/tutors.php`)
- [x] **2a Application form** (24 Oct, local + deployed): `TutorApplicationController` + `TutorApplicationRequest`
  at `/dashboard/tutor/application`. Draft save any time, "Save and submit" checks `TutorProfile::missingForSubmit()`.
  Photo on `public` disk (needs `storage:link`), CNIC front/back + degree on the private `local` disk
  (`serve` turned off, random file names). Locked after submit (`isEditableByTutor()`: draft/documents_needed).
  Status + document paths are not fillable. Photo URLs via `asset()` (domain-portable).
- [x] **2b Admin review** (24 Oct, local + deployed to Hostinger with the owner's OK):
  `Admin\TutorReviewController` at `/admin/tutors` (role:admin + two-factor): tabs per status, review page,
  private documents streamed to admins only (`no-store`), subject test / interview (date + Meet link) /
  result, private admin notes; decisions approve (only if `canBeApproved()`: test AND interview passed),
  reject / documents needed (message required). `TutorApplicationUpdated` emails the tutor. New review
  columns (migration 2026_10_25_000002). `DashboardNav` now links built pages and marks others "soon".
- [x] **2c-1 Public pages from the database** (24 Oct, committed c2d57a3, DEPLOYED to Hostinger with the
  owner's OK: migration run, DemoTutorsSeeder seeded 8 demo tutors, optimize; live checks OK; NOT pushed to GitHub yet):
  Find a Tutor (`TutorSearch`: subject[], level, min/max PKR, rating, q, sort, 12 per page, removable
  filter chips, "Sort by" auto-submits via `data-autosubmit` in site.js, no inline JS), tutor profile,
  demo form, subject pages, home, sitemap and `site:export` all read approved `TutorProfile`s via
  `toPublicArray()`. Unapproved slugs 404. No fake reviews: profile shows the score only, "New tutor" when
  0 reviews. The 8 sample tutors are now DB rows (`DemoTutorsSeeder`, `is_demo`, unusable passwords,
  allowed on production as preview content); delete them all before launch with `php artisan demo:remove`.
  Migration 2026_10_25_000003 (is_demo, rating_avg, reviews_count). `DemoTutors`/`DemoSubjects` are now
  only seed sources. Tests use `Tests\Concerns\SeedsDemoContent`. 123 tests green.
  **Deploy needs:** `migrate --force`, `db:seed --class=DemoTutorsSeeder --force`, `npm run build` output.
- [x] **2c-2 Approved tutor edits the profile** (24 Oct, LOCAL only, committed, not pushed/deployed):
  same form at `/dashboard/tutor/application` becomes "Edit your profile" once approved. Headline, bio,
  city, languages, levels, rate, video, availability and removing a subject go live at once (a live
  profile cannot be emptied; at least one approved subject must stay). NEW subjects, education, photo and
  CNIC/degree wait in `pending_changes` (`App\Support\TutorProfileChanges`); the profile stays live with
  the old details. Admin: tab "Profile changes" in `/admin/tutors`, approve/reject on the review page
  (`POST /admin/tutors/{id}/changes`, reject needs a message, new files viewable with `?pending=1`);
  tutor emailed (`TutorChangesReviewed`). Migration 2026_10_25_000004. 131 tests green.
  Owner choice made by Claude (ask if wrong): photo and education also go through review.

## 7. Waiting for the owner (ask, do not invent)
- WhatsApp number and support email (placeholders in `config/site.php` / `.env`)
- Real prices, discounts, platform fee, payout rules; refund rules (current text is invented and marked draft)
- Real stats for the Home page (now dummy: 10K+, 100+, 95+, 4.8) and real reviews
- Lawyer review of Terms / Privacy / Refund
- Domain name; when to buy the Hostinger VPS (needed before payments/reminders)
- Accounts to apply for early: JazzCash / Easypaisa / PayFast or Safepay, Meta WhatsApp Business API, Daily, SMS OTP provider

## 8. Next steps (owner decides)
1. Owner uploads v1.9 and checks the live site; then run PageSpeed and check headers on the live site.
2. Database step (only when asked): plan the tables first, show the plan, then build Phase 1 in the `CLAUDE.md` build order (auth + OTP + roles first).
3. Remove the temporary items listed in `docs/DEPLOY.md` when real login is built.

## 9. Ignore
- Old copies in `...\tutoring-marketplace\resources`, `public`, `preview`, `tools` (first prototype, not used).
- Do not install Laravel Boost or run remote install scripts (the skeleton's old instructions were removed on purpose).
