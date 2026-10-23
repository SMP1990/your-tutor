@extends('layouts.legal')

@section('title', 'Terms & Conditions | ' . config('app.name'))
@section('legal-title', 'Terms & Conditions')

@section('legal-body')
<p>These terms apply to everyone who uses {{ config('app.name') }}: parents, students and tutors. By creating an account or booking a class you agree to them.</p>

<h2>1. What we do</h2>
<p>We run {{ config('app.name') }}, an online marketplace. We help families find tutors, book classes, attend them online and pay securely. Tutors are independent and are not our employees.</p>

<h2>2. Accounts</h2>
<ul>
    <li>Give true and complete details and keep your password private.</li>
    <li>Students under 18 must use the platform with a parent or guardian's permission. A parent account can manage children's classes.</li>
    <li>We may suspend accounts that break these terms.</li>
</ul>

<h2>3. Booking and classes</h2>
<ul>
    <li>The first demo class with each tutor is free.</li>
    <li>Paid classes are booked at the tutor's hourly rate shown on their profile, or as part of a monthly package.</li>
    <li>Be on time. A class may be counted as completed if the student does not join within 15 minutes of the start time.</li>
    <li>Keep all classes and payments on the platform. Arranging classes outside the platform with a tutor met here is not allowed.</li>
</ul>

<h2>4. Payments</h2>
<p>Payments are made in advance through the payment methods on the site. We hold the payment and release it to the tutor after the class is completed. Refunds follow our <a href="{{ route('refund-policy') }}">Refund Policy</a>.</p>

<h2>5. Tutors</h2>
<ul>
    <li>Tutors must pass our checks and keep their profile accurate.</li>
    <li>Tutors set their own rate and availability, and a platform fee is deducted from each paid class.</li>
    <li>Tutors must behave professionally, especially with children.</li>
</ul>

<h2>6. Behaviour</h2>
<p>No harassment, hateful content, cheating services or sharing of other people's personal information. Report any problem to us and we will act on it.</p>

<h2>7. Recordings</h2>
<p>Classes are recorded only when the family has agreed. Recordings of children need a parent's consent. See our <a href="{{ route('privacy') }}">Privacy Policy</a>.</p>

<h2>8. Liability</h2>
<p>We work hard to keep the service running and to check tutors, but we cannot guarantee exam results or that the service will never be interrupted.</p>

<h2>9. Changes</h2>
<p>We may update these terms. We will show the new date at the top of this page and tell account holders about important changes.</p>

<h2>10. Contact</h2>
<p>Questions about these terms? <a href="{{ route('contact') }}">Contact us</a>.</p>
@endsection
