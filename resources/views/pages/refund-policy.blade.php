@extends('layouts.legal')

@section('title', 'Refund Policy | ' . config('app.name'))
@section('legal-title', 'Refund Policy')

@section('legal-body')
<p>We want every family to feel safe paying in advance. Your payment is held by us and is released to the tutor only after the class has taken place.</p>

<h2>1. Free demo class</h2>
<p>The first demo with each tutor is free, so there is nothing to refund.</p>

<h2>2. If the tutor misses a class</h2>
<p>If the tutor does not join a paid class, or cancels less than 12 hours before it, you can choose a full refund for that class or a free replacement class.</p>

<h2>3. If you cancel</h2>
<ul>
    <li>Cancel 12 hours or more before the class: full refund or move the class to another time.</li>
    <li>Cancel less than 12 hours before, or do not join: the class is counted as completed.</li>
</ul>

<h2>4. Technical problems</h2>
<p>If a class cannot happen because of a problem on our side, you get a free replacement class or a refund.</p>

<h2>5. Monthly packages</h2>
<ul>
    <li>Unused classes move to the next month.</li>
    <li>You can stop a package at any time and get a refund for classes not yet taken, at the per-class package price.</li>
    <li>If you switch tutor, your unused classes move to the new tutor.</li>
</ul>

<h2>6. How refunds are paid</h2>
<p>Refunds go back to the original payment method, or as account credit if you prefer. They are usually processed within 5–7 working days.</p>

<h2>7. How to ask for a refund</h2>
<p>Use the "Report a problem" button on the class in your dashboard, or <a href="{{ route('contact') }}">contact us</a> within 7 days of the class.</p>
@endsection
