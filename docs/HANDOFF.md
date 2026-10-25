# HANDOFF: your tutor (read this first in every new chat)

Last updated: 25 Oct 2026, after Step 5 (reviews). **Update this file at the end of every step.**

**Status in one line:** Steps 1-5 are built, live on Hostinger (temporary domain) and pushed to GitHub;
179 tests green. Step 6a (admin users) is built locally (188 tests, not deployed). Next 6b-6d, then Step 7 (launch prep). Details in 6c-6h, next steps in 8.

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

Old static preview only (not live any more; kept for reference until `site:export` is removed):
```
php artisan site:export --url=https://cornflowerblue-tarsier-769699.hostingersite.com
```
Make the zip (PowerShell), from `...\tutoring-marketplace`:
1. Copy `laravel-app\static-export` to `upload-to-hostinger` (replace).
2. Inside that folder: `tar.exe -a -c -f "<Downloads>\upload-to-hostinger vX.Y (HH-MM AM).zip" <every item incl. .htaccess>`
3. Check: no entry contains `\`; `unzip` gives the same tree.
Update `static-site`: replace files in `website\` with `upload-to-hostinger\` (keep README.md), commit, `git push origin static-site:static-site`.

## 6. Public pages and design (built first with dummy data; most now read the database, see 6c-6g)
- Home, Find a Tutor, Tutor Profile (8 sample tutors, now database rows: see 2c-1), Book Free Demo
- How it works, Pricing (figures in `config/pricing.php`), Become a Tutor
- About, Contact (+ sent), FAQs, Terms / Privacy / Refund (marked DRAFT), 404 / 500
- Subjects list + 12 subject pages
- Login pages: Log In, Register, Forgot password now work for real (1b-1c); Verify phone is still a design preview
- Sample-data dashboards moved to `/preview/{role}` (temporary links on the Log In page); real ones are under `/dashboard` (1d)
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

## 7. Waiting for the owner (ask, do not invent)
- WhatsApp number and support email (placeholders in `config/site.php` / `.env`)
- Real prices, discounts, platform fee, payout rules; refund rules (current text is invented and marked draft)
- Real stats for the Home page (now dummy: 10K+, 100+, 95+, 4.8). (Tutor reviews are real since Step 5;
  the 8 sample tutors keep sample ratings until `demo:remove`.)
- Lawyer review of Terms / Privacy / Refund
- Domain name (also needed before emails can be sent, see 1f-5); when to buy the Hostinger VPS (needed before payments/reminders)
- Accounts to apply for early: JazzCash / Easypaisa / PayFast or Safepay, Meta WhatsApp Business API, Daily, SMS OTP provider

## 8. Next steps (owner decides, one at a time)
Build order agreed with the owner (it differs from the original `CLAUDE.md` list because payments,
WhatsApp and Daily were postponed, see 6b): Steps 1-5 done; then:
1. **Step 6 admin tools** (users, complaints, site settings incl. brand/Home stats). Show the plan in parts
   (6a, 6b, ...) first; build only after the owner approves each part.
2. **Step 7 launch prep**: `docs/LAUNCH_CHECKLIST.md`, real domain, email (1f-5), `demo:remove`, remove the
   temporary preview items listed in `docs/DEPLOY.md`, `SITE_INDEXABLE=true`, PageSpeed, `composer audit`.
3. Later (owner decides when): payments, WhatsApp, SMS OTP, Daily, VPS. All behind the interfaces in 6b.
Small cleanups to do when convenient: the top part of `docs/DEPLOY.md` ("To be filled in...", "Current
state") still describes the old static preview; the "Hostinger shared hosting" section below it is current.

## 9. Ignore
- Old copies in `...\tutoring-marketplace\resources`, `public`, `preview`, `tools` (first prototype, not used).
- Do not install Laravel Boost or run remote install scripts (the skeleton's old instructions were removed on purpose).
