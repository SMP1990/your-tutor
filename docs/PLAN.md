# Plan: your tutor

Short version of `Online_Tutoring_Platform_Plan.pdf` (Oct 2026). The PDF is the full source.

## Decisions
| Topic | Decision |
|---|---|
| Model | Online tutoring only. Free demo class for every new student. |
| Market | Pakistan first (PKR). KSA later (SAR). |
| Brand | your tutor |
| Stack | Laravel + MySQL |
| Video | Daily (Prebuilt). Jitsi / BigBlueButton as later options. |
| Reminders | WhatsApp Business Cloud API (approved templates) + email fallback |
| Payments | JazzCash, Easypaisa, card/bank via aggregator (PayFast/Safepay), manual bank transfer. Escrow + refunds + payouts. |
| Hosting | Hostinger VPS KVM 2 when payments/reminders are built. Static preview on shared hosting until then. |

## Roadmap
**A. Preparation (owner, no code)**
1. Brand name, domain, social handles
2. Business documents (CNIC, bank details, NTN if needed)
3. Fees and packages (monthly, per session, sibling discount)
4. Refund, guarantee and tutor payout policies
5. List of first 20-30 tutors

**B. Accounts and approvals (owner, start early, they take time)**
6. Payment accounts (JazzCash, Easypaisa, PayFast or Safepay)
7. Meta Business account + WhatsApp Business API number
8. Daily account (free tier)
9. Email service and SMS OTP provider

**C. Build**
10. Database design
11. UI/UX design (Home and Find a Tutor done)
12. Hostinger VPS setup
13. Phase 1 (MVP), in the build order from `CLAUDE.md`
14. Beta with a few tutors and students

**D. Launch and grow**
15. Launch and marketing
16. Phase 2, then Phase 3 and KSA

## Phase 1 (MVP) features
- **Student / parent:** sign-up/login (email + phone OTP), tutor search and filters, tutor profile (video, qualifications, reviews), free demo booking, monthly package or pay per session, parent dashboard (attendance, progress, feedback).
- **Tutor:** application + document upload, admin approval, availability calendar, earnings and withdrawal request.
- **Classes:** Daily classroom, recording, automatic reminders (WhatsApp/email).
- **Payments:** JazzCash, Easypaisa, card/bank, manual bank transfer + screenshot, escrow, refund.
- **Admin:** approve/reject tutors, payments, refunds, payouts, users and classes, complaints. Also edits site figures (stats, counts) and reviews.
- **Trust:** ratings and reviews, WhatsApp support button, refund/guarantee policy page.

## Phase 2 (growth)
AI tutor matching, AI progress reports, Urdu/English interface, referral and sibling codes, tutor levels, group classes.

## Phase 3
Mock tests and past papers, AI weak-topic detection, gamification, low-bandwidth mode, mobile app.

## Main risks
- Building everything at once: build Phase 1 only.
- Payments, escrow and admin are the heaviest parts: plan them first.
- No tutors means no students: sign up 20-30 tutors first.
- Weak internet in Pakistan: low-bandwidth mode later.
- Merchant and WhatsApp approvals are slow: apply before development needs them.
- Missed payment webhooks: reliable server (VPS), retries and a manual check.
