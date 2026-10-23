# Site map: your tutor (draft, waiting for approval)

Status: ✅ built · ⬜ to build. All pages use dummy data until the database step.
URLs are relative, so they work on any domain or host.

## 1. Public pages
| # | Page | URL | What it shows | Status |
|---|---|---|---|---|
| 1 | Home | `/` | Hero + search, how it works, featured tutors, subjects, reviews, CTA, why us | ✅ |
| 2 | Find a Tutor | `/tutors` | Filters, tutor cards, sort, pagination | ✅ |
| 3 | Tutor Profile | `/tutors/{name}` | Photo, intro video, qualifications, subjects, rates, availability, reviews, "Book free demo" | ⬜ |
| 4 | Book Free Demo | `/tutors/{name}/demo` | Short form: student, subject, grade, preferred time, phone | ⬜ |
| 5 | Subject page | `/subjects/{subject}` | e.g. "Online Physics tutors": intro + tutors for that subject (good for Google) | ⬜ |
| 6 | How it works | `/how-it-works` | Steps for parents/students, and for tutors | ⬜ |
| 7 | Pricing | `/pricing` | Per session vs monthly packages, sibling discount, free demo | ⬜ |
| 8 | Become a Tutor | `/become-a-tutor` | Benefits, requirements, how payouts work, "Apply" button | ⬜ |
| 9 | About Us | `/about` | Story, mission, team | ⬜ |
| 10 | Contact | `/contact` | WhatsApp, email, contact form | ⬜ |
| 11 | FAQs | `/faqs` | Questions for parents, students, tutors | ⬜ |
| 12 | Terms & Conditions | `/terms` | Legal text (placeholder until a lawyer checks it) | ⬜ |
| 13 | Privacy Policy | `/privacy` | Legal text (placeholder) | ⬜ |
| 14 | Refund Policy | `/refund-policy` | Refund and guarantee rules | ⬜ |

## 2. Login pages
| # | Page | URL | Status |
|---|---|---|---|
| 15 | Log In | `/login` | ⬜ |
| 16 | Register (choose: student, parent or tutor) | `/register` | ⬜ |
| 17 | Verify phone (OTP code) | `/verify-phone` | ⬜ |
| 18 | Forgot / reset password | `/forgot-password` | ⬜ |

## 3. Dashboards (after login)
| # | Dashboard | URL | Main sections |
|---|---|---|---|
| 19 | Student | `/student` | Upcoming classes, join class, bookings, payments, reviews to leave |
| 20 | Parent | `/parent` | Children, attendance, progress and tutor feedback, payments |
| 21 | Tutor | `/tutor` | Application status, profile editor, availability calendar, classes, earnings, withdraw |
| 22 | Admin | `/admin` | Tutor approvals, users, bookings, payments, refunds, payouts, reviews, complaints, site settings (stats, counts) |
| 23 | Classroom | `/class/{id}` | Daily video room + "Join class" (built with the video step) |

## 4. Other
- 404 "page not found" and 500 "something went wrong" pages
- `sitemap.xml` and `robots.txt` (for Google)

## 5. Menus
**Header:** Find a Tutor · Subjects · Pricing · How it works · Become a Tutor · Log In · Register
**Footer:**
- Company: About Us · Contact · FAQs · Become a Tutor
- Legal: Terms · Privacy · Refund Policy
- WhatsApp support button (bottom corner, every page)

## 6. Suggested build order
1. Tutor Profile (3) and Book Free Demo (4)
2. How it works (6), Pricing (7), Become a Tutor (8)
3. About, Contact, FAQs, legal pages (9-14), 404/500
4. Subject pages (5)
5. Login pages (15-18)
6. Dashboards (19-22), design only
7. Classroom (23) and the database, when you ask for it
