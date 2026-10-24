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
| Live preview (Hostinger Business, shared) | https://grey-quail-750388.hostingersite.com/ (temporary domain) |
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
php artisan site:export --url=https://grey-quail-750388.hostingersite.com
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
- [ ] 1b Register + email verification
- [ ] 1c Log in / log out / forgot password
- [ ] 1d Roles: own dashboard per role, admin two-step login
- [ ] 1e Parent adds children
- [ ] 1f Run on Hostinger as real Laravel (owner creates MySQL DB + email account, click-by-click)
Public pages still use `DemoTutors`/`DemoSubjects` until Step 3.

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
