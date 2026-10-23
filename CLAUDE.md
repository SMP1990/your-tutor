# your tutor: Online Tutoring Platform

Online-only tutoring marketplace. Pakistan first (PKR), KSA later (SAR). Students/parents book
tutors, attend live classes, pay securely. Plan lives in `docs/PLAN.md`.
Brand name: "your tutor" (set in `APP_NAME`, never hard-coded in views).

## Stack (final)
- Laravel + MySQL (PHP). Hosting: Hostinger VPS (KVM 2) later; keep code host-independent.
  Until then, a static preview runs on Hostinger Business shared hosting (git branch `static-site`).
- Video: Daily (Prebuilt classroom). Keep it behind one service class so it can be swapped.
- Reminders: WhatsApp Business Cloud API (approved templates) + email fallback.
- Payments: JazzCash, Easypaisa, card/bank via an aggregator, manual bank transfer. Escrow model.

## How to work (IMPORTANT)
- Do ONE step at a time. Finish it, stop, summarise, and ask permission before the next step.
- Plan first, show the plan, build only after approval.
- Keep code short. Small files, small classes, one job each. No giant files.
- Reuse Laravel built-ins (auth, queues, scheduler, policies). Do not reinvent them.
- The user is not technical about servers: give click-by-click, one-step-at-a-time instructions.
- Be honest and critical: say what is wrong and how to fix it. No empty praise.
- Ask for confirmation before redoing work if the same request is repeated.

## Rules for safety and portability
- No secrets in code. Everything in `.env` (keys, passwords, URLs). Keep `.env.example` updated.
- Database changes only through migrations. Seeders for test data.
- Files/recordings go through Laravel's storage layer (so storage can move to cloud later).
- Third parties (Daily, WhatsApp, gateways) are wrapped in service classes, never called from views.
- Money: store amounts in minor units with a currency code (PKR/SAR). Support multiple gateways.
- Payment webhooks must be idempotent and logged. Never trust the browser for payment status.
- No host-specific code. Cron/scheduler setup documented in `docs/DEPLOY.md`.

## Tests
- Every payment, escrow, refund, booking and payout flow needs automated tests before it is "done".
- Run the tests after every change; do not refactor without green tests.

## Security & Quality (non-negotiable)
Security
- Never collect or store card details. Use the gateway's hosted checkout only.
- Verify the signature of every payment webhook. Reject anything unsigned or replayed.
- Use Laravel built-ins: hashed passwords, CSRF, Eloquent/query bindings (no raw SQL with user input), validation on every request.
- Authorization through Policies/Gates: students see only their data, tutors only their classes, admin separated.
- Rate-limit login, OTP and password reset. Two-step login for admin accounts.
- Validate file uploads (type, size), store outside the public folder, serve through signed URLs.
- Children's data: collect the minimum, record classes only with parent consent, restrict who can view recordings.
- Keep Laravel and packages updated; run `composer audit` before each release.

Stability
- Every change goes to staging first, then production. Keep rollback simple (git tag per release).
- Heavy work (WhatsApp, email, reports) runs on queues, never inside the web request.
- Daily backups (database + files) stored off the server; test a restore regularly.
- Third-party failure must not break the site: WhatsApp fails -> email; Daily fails -> clear message + rejoin.

Bugs & monitoring
- Log errors to an error tracker (e.g. Sentry) and keep useful app logs (payments, bookings).
- Add uptime monitoring with alerts.
- Fix bugs before adding features. During beta/launch: no new features.

Speed & SEO
- Public pages server-rendered with Blade; minimal JavaScript.
- Clean URLs, unique titles/meta descriptions, sitemap.xml, robots.txt, schema markup, Open Graph tags.
- Cache pages/queries where safe, optimise images (WebP, lazy load), use Cloudflare.
- Check Google PageSpeed Insights before every major release.

Release gate: follow `docs/LAUNCH_CHECKLIST.md` before going live.

## Git
- Small commits, one change each, clear messages. Work on branches; merge after tests pass.

## Build order (Phase 1 / MVP)
1. Project skeleton, auth (email + phone OTP), roles (student, parent, tutor, admin)
2. Tutor application + admin approval
3. Tutor profiles, search, availability
4. Booking + free demo class
5. Payments + escrow + refunds + payouts
6. Classroom (Daily) + attendance + recordings
7. Reminders (WhatsApp/email)
8. Parent dashboard, reviews, admin panel
Phase 2/3 only after launch (see `docs/PLAN.md`).

## Do NOT
- Create accounts, enter real payment details, or use real API keys while testing. Use test/sandbox keys.
- Add features outside the current step.
