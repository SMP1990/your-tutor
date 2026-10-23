# Launch Checklist

Tick every box before the platform goes live. Anything unticked = not ready.

## 1. Security
- [ ] Card payments use the gateway's hosted checkout (no card data on our server)
- [ ] Payment webhook signatures verified; duplicate webhooks ignored
- [ ] Students, parents, tutors and admin can only see their own data (tested)
- [ ] Login, OTP and password reset are rate-limited
- [ ] Admin accounts use two-step login
- [ ] All secrets are in `.env`; `.env` is not in git and not publicly reachable
- [ ] `APP_DEBUG=false` on the live site
- [ ] File uploads validated and stored privately
- [ ] `composer audit` shows no known vulnerabilities
- [ ] HTTPS everywhere + Cloudflare enabled
- [ ] External code & security review by an experienced Laravel developer (focus: payments)

## 2. Payments
- [ ] Tested in sandbox: successful payment, failed payment, cancelled payment
- [ ] Escrow: money released to tutor only after class is completed
- [ ] Refund flow tested end to end
- [ ] Tutor payout / withdrawal tested
- [ ] Manual bank transfer + admin verification tested
- [ ] Amounts and currency (PKR) correct on invoices and receipts

## 3. Stability
- [ ] Staging site exists and matches the live setup
- [ ] Daily off-server backups running; one restore tested successfully
- [ ] Queue worker and scheduler (cron) running and monitored
- [ ] Uptime monitoring with alerts (e.g. UptimeRobot)
- [ ] Error tracking connected (e.g. Sentry)
- [ ] Load test done: we know how many users/classes the server handles
- [ ] Rollback steps written and tried once

## 4. Core features (tested by real people)
- [ ] Sign-up / login (email + OTP) for student, parent, tutor
- [ ] Tutor application -> admin approval
- [ ] Search tutors, view profile, book free demo
- [ ] Buy package / pay per session
- [ ] Join class (Daily) from laptop and mobile; weak-internet test
- [ ] WhatsApp reminders arrive; email backup works if WhatsApp fails
- [ ] Attendance shows on parent dashboard
- [ ] Reviews and ratings
- [ ] All automated tests pass

## 5. Speed & SEO
- [ ] Google PageSpeed Insights: good score on mobile for home and tutor pages
- [ ] Unique title + meta description on every public page
- [ ] sitemap.xml and robots.txt live; site added to Google Search Console
- [ ] Schema markup and Open Graph tags
- [ ] Images optimised (WebP, lazy loading)
- [ ] Analytics installed

## 6. Legal & trust
- [ ] Terms, Privacy and Refund policy pages live
- [ ] Parent consent for recording classes of children
- [ ] WhatsApp opt-in at sign-up
- [ ] Support contact (WhatsApp/email) visible

## 7. Beta launch
- [ ] Beta with 5-10 tutors and a small group of students
- [ ] Feedback collected and critical bugs fixed
- [ ] No new features during beta
- [ ] Go / no-go decision taken before public launch

## 8. After launch
- [ ] Monthly updates of Laravel and packages
- [ ] Monthly check of backups, errors and server usage
- [ ] Maintenance budget agreed
