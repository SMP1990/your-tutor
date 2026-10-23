@extends('layouts.legal')

@section('title', 'Privacy Policy | ' . config('app.name'))
@section('legal-title', 'Privacy Policy')

@section('legal-body')
<p>This policy explains what personal information {{ config('app.name') }} collects, why, and how we protect it. We collect as little as we need, especially about children.</p>

<h2>1. What we collect</h2>
<ul>
    <li><strong>Account details:</strong> name, phone number, email and password (stored encrypted).</li>
    <li><strong>Student details:</strong> first name, grade and subjects. We do not ask for a child's photo or ID.</li>
    <li><strong>Tutor details:</strong> qualifications, ID and documents for checks, profile photo and video, and payout details.</li>
    <li><strong>Class details:</strong> bookings, attendance, class notes, reviews and, only with consent, recordings.</li>
    <li><strong>Payments:</strong> amounts and status. Card details are entered on the payment provider's page and never reach our servers.</li>
</ul>

<h2>2. Why we use it</h2>
<ul>
    <li>To run your account, book classes and process payments and refunds.</li>
    <li>To send class reminders on WhatsApp or email, only if you agreed to receive them.</li>
    <li>To keep the platform safe and to answer support requests.</li>
</ul>

<h2>3. Who can see it</h2>
<ul>
    <li>Tutors see only what they need for their own classes.</li>
    <li>Parents see their own children's classes, attendance and notes.</li>
    <li>Service providers we use (payments, video classes, messaging, hosting) process data only to provide their service.</li>
    <li>We never sell personal information.</li>
</ul>

<h2>4. Class recordings</h2>
<p>Classes are recorded only with consent, and classes of children only with a parent's consent. Recordings can be viewed only by the family, the tutor and, for safety reviews, our support team.</p>

<h2>5. How long we keep it</h2>
<p>We keep your information while your account is active and for as long as the law requires afterwards. You can ask us to delete your account.</p>

<h2>6. Your rights</h2>
<p>You can ask to see, correct or delete your personal information, and you can stop WhatsApp or email messages at any time.</p>

<h2>7. Security</h2>
<p>We use encrypted connections (HTTPS), encrypted passwords and strict access rules. Uploaded documents are stored privately.</p>

<h2>8. Contact</h2>
<p>For privacy questions or requests, <a href="{{ route('contact') }}">contact us</a>.</p>
@endsection
