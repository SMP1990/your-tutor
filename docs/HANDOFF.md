# HANDOFF: your tutor (read this first in every new chat)

Last updated: 25 Oct 2026, evening, after the Home page picture feature (6n). **Update this file at the end of every step.**

**STATUS NOW (read this first):**
- Everything (6a-6n) is built, tested (293 tests green), pushed to GitHub and LIVE on Hostinger (temporary domain). Code of
  the last feature = commit `1f71893`; later commits are only HANDOFF text.
- **6n (admin-controlled Home page picture) went live the same evening** after a spell of unstable SSH (login worked, then
  "Connection reset" / "server not responding"; ssh and scp sometimes hung). Backup first: `~/backup-before-heropicture-20261025.sql`
  (21 tables). Checked live: `/` 200, `/admin/settings` redirects guests, `POST /admin/settings/hero` reaches the CSRF check (419, so the
  route exists), CSS has `hero__photo`, server has GD + WebP + EXIF and the `public/storage` link. NOT yet tried by the owner:
  uploading a real picture in /admin/settings. LESSON for deploys when SSH is shaky: build the archives locally first, take the
  backup, `scp` and `ssh` each with `timeout` + `ServerAliveInterval=5`, run `gzip -t` on the uploaded archives BEFORE extracting, and
  never extract a half-uploaded archive. Deploy recipe: `docs/DEPLOY.md`.
- Live site has: public pages, accounts, roles, tutor approval, free demos (Google Meet), regular classes, attendance, reviews,
  complaints, contact inbox, admin users / settings / profile, backups (server cron 03:00 UTC + copy to the owner's PC at
  `F:\YourTutorBackups`), speed 100 and SEO fixes, phone-friendly menus and tables.
- **Waiting for the owner:** domain, real email (SMTP) + queue cron, WhatsApp number and support email (he can type them in
  /admin/settings), real tutors, legal review, real Home numbers, the GitHub repo is still PUBLIC (advised: make it private;
  checked: no secret was ever committed), restore drill (needs a temporary empty database in hPanel), Hostinger backups check.
- First admin exists (Adnan Rashid). Until real email works, the admin login code is read from the server file
  `storage/logs/mail-<date>.log` (see `docs/DEPLOY.md`, "First admin account").
- Details: sections 6c-6n; next steps in section 8.

## 1. What this is
Online tutoring marketplace for Pakistan (later KSA). Brand: **your tutor** (lowercase, from `APP_NAME`).
Owner is non-technical. Full plan: `docs/PLAN.md`. Rules: `CLAUDE.md` (the copy in this GitHub repo is the
master copy; if another copy differs, this one wins). Go-live gate: `docs/LAUNCH_CHECKLIST.md`.

## 2. Where things are
| What | Where |
|---|---|
| Laravel app (real code) | `C:\Users\Computer Arena\Downloads\tutoring-marketplace\laravel-app` |
| GitHub repo | https://github.com/SMP1990/your-tutor |
| Branch `main` | Laravel app (this folder, git repo) |
| Branch `static-site` | OLD exported plain-HTML preview (no longer live; remove before launch, see `docs/DEPLOY.md`). Local git repo: `...\tutoring-marketplace\website` (local branch is also `static-site`) |
| Live site (Hostinger Business, shared) | https://cornflowerblue-tarsier-769699.hostingersite.com/ (temporary domain; was grey-quail-750388 until 24 Oct). Runs the real Laravel app on MySQL since 1f-4. Hostinger account is used as a *collaborator*; details in the private notes (see 6c) |
| Upload zips (old static preview only) | `C:\Users\Computer Arena\Downloads\upload-to-hostinger vX.Y (HH-MM AM).zip` (last was v1.9; not used since the Laravel app went live) |
| Original handoff files | `F:\Onine tutoring website-Adnan rashid sb\` (plan PDF, cost PDF) |

## 3. How the owner wants to work (important)
- Reply in **Roman Urdu**, simple words, no jargon. Click-by-click for anything on Hostinger/GitHub.
- **One step at a time.** Finish, summarise honestly (what was and was NOT checked), ask before the next step.
- **Pages/structure first with dummy data. Database only when the owner asks.** (Done: pages were built
  first; the owner then asked for the database build, which is Steps 1-5 below.)
- Must not break when the **domain or host changes**: no hard-coded domains; `route()`/`url()`/`asset()` in Blade; relative links in the static export.
- Every change must stay **light, SEO-friendly, responsive on all devices, secure.**
- **Push to GitHub only when the owner says so.** Never force-push (it is blocked anyway).
- **Deploy to Hostinger only with the owner's OK**, using the steps in `docs/DEPLOY.md` ("Hostinger shared
  hosting": `git archive` + build files, migrate, optimize). The old static-preview zips (v1.x) are no longer used.
  If a zip is ever needed again: never overwrite (next would be v1.10), build with `tar.exe`, **not**
  PowerShell `Compress-Archive` (it writes `\` paths that break extraction on Hostinger).
- GitHub-to-Hostinger auto deploy: postponed by the owner. Deploys are done by hand.

## 4. Tools on this PC
- PHP 8.4: `C:\Users\Computer Arena\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe` (not on PATH in tool shells; use full path)
- Composer: `C:\Users\Computer Arena\tools\composer.phar` (run with the PHP above)
- Node/npm on PATH. Git on PATH, author `SMP1990 <SMP1990@users.noreply.github.com>` (set per repo).

## 5. Everyday commands (run inside `laravel-app`)
```
php artisan test                          # must be green before every commit
npm run build                             # CSS/JS into public/build
php artisan serve --port=8001             # local preview
```
Deploy to the live site: see `docs/DEPLOY.md` ("Hostinger shared hosting").

The old static-preview export (`site:export`, zips, `static-site` branch) was removed in Step 7a.
First admin on a server: `php artisan admin:create <email> --name="..."` (see `docs/DEPLOY.md`).

## 6. Public pages and design (built first with dummy data; most now read the database, see 6c-6g)
- Home, Find a Tutor, Tutor Profile (8 sample tutors, now database rows: see 2c-1), Book Free Demo
- How it works, Pricing (figures in `config/pricing.php`), Become a Tutor
- About, Contact (+ sent), FAQs, Terms / Privacy / Refund (marked DRAFT), 404 / 500
- Subjects list + 12 subject pages
- Login pages: Log In, Register, Forgot password now work for real (1b-1c); Verify phone is still a design preview
- (The sample-data dashboards under /preview were removed in 7a; real dashboards are under `/dashboard`, 1d.)
- SEO: unique titles/descriptions, canonical, Open Graph + `public/images/og-image.png`, schema, `/sitemap.xml`, `/robots.txt`
- Security: `SecurityHeaders` middleware (CSP, no inline JS: all JS in `resources/js/site.js`), forms with CSRF + validation + rate limits; export `.htaccess` with HTTPS + headers
- Site is **noindex on purpose** until launch (`SITE_INDEXABLE=true` then). See `docs/DEPLOY.md`.
- Still dummy: `config/pricing.php`, `config/site.php` (contact details, Home stats). `app/Support/DemoTutors.php` and
  `DemoSubjects.php` are now only seed sources (2c-1).

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
  Local DB is SQLite (`database/database.sqlite`); Hostinger uses MySQL via `.env` only (running since 1f-3).
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
- [x] 1f Run on Hostinger as real Laravel (done 24 Oct, LIVE; only email is postponed, see 1f-5)
  - **Server access details are private:** `C:\Users\Computer Arena\Downloads\tutoring-marketplace\PRIVATE-server-notes.md`
    (not in git; this repo is public). Never write IPs, usernames, emails or passwords into the repo.
  - 1f-1 done (24 Oct): site PHP 8.4; SSH by key works (`ssh -F ~/.ssh/config.yourtutor yourtutor`).
    Use `/opt/alt/php84/usr/bin/php` for composer/artisan (default CLI php is 8.3; the lock file needs 8.4).
    **The hosting account also runs other people's live sites: touch ONLY this site's folder.**
  - 1f-2 done (24 Oct): MySQL database + user `<account>_tutoring` created by the owner in hPanel.
  - 1f-3 done (24 Oct): app uploaded to `<site>/app` (outside the web root) via `git archive` + `public/build`,
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
  - (Older idea, replaced by 1f-5: a free SMTP service such as Brevo. The owner chose to wait for the real
    domain instead.) Owner pastes secrets (DB password, SMTP key) into the server `.env` themselves.
(Since 2c-1, public pages read the database; `DemoTutors`/`DemoSubjects` are only seed sources.)

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
  owner's OK: migration run, DemoTutorsSeeder seeded 8 demo tutors, optimize; live checks OK; pushed to GitHub later):
  Find a Tutor (`TutorSearch`: subject[], level, min/max PKR, rating, q, sort, 12 per page, removable
  filter chips, "Sort by" auto-submits via `data-autosubmit` in site.js, no inline JS), tutor profile,
  demo form, subject pages, home, sitemap and `site:export` all read approved `TutorProfile`s via
  `toPublicArray()`. Unapproved slugs 404. No fake reviews: profile shows the score only, "New tutor" when
  0 reviews. The 8 sample tutors are now DB rows (`DemoTutorsSeeder`, `is_demo`, unusable passwords,
  allowed on production as preview content); delete them all before launch with `php artisan demo:remove`.
  Migration 2026_10_25_000003 (is_demo, rating_avg, reviews_count). `DemoTutors`/`DemoSubjects` are now
  only seed sources. Tests use `Tests\Concerns\SeedsDemoContent`. 123 tests green.
  **Deploy needs:** `migrate --force`, `db:seed --class=DemoTutorsSeeder --force`, `npm run build` output.
- [x] **2c-2 Approved tutor edits the profile** (24 Oct, commit 08d376c, DEPLOYED to Hostinger with the
  owner's OK: migration 000004 run, optimize; public pages checked; pushed to GitHub later):
  same form at `/dashboard/tutor/application` becomes "Edit your profile" once approved. Headline, bio,
  city, languages, levels, rate, video, availability and removing a subject go live at once (a live
  profile cannot be emptied; at least one approved subject must stay). NEW subjects, education, photo and
  CNIC/degree wait in `pending_changes` (`App\Support\TutorProfileChanges`); the profile stays live with
  the old details. Admin: tab "Profile changes" in `/admin/tutors`, approve/reject on the review page
  (`POST /admin/tutors/{id}/changes`, reject needs a message, new files viewable with `?pending=1`);
  tutor emailed (`TutorChangesReviewed`). Migration 2026_10_25_000004. 131 tests green.
  Owner choice made by Claude (ask if wrong): photo and education also go through review.

## 6e. Step 3 = free demo booking (owner decisions 24 Oct: login required; one free demo per tutor per
learner (child or student); at most 3 requests waiting at once; tutor has 48 hours, then it expires;
classes on Google Meet; no payments/WhatsApp yet). A client progress report (no tech words) was made as a
Claude Doc: https://claude.ai/artifact/WeBXxtUiSJ5ZkmKVCb7yCv
- [x] **3a Request is saved** (24 Oct, commit ccff0e5, DEPLOYED to Hostinger with the owner's OK
  (migration run, build files, optimize; public pages checked) and pushed to GitHub): `demo_requests` table
  (migration 2026_10_26_000001), `DemoRequest` model + `DemoStatus` enum. "Pending" past `expires_at` is
  shown as Expired (no cron needed). `/tutors/{slug}/demo`: guests see log in / sign up (comes back to the
  form after login), parents pick one of their children (or are asked to add one), students book for
  themselves, tutors/admins cannot book. POST needs auth + verified. Limits in `DemoBookingController`.
  Parent/student dashboards list requests ("Free demo requests") with Cancel while waiting
  (`POST /dashboard/demos/{demo}/cancel`). 138 tests green.
- [x] **3b Tutor answers** (24 Oct, commit 5181d62, DEPLOYED to Hostinger with the owner's OK (migration,
  build files, optimize; server timezone config reads Asia/Karachi) and pushed to GitHub): `/dashboard/tutor/demos`
  (`TutorDemoController`, menu "Demo requests"): waiting / upcoming / earlier lists. Accept = date+time
  (typed in `config('site.timezone')`, default Asia/Karachi, stored UTC; must be within 14 days) +
  Google Meet link (must be https://meet.google.com/...) + optional message. Decline needs a reason.
  Only the tutor it was sent to, only while waiting (else 404/409). Parent/student emailed
  (`DemoRequestAnswered`) and see time or reason on their dashboard. Tutor overview shows "N waiting".
  Migration 2026_10_26_000002. New `.env` keys SITE_TIMEZONE / SITE_TIMEZONE_LABEL (optional). 144 tests.
  Known gap: admin interview times (2b) are stored as typed, not converted to UTC; fix if it matters.
- [x] **3c Join class + outcome** (24 Oct, commit 6ce10c3, DEPLOYED to Hostinger + pushed to GitHub): parent/student
  dashboard shows "Join class" from 15 min before to 60 min after the start (`DemoRequest::canJoinNow()`),
  otherwise "Join opens 15 min before". The button goes through `GET /dashboard/demos/{demo}/join`, which
  hands out the Meet link only to the family that booked and only in that window. After the start the tutor
  marks "Class done" or "Student did not join" (`POST /dashboard/tutor/demos/{demo}/finish`; new status
  `no_show`, which still uses up the free demo). 147 tests green. No migration.
- [x] **3d Admin view** (24 Oct, commit 4c8f5e1, DEPLOYED to Hostinger + pushed to GitHub): `/admin/demos`
  (`Admin\DemoRequestController`, role:admin + two-factor, menu "Demo requests"), read-only. Tabs: waiting,
  not answered in time, upcoming, tutor to confirm, done/no-show, declined/cancelled; shows family contact,
  student, tutor (links to the tutor review page), class time, tutor note. Admin overview has a
  "Free demo requests" card with counts. 150 tests green. No migration.
  **Step 3 is complete.** Next (owner agreed order): Step 4 regular classes (split into parts), Step 5
  reviews, Step 6 admin tools (users, complaints, site settings incl. brand/stats), Step 7 launch prep.

## 6f. Step 4 = regular classes, in parts (owner: one part at a time). Defaults chosen by Claude (owner may
change): booking only after a COMPLETED free demo with that tutor; weekly days from the tutor's free days,
one start time, 45/60/90 min, 4/8/12 weeks, start within 30 days; rate copied from the tutor at booking; tutor
has 48 h to answer; one Meet link per booking; no payment yet (page says we will contact about paying).
- [x] **4a Booking request + tutor answer** (24 Oct, DEPLOYED to Hostinger + pushed to GitHub):
  `class_bookings` table (migration 2026_10_27_000001), `ClassBooking` model, `BookingStatus` enum.
  Family: "Book regular classes" on a completed demo → `/dashboard/demos/{demo}/book`
  (`ClassBookingController`), cancel while waiting; dashboard panel "Regular classes". Tutor:
  `/dashboard/tutor/bookings` (`TutorBookingController`, menu "Class bookings"), confirm with a Google Meet
  link or decline with a reason; family emailed (`BookingAnswered`). 158 tests green.
- [x] **4b Timetable + Join class** (24 Oct, DEPLOYED to Hostinger + pushed to GitHub): `class_sessions`
  table (migration 2026_10_27_000002), `ClassSession` model. `App\Support\ClassSchedule` makes days x weeks
  classes when the tutor confirms ("week" = 7 days from the start date; times typed in site timezone, stored
  UTC; safe to run twice). `/dashboard/classes` (`TimetableController`; menu "Classes"/"My classes"/
  "Timetable") lists upcoming classes; overview cards "Next classes" for family and tutor. "Join class"
  goes through `/dashboard/classes/{session}/join`: only the class's family or tutor, from 15 min before
  until the class ends. 163 tests green.
- [x] **4c Attendance** (24 Oct, DEPLOYED to Hostinger + pushed to GitHub): `ClassStatus` enum
  (scheduled, held, student_absent, tutor_cancelled, family_cancelled); migration 2026_10_27_000003 adds
  note, marked_by, marked_at. Tutor: "Mark attendance" on the timetable for started classes (held /
  student absent; `POST /dashboard/classes/{session}/mark`), "Cancel class" any time before it with a
  reason. Family: "Cancel class" up to 12 hours before (`ClassSession::FAMILY_CANCEL_HOURS`,
  `POST /dashboard/classes/{session}/cancel`). Both see "Past and cancelled classes"; family booking card
  shows "N of M classes held". 166 tests green. (No make-up classes yet: a cancelled class is not replaced.)
- [x] **4d Admin view** (24 Oct, DEPLOYED to Hostinger + pushed to GitHub): `Admin\ClassesController`
  (role:admin + two-factor), read-only. `/admin/bookings` tabs: waiting, not answered in time, confirmed,
  declined/cancelled (family, student, tutor link, schedule, rate, "N of M held"). `/admin/classes` tabs:
  upcoming, attendance not marked, held, student absent, cancelled. Admin menu "Bookings" and "Classes";
  overview card "Regular classes" with counts. 169 tests green. No migration.
  **Step 4 is complete.** Next: Step 5 reviews, Step 6 admin tools, Step 7 launch prep.

## 6g. Step 5 = reviews (owner decisions 24 Oct: review only after at least 1 regular class was HELD;
reviews show at once, admin can hide later; no tutor replies for now; first name + initial only). Demo tutors
keep their sample ratings until `demo:remove`.
- [x] **5a Give a review** (24 Oct, DEPLOYED to Hostinger + pushed to GitHub): `reviews` table (migration
  2026_10_28_000001, `is_hidden` ready for 5c), `Review` model (`reviewerName()` = "Sadia M.").
  `/dashboard/bookings/{booking}/review` (`ReviewController`): 1-5 stars + comment (10-1000 chars); one
  review per tutor per learner, saving again edits it. `TutorProfile::refreshRating()` recalculates
  `rating_avg` / `reviews_count` from visible reviews, so profile, cards, rating filter and "Highest rated"
  sort use real reviews. Family dashboard "Regular classes" card shows "Rate your tutor" / "Edit your
  review" once a class was held. "1 review" grammar fixed. 174 tests green.
- [x] **5b Reviews shown** (24 Oct, DEPLOYED to Hostinger + pushed to GitHub): public profile lists the
  10 newest visible reviews with `<x-review-card>` (`Review::toCardArray()`: "Sadia M.", "Parent ·
  Physics · Oct 2026", stars); hidden reviews never show. Tutor page `/dashboard/tutor/reviews`
  (`TutorReviewsController`, menu "Reviews") with average and all visible reviews. 176 tests green.
- [x] **5c Admin moderation** (24 Oct, DEPLOYED to Hostinger + pushed to GitHub): `/admin/reviews`
  (`Admin\ReviewModerationController`, role:admin + two-factor, menu "Reviews"): tabs low ratings (1-2
  stars), all visible, hidden; real name + "shown as" name; Hide / Show again
  (`POST /admin/reviews/{review}/toggle`) recalculates the tutor's rating at once. Admin overview card
  "Reviews". 179 tests green. No migration.
  **Step 5 is complete.** Next: Step 6 admin tools (users, complaints, site settings incl. brand/stats),
  then Step 7 launch prep.

## 6h. Step 6 = admin tools (owner decisions 25 Oct: user is suspended, never deleted; brand name stays in `.env`;
no email on complaints for now; tutors may also report; admin overview payments/refunds cards say "Coming soon";
Home numbers start manual, with an automatic option). Parts: 6a users, 6b complaints, 6c contact inbox, 6d site settings.
- [x] **6a Users** (25 Oct, committed locally, NOT deployed, NOT pushed): `/admin/users` (`Admin\UserController`,
  role:admin + two-factor, menu "Users"): list with search (name/email/phone) and role / status filters, detail page
  (email verified?, phone, children, tutor review link, demo / booking / review counts). Suspend and re-open need a
  reason (5-500 chars); an admin cannot suspend themselves or any admin. Migration 2026_10_29_000001 adds
  `users.suspended_at` / `suspended_reason` and the `admin_actions` table (`AdminAction`: who, to whom, what, why;
  shown as "History"). Suspended users: login refused after the right password (`LoginRequest`), logged out on the
  next request (`EnsureNotSuspended` in the web group), their DB sessions deleted, and a suspended tutor drops out of
  every public page (`TutorProfile::approved()` now checks it). Admin can "Mark email as verified" with a reason
  (needed while live email is off, see 1f-5). 188 tests green. Next: 6b complaints (done, see below).

- [x] **6b Complaints** (25 Oct, local only, NOT deployed, NOT pushed; owner said: push + deploy after ALL of Step 6):
  Parent, student or tutor reports a problem with a booking or one class ("Report a problem" on upcoming and
  past classes, on the family's confirmed bookings and on the tutor's confirmed students). Routes
  `/dashboard/bookings/{booking}/report` and `/dashboard/classes/{session}/report`, list at `/dashboard/reports`
  (menu "My reports"), `ComplaintController`; only the booking's family or its tutor (else 404); category list
  in `Complaint::CATEGORIES`, details 10-1000 chars, at most 3 open reports per person per booking. Table
  `complaints` (migration 2026_10_30_000001, `ComplaintStatus` New / In progress / Closed). Admin: `/admin/complaints`
  (`Admin\ComplaintController`, menu "Complaints", tabs with counts): detail with family, tutor, booking and class,
  status, private note (never shown to users) and a reply the reporter reads under "My reports". No email (owner
  decision). Tests in `ComplaintTest`. 198 tests green. Next: 6c contact inbox.

- [x] **6c Contact inbox** (25 Oct, local only, NOT deployed, NOT pushed): the Contact form now saves to
  `contact_messages` (migration 2026_10_31_000001; `ContactMessage`, `ContactStatus` New / Read / Answered; sender's
  `user_id` kept when logged in). A hidden `website` field is a bot trap (filled = pretend success, save nothing);
  the 5-per-minute throttle stays. Admin `/admin/messages` (`Admin\ContactMessageController`, menu "Messages",
  tabs with counts): opening a new message marks it Read; "Reply on WhatsApp" (wa.me from the phone, 03xx/+92xx)
  and "Reply by email" (mailto) buttons; status + private note. The site sends no email (owner decision).
  206 tests green. Next: 6d site settings, then push + deploy everything (owner's order).

- [x] **6d Site settings** (25 Oct, local only, NOT deployed, NOT pushed): `/admin/settings`
  (`Admin\SettingsController`, menu "Site settings"). Table `settings` (migration 2026_11_01_000001, key/value);
  `App\Support\SiteSettings` + middleware `ApplySiteSettings` (web group) put saved WhatsApp number / display,
  support email and hours over `config('site.*')`; an empty box means the `.env` / `config/site.php` default.
  WhatsApp is typed once (+92 / 03xx accepted) and stored as digits for wa.me plus the text as typed.
  Home page numbers (`config('site.stats')` are the old placeholders 10K+/100+/95+/4.8): each is "Type my own"
  (default) or "Count automatically" (students = learners with a held class, tutors = approved non-demo tutors,
  subjects = active subjects, rating = visible reviews); a number that counts as zero is hidden, and so is the
  whole block when all are empty. The settings page shows what automatic would show now. Brand name stays in `.env`.
  Admin overview (`dashboard/admin.blade.php`) got Complaints and Contact messages cards and a "Payments and
  refunds: Coming soon" card. CORRECTION: that real page never had made-up figures; the sample figures
  (1,240 students, PKR 2.4M ...) live only in `dashboards/admin.blade.php`, the labelled sample page at
  `/preview/admin` used by the static export. It was left as it is.
  213 tests green. **Step 6 is complete locally (6a-6d).** Per the owner: push to GitHub and deploy to Hostinger
  now (needs `migrate --force` for 4 migrations: 2026_10_29_000001, 2026_10_30_000001, 2026_10_31_000001,
  2026_11_01_000001, then `optimize`; build files are unchanged because no CSS/JS was added).

**Step 6 DEPLOYED (25 Oct, with the owner's OK) and pushed to GitHub (main = 6abad37):** database backed up first
(`~/backup-before-step6-20261025.sql` on the server, outside the web root; delete it when no longer needed), code
extracted into `app/`, 4 migrations run (all ran fine), `artisan optimize`. Live checks: public pages 200, all new
admin / report pages redirect guests to /login, `/.env` 403, security headers present, Home still shows the
placeholder numbers (settings default). NOT tested live: logging in as admin (live email is off, see 1f-5, so the
admin code cannot arrive), the new admin pages with real clicks, and sending a Contact message (would write to the live DB).
**Step 6 is complete.** Next: Step 7 launch prep (see `docs/LAUNCH_CHECKLIST.md`).

## 6i. Step 7 = launch prep (owner agreed the plan 25 Oct: 7a safai + first admin, 7b email, 7c security / backups /
monitoring, 7d speed + SEO, 7e owner's list + beta; analytics NOT now)
- [x] **7a Clean-up + first admin** (25 Oct, DEPLOYED + pushed with 7b-1): new command
  `admin:create <email> --name= --phone=` (`CreateAdmin`; password asked twice, hidden, 12+ chars letters + numbers;
  admin is created email-verified; never an argument). Removed: sample dashboards `/preview/{role}` and their views
  (`resources/views/dashboards`), preview links on Log In + their CSS, `/verify-phone` page, `site:export`
  (`ExportStaticSite`), `StaticExportTest`, `DashboardPagesTest`; `DashboardNav::for()` lost its `$live` flag.
  CSS changed, so the next deploy needs `npm run build` and the new `public/build` files. 213 tests green.
  Still open for 7e (owner): placeholders `[WhatsApp number]` / `[support email]` (set in /admin/settings),
  `MAIL_FROM_ADDRESS` default `hello@example.com` (set in server `.env` with real mail), "DRAFT" banner on the legal pages.
  Not done by hand yet (needs the owner's OK): delete the `static-site` branch on GitHub, local `website/` and
  `upload-to-hostinger*` folders, server `public_html_static_backup/`.

- [x] **7b-1 Email code** (25 Oct, DEPLOYED + pushed): emails no longer block pages.
  Queued (`QueuedNotification`, 3 tries, retry after 1 and 5 min): tutor application / profile decisions, demo and
  booking answers, and the password-reset email (`QueuedResetPassword` via `User::sendPasswordResetNotification`).
  Sent at once, never queued: admin login code (`TwoFactorCode::send` now returns false on failure and the code is
  dropped) and the verification link (`EmailVerificationController::sendLink`; `RegisterController` no longer fires
  the `Registered` event because its default listener is queued). If the mail server is down the person sees "We could
  not send the email right now" instead of a 500. Every email gets Reply-To = support email (listener in
  `AppServiceProvider`, applies the saved Site settings so it also works in the queue worker and console).
  New command `mail:test <email>` (prints mailer / host / from, never the password; warns on log mailer and
  placeholder from-address). "Pakistan time" in the interview email now uses `config('site.timezone_label')`.
  **FOUND on the live server (read-only check): `QUEUE_CONNECTION=database`, `MAIL_MAILER=log`, 0 jobs, 0 failed.**
  So with the current live code, a verification email after registering would just sit in `jobs` (no worker). The new
  code avoids this for verification and the admin code, but the queued emails (reset, decisions) need the hPanel cron
  from `docs/DEPLOY.md` ("Email") before they can ever be sent. `crontab` does not exist over SSH on Hostinger: add the
  cron in hPanel. 7b-2 (real SMTP) waits for the owner's domain. Tests: `MailQueueTest`; 222 tests green.

**7a + 7b-1 DEPLOYED (25 Oct, with the owner's OK; GitHub main = 5f1648b):** database backed up first
(`~/backup-before-7a7b-20261025.sql` on the server; the Step 6 backup `~/backup-before-step6-20261025.sql` is still
there too; delete both when not needed), code + new `public/build` uploaded, old preview/export files deleted on the
server, `migrate --force` (nothing to migrate), `optimize`. Live checks: public pages 200, `/preview/*` and
`/verify-phone` 404, Log In has no preview links, new CSS 200, admin pages redirect guests, `/.env` 403, no new
errors in the log. Commands now on the server: `admin:create`, `mail:test`, `demo:remove`. NOT done yet: the first
admin account (owner runs `admin:create` over SSH, see `docs/DEPLOY.md`), the queue cron in hPanel and real SMTP (7b-2,
waits for the domain). Until then queued emails (password reset, tutor / demo / booking answers) are NOT delivered.

**First admin created and logged in (25 Oct):** `admin:create` was run by the owner from the Claude app's Terminal
panel (PowerShell + `ssh -t ...`; a typed password that echoed in a wrong MSYS2 window was thrown away and a new one
chosen). Admin: Adnan Rashid, musfirah.fiverr2020@gmail.com, role admin, verified. Login worked after reading the
two-factor code from the server log. CORRECTION to earlier notes: the code is NOT in the log by default because the
live `.env` has `LOG_LEVEL=warning` and the log mailer writes at debug level; see `docs/DEPLOY.md` ("First admin
account") for the temporary-debug way. Until real SMTP (7b-2) EVERY admin login (once per session) needs this, so 7b-2 is
now the main blocker. DONE and DEPLOYED 25 Oct (main 6456e3b; backup `~/backup-before-maillog-20261025.sql`, no
`.env` change): the log mailer now writes to its own `mail` channel (daily file `storage/logs/mail-<date>.log`, debug
level, kept 3 days), so reading a code no longer needs `.env` changes (see DEPLOY.md). Verified live with `mail:test`
(file created, `laravel.log` untouched). Small known wart: `mail:test` still ends with "Check the inbox" even when the
mailer is `log`; fix it in the next change. `laravel.log` still holds the debug-time entries of 25 Oct 08:15
(login code and mail text); the code is single-use and expired.

## 6j. Step 7c = security, backups, monitoring (owner OK 25 Oct: PC copy of backups yes, Sentry NOT now)
- [x] **7c-1 Security check** (25 Oct, local, NOT deployed): `composer audit` and `npm audit` show 0 problems. All 25
  admin routes have `role:admin` + `two-factor`; guests reach only public pages and 5 forms, all rate-limited; documents
  are on the private disk and served to admins only (`document` is whitelisted). Found NO access hole. New
  `AccessIsolationTest` (7 tests) walks every id-based route as another parent / student / tutor / admin / guest and
  expects 403/404 and no change; keep it green when adding routes. Logging: code default is now `LOG_STACK=daily`
  (14 days) but the live `.env` has `LOG_STACK=single`: change it to `daily` when deploying (owner agreed in the plan).
  Not covered: an external code review (the checklist wants one before payments exist) and a load test (not on the
  shared server: it hosts other people's sites).
- [x] **7c-2 Backups** (25 Oct, local, NOT deployed, NOT run yet): `bin/backup.sh` (server, daily by cron, DB + uploads,
  checked, keeps 7), `bin/pull-backup.ps1` + `bin/install-pc-backup.ps1` / `.cmd` (copy to `F:\YourTutorBackups`, chosen because C: has little room; the owner double-clicks the `.cmd`; the cron on the server (daily 03:00 UTC, set in hPanel by the owner on 25 Oct) is in place;
  daily scheduled task, 14 kept, private folder). Steps and the restore drill are in `docs/DEPLOY.md` ("Backups").
  STILL TO DO: deploy, run `backup.sh` once, owner adds the hPanel cron, owner runs `install-pc-backup.ps1`, restore
  test into a temporary database, check Hostinger's own backups in hPanel. `BackupScriptTest` guards the scripts.
- **7c-1 + 7c-2 DEPLOYED (25 Oct, main 6c45897; backup `~/backup-before-7c-20261025.sql`):** server `.env` now has
  `LOG_STACK=daily` (rest of `.env` verified unchanged), `bin/backup.sh` ran once by hand on the server: OK, 21 tables in
  the dump, `~/backups` is mode 700 and the files 600, no `.env` inside the archives; the files archive is nearly empty
  because nobody has uploaded a document or photo yet. NOT done yet: the hPanel cron for `backup.sh` (and for the queue),
  `install-pc-backup.ps1` on the owner's PC, the restore drill into a temporary database, checking Hostinger's own
  backups in hPanel. Old manual dumps in `~` (step6, 7a7b, maillog, 7c) can be deleted once the daily ones run.
- [ ] 7c-3 monitoring (UptimeRobot on `/up`, owner makes the account), 7c-4 rollback notes + keep last 3 release archives.

## 6k. Profile page (25 Oct, owner's request after 7c; LIVE since the speed/SEO deploy)
`/dashboard/profile` (`ProfileController`, menu "Profile" / "My profile" for student, parent, tutor, admin; "Edit profile"
link on the dashboard account card; admin must have typed the emailed code). Three separate forms, each asks for the
CURRENT password (`current_password` rule, 6 tries per minute): (1) name + phone (a changed phone loses
`phone_verified_at`; a TUTOR's name is read-only because it was checked against the CNIC and is public: the admin
changes it), (2) email: sets `users.pending_email` (migration 2026_11_02_000001), sends `ConfirmNewEmail` AT ONCE to the
NEW address (signed link, 60 minutes, works on any device, tied to the pending address so an older link dies when a
newer request is made; if the address was taken meanwhile nothing changes) and warns the OLD address with the queued
`EmailChangeRequested`; the email only changes when the link is opened (then it also counts as verified), (3) password
(8+ letters and numbers, admin 12+, must differ from the old one; all other sessions of that user are deleted from the
`sessions` table). Mail down = a message, not a 500. 15 tests in `ProfileTest`, 248 tests green. Deploy needs
`migrate --force` (1 migration). Until real SMTP the confirmation link only appears in `storage/logs/mail-<date>.log`.

## 6l. Step 7d = speed and SEO (25 Oct; LIVE, measured)
Measured on the LIVE home page with Google PageSpeed (web UI; the API had no free quota left), mobile, slow 4G, before
these changes: Performance 89, Accessibility 100, Best Practices 100, SEO 69 (only "page is blocked from indexing",
which is on purpose until launch), FCP and LCP 3.0 s, TBT 0, CLS 0.001. Google's main finding: render-blocking requests
(~2 s), i.e. the Google Fonts stylesheet plus our CSS. Curl showed brotli, 7-day cache, 0.7 KB JS, and a ~1.2 s time to
first byte that is the same for a plain static file, so it is distance / TLS from the measuring PC, not PHP: do NOT
tune PHP for it.
Changes: (1) FONTS SELF-HOSTED: `resources/fonts/*.woff2` (DM Sans 400/500/700, Bricolage Grotesque 600/700, Latin, OFL,
licences next to them), `@font-face` with `font-display: swap` in `app.css`, 2 preloads via `partials/fonts.blade.php`
(both layouts), Google removed from the pages and from the CSP (`style-src 'self'`, `font-src 'self'`); the unused Urdu
font branch was dropped (when an Urdu UI is built, self-host an Urdu font). (2) `public/.htaccess`: fingerprinted
`/build` files get `max-age=31536000, immutable`. (3) SEO: subject and tutor titles/descriptions are cut to Google's
length (tutor title max 2 subjects, ~66 chars; description 155, the call to action is kept), tutor demo FORM page is
`noindex`, and after launch `robots.txt` now blocks the REAL private paths (`/dashboard`, `/admin`, `/two-factor`,
`/email/`, `/profile/`, `/login`, `/register`, `/forgot-password`, `/reset-password`, `/*/demo`); before it still listed
the old `/student` `/parent` `/tutor`. Tests: `FontsTest`, `SeoPolishTest` (all 24 sitemap pages: title 10-70, description
70-160, one h1, canonical, unique titles). 258 tests green.
**DEPLOYED 25 Oct (main dc1f207, with the owner's OK; backup `~/backup-before-profile-seo-20261025.sql`, migration
`pending_email` ran, `public/build` uploaded, `optimize`).** Live checks: 0 Google references, fonts + CSS served with
`max-age=31536000, immutable`, CSP `'self'` only, subject description 156 chars, profile route redirects guests.
MEASURED LIVE AFTER (pagespeed.web.dev, mobile, slow 4G): HOME Performance 89 -> **100** (FCP 3.0 -> 1.1 s, LCP 3.0 -> 1.2 s,
CLS 0, Speed Index 3.0 -> 2.2 s), Accessibility 100, Best Practices 100, SEO 69 (only "blocked from indexing", intended).
`/tutors`: Performance 100 (FCP/LCP 0.9 s), Accessibility 99 ("heading elements not in descending order": h1 straight to
h3 because the Filters h2 is hidden on phones). TUTOR PAGE `/tutors/usman-f`: Performance 100 (FCP 1.1, LCP 1.2 s, CLS 0.001),
Accessibility 100, Best Practices 100, SEO 69. `/contact` was not measured. Left-over Google hints: render-blocking CSS
~300 ms (our own 54 KB CSS; splitting it is not worth it now) and 2 KiB of short cache lifetimes (images/favicon).
The `/tutors` heading fix (an `sr-only` h2 "Tutors" above the cards, test in `SeoPolishTest`) is committed but NOT deployed yet.
`robots.txt` on the temporary `hostingersite.com` domain is NOT ours: Hostinger's CDN answers it (Googlebot disallowed, the
rest allowed); our pages carry `noindex` anyway, and on the real domain our own route answers.
Launch day: `SITE_INDEXABLE=true`, submit `/sitemap.xml` in Search Console (needs the domain).

## 6m. Small screens (25 Oct, owner: "on a small screen it is cut off"; LIVE)
Cause, found by logging in to the LOCAL demo admin at 375 px: (1) the dashboard menu was one strip that scrolls sideways
(14 items for the admin, only 2-3 visible, nothing says "scroll"), (2) every `.data-table` was 560 px+ wide inside a
sideways scroll box, so on a phone only Name/Role/Email showed and Status / the "Open" button were hidden to the right.
Fix: (1) `layouts/dashboard.blade.php`: a "Menu" button (hidden checkbox + label, works without JS) showing the current
page; below 1080 px the list opens as a vertical list, from 1080 px the sidebar is unchanged; (2) below 640 px a table row
is a card (`.data-table` rules in `app.css`) and `site.js` copies each header's text into `data-label` of its cells (a header
holding only an `.sr-only` label, like "Open", gets none). It covers ALL `.data-table` tables (admin overview, users, user
history, demos, bookings, classes, reviews, complaints, messages) and every dashboard menu (student, parent, tutor, admin).
`avail` (availability grid) and `price-table` keep their own sideways scroll on purpose. Checked in the browser at 375 px and
1280 px: no element is cut off on /admin, /admin/users, demos, bookings, classes, reviews, complaints, settings, profile.
Rule for new tables: use `class="data-table"` with a `<thead>` and `<th scope="col">` (`SmallScreenTest` checks it).
DEPLOYED 25 Oct (main f11b69c, with the owner's OK; backup `~/backup-before-smallscreen-20261025.sql`, `npm run build` uploaded, `optimize`, no migration). The `/tutors` heading fix went live with it. 263 tests green. The owner checks it on his phone.

## 6n. Home page picture from the admin (25 Oct, owner's request: "the hero picture must be controllable from the dashboard, add and delete"; LIVE)
Admin > Site settings > "Home page picture" (`Admin\HeroPictureController`, routes `admin.hero.store|update|destroy`, role:admin + two-factor).
Upload (first version had limits; see "SECOND VERSION" below), replace, change the short description (alt text, 120 chars) or delete.
`App\Support\HeroPicture`: the upload is decoded with GD, rotated by EXIF, cut to 4:3 around the centre and saved again as TWO
WebP files `storage/app/public/hero/<16 hex>-1200.webp` and `-600.webp` (random names; the original is never kept, so GPS/EXIF and
any hidden code are gone). Settings keys `hero_image` (base path, validated by regex, so a tampered value shows nothing) and
`hero_image_alt` (table `settings`). A new upload deletes the old pair only after the new one is saved. Home (`home.blade.php`): with a
picture the `.hero__art` card becomes `.hero__art--photo` with `<img srcset 600w/1200w sizes fetchpriority=high width/height>` plus a
preload in `<head>` (LCP); without one, or when the files are missing, the drawn demo card is shown. The 4 numbers were already
admin-controlled (6d). Uses the existing `public/storage` link (exists on the live server; GD + WebP + EXIF available there).
Tests `HeroPictureTest` (9) incl. wrong files (text as .jpg, SVG, GIF, PDF, PHP, too small, too many pixels, too big), code hidden
inside a JPEG not surviving, transparent PNG, missing files, odd settings. Checked in the browser at 375 px and 1280 px.
Deploy: `npm run build` (CSS changed) + code, no migration. 272 tests green.
SECOND VERSION (25 Oct, owner: "too many limits, I got tired of uploading, do it all automatically"; LIVE, backup `~/backup-before-heropicture2-20261025.sql`):
NO size or pixel limits any more. `App\Support\HeroPictureReader` reads JPG, PNG, WebP, GIF (first frame), BMP, AVIF with GD, and
iPhone HEIC / TIFF through Imagick when the server has it (forced reader `HEIC:` / `TIFF:`, after a magic-byte check). Verified ON THE
SERVER (CLI, nothing saved): jpg png gif bmp webp avif heic tiff all read; SVG, PDF, text and PHP are refused with a plain-words reason
(`HeroPictureException`, shown as it is). The web PHP might differ from the CLI for Imagick: if an iPhone HEIC upload is refused on the
live site, that is why (the message then says to save it as JPG/PNG). File limit is only a safety net: 50 MB (`MAX_UPLOAD_KB`); a picture
that would not fit in the PHP memory (about 90+ megapixels on the live 512 MB) is refused with a clear message (the code also tries to raise the
limit to 1 GB). Output: biggest 4:3 centre crop, width = min(1200, crop), small = min(600, crop): a small source is NOT enlarged
(files `<base>-<width>.webp`, widths stored as `hero_image_w` / `hero_image_sw`; Home leaves out `srcset` when both are the same).
The admin page now just says "Any picture works, of any size". Tests: `HeroPictureTest` 15 (formats, small, tiny, tall, huge, refusals,
hidden code, transparent PNG, replace/delete with different sizes). 278 tests green.
THIRD VERSION (26 Oct, owner sent his woman-with-laptop picture and a Grapeslab hero as inspiration: "it is cut, no effect, no motion,
give it a proper modern hero look"; LIVE since 26 Oct 00:3x PKT, main 34ab4c6, backup `~/backup-before-modernhero-20261026.sql`, `npm run build` uploaded, `optimize`, no migration; live checks: pages 200, 3 floating cards, `hero-stage--sample` shown until he uploads a picture, CSS cached 1 year): (1) NOTHING IS CUT any more: the picture keeps its own shape,
longest side 1200 (big) / 640 (small), never enlarged; settings `hero_image_w/_h/_sw/_sh` + `hero_image_mode` (`cutout` | `photo`).
(2) AUTO MODE: if >= 2% of the pixels are see-through it is a "cutout" (empty margins trimmed so the person stands tight on the circles),
else a "photo" (white rounded frame). His file has the chequered pattern painted INTO solid pixels, so it counts as a photo: he needs a
PNG with a REAL transparent background (remove.bg, Canva "background remover", Photoshop...). Automatic background removal was NOT built
(risky: it can eat white clothes); offer it later as an opt-in if he wants. (3) NEW HERO (`home.blade.php`, `app.css` "Hero stage"):
`.hero-stage` with a pale halo + dark green disc + orange dot + dashed spinning ring + dotted grid behind the picture, three floating glass
cards ("Verified tutors: ID, test and interview", "Free demo class: no payment needed", "Live 1-to-1 online") that pop in and float, a soft
sway of the figure, a pulsing green "live" dot, the headline word "trusted tutor" highlighted (`.hero__mark`) and a soft gradient behind the hero.
CSS only (no JS, CSP safe); text and the LCP picture are NOT faded in (kept PageSpeed 100); `prefers-reduced-motion` switches all motion off;
the third card is hidden under 480 px. Without a picture the old sample tutor card sits in the same stage. Dribbble and Awwwards were blocked in
the browser pane, so the look comes from his Grapeslab picture plus common modern edtech patterns (web search). 281 tests green.
Owner next: upload a PNG with a REAL transparent background in /admin/settings and look at Home on his phone.
FOURTH VERSION (26 Oct, owner saw the live hero: size, effects and motion are good; "remove the picture background so the circles are
directly behind the person"; LIVE since 26 Oct, main 9c06e46, backup `~/backup-before-bgremove-20261026.sql`, code only, no build, no migration; tested on the server with a 1,000,000 pixel chequered picture: removed in 0.86 s, 124 MB peak, white shirt kept): AUTOMATIC BACKGROUND REMOVAL. `App\Support\BackgroundRemover`
(GD, no external service, no AI): learns the 1-4 flat colours of the picture's border (must cover 85% of it, else "busy" and nothing
happens), keeps only pixels of those colours that are connected to the border, ignores bridges thinner than 4 px (erode, flood fill,
dilate), so white clothes inside the person are never removed, and fades the halo along the edge (2 rounds). It refuses if less than 4% or
more than 92% would go. Works on a copy of at most 1000 px (about 2 s). The admin form has "Background of the picture": Automatic (default; only
light backgrounds: white, grey, chequered), Keep exactly as it is, Try to remove any plain background (any flat colour);
`HeroPicture::store(..., $background)` returns `removed` / `kept` / `too_busy` and the controller shows a matching message.
Limits to tell the owner: hair edges are rougher than a professional tool (remove.bg, Canva); a busy background (room, street) is
left as it is. `BackgroundRemoverTest` (7, in tests/Unit) + 6 new upload tests; 293 tests green. Checked locally on his picture: removed in 2.2 s,
woman stands on the green disc on phone and desktop. Owner next: upload his picture again in /admin/settings (Background: Automatic) and look at Home on his phone.

## 6o. Site font = Roboto (26 Oct, LIVE on Hostinger 26 Oct, backup `~/backup-before-roboto-20261026.sql`; owner: "only fonts and layout feel AI-made; make the font Roboto everywhere and touch nothing else")
Only the font was changed. DM Sans + Bricolage Grotesque were replaced by **Roboto** (self-hosted, Latin, OFL, 400 / 500 / 700 in
`resources/fonts/roboto-latin-*-normal.woff2` + `LICENSE-Roboto.txt`): `@font-face` and the two CSS variables `--font-body` /
`--font-heading` in `resources/css/app.css`, preloads of 400 and 700 in `partials/fonts.blade.php`, `FontsTest`. Every `font-family`
in the CSS goes through those two variables, so the public site, dashboards and admin all changed together. The one old `font-weight: 600`
now renders as 700 (Roboto has no 600). Layout, copy, colours and the hero were NOT touched. Checked in the browser: Home, tutors,
pricing, contact at 375 px (no sideways overflow) and Home at 1000 px, 0 Google requests, 0 old fonts loaded. 293 tests green.
If the owner also wants the LAYOUT changed (he said it "feels AI-made"), ask for a reference site and plan it first. Needs `npm run build`
and the code on deploy, no migration.

## 7. Waiting for the owner (ask, do not invent)
- WhatsApp number and support email (placeholders in `config/site.php` / `.env`)
- Real prices, discounts, platform fee, payout rules; refund rules (current text is invented and marked draft)
- Real stats for the Home page (now dummy: 10K+, 100+, 95+, 4.8). (Tutor reviews are real since Step 5;
  the 8 sample tutors keep sample ratings until `demo:remove`.)
- Lawyer review of Terms / Privacy / Refund
- Domain name (also needed before emails can be sent, see 1f-5); when to buy the Hostinger VPS (needed before payments/reminders)
- Accounts to apply for early: JazzCash / Easypaisa / PayFast or Safepay, Meta WhatsApp Business API, Daily, SMS OTP provider

## 8. Next steps (owner decides, one at a time)
Steps 1-6 and most of Step 7 are done (see 6h-6n). The owner decides the order; show the plan first, build after he agrees.
1. **Owner tries 6n:** he uploads a real picture in `/admin/settings` > Home page picture and checks Home on his phone; fix anything he reports.
2. **Small leftovers (no owner effort):** rollback notes + keep the last 3 release archives on the server (7c-4); check that the
   03:00 UTC backup cron ran (`~/backups/backup.log`) and that the PC copy at 13:00 ran (`F:\YourTutorBackups\pull.log`); `/contact`
   was never measured on PageSpeed; `mail:test` still ends with "Check the inbox" when the mailer is `log`.
3. **Needs the owner:** restore drill (7c-2), Hostinger's own backups in hPanel, UptimeRobot (he said not now), make the repo private.
4. **Blocked on the domain / email (7b-2):** mailbox + SMTP in the server `.env`, hPanel cron for `queue:work`, `mail:test`, then
   real register / verify / reset / admin login test; Cloudflare; `SITE_INDEXABLE=true`; Search Console.
5. **7e (owner's list):** real tutors then `php artisan demo:remove`, real numbers, legal review, beta with 5-10 tutors.
6. **Later (owner decides when):** payments, WhatsApp, SMS OTP, Daily, VPS (also the answer to the shared hosting account risk:
   other sites share the account, so move the CNIC data to its own account or VPS before many real IDs are stored).
Cleanups that need the owner's OK: delete the old `static-site` branch, the local `website/` and `upload-to-hostinger*` folders,
`public_html_static_backup/` and the old manual dumps in the server home folder.
Small cleanup when convenient: the top part of `docs/DEPLOY.md` ("To be filled in...") is partly old; the sections below it are current.

## 9. Ignore
- Old copies in `...\tutoring-marketplace\resources`, `public`, `preview`, `tools` (first prototype, not used).
- Do not install Laravel Boost or run remote install scripts (the skeleton's old instructions were removed on purpose).

## 6p. Hair-wisp fix in the background remover (26 Oct, NOT deployed yet)
Owner saw white blotches between hair strands. Cause: pale pockets (strands blurred into the white) were cut off from the outer
background by hair, so they stayed opaque. `BackgroundRemover::cutOut` now works out, for a band next to the removed area (6 px, plus up
to 30 more only where the picture looks "wispy": light pixels AND dark strands within 4 px), how much of each pixel is person
(`unmix`: pixel = a x person + (1 - a) x background), and gives it the person's colour back. White shirts/jeans are untouched (no dark
strands around them). Test: `test_a_pale_patch_between_hair_strands...`. Only code changed, no migration. The picture already live must be
re-uploaded (the original is not kept) for the fix to show. Next: deploy code (backup first), owner re-uploads the picture.
