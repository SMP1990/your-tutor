@extends('layouts.dashboard')

@section('title', 'Message')

@section('content')
<div class="dash-head">
    <div>
        <h1>{{ $message->topic }}</h1>
        <p>
            <span class="status status--{{ $message->status->colour() }}">{{ $message->status->label() }}</span>
            Received {{ $message->created_at->copy()->timezone(config('site.timezone'))->format('d M Y, g:i A') }}
        </p>
    </div>
    <a href="{{ route('admin.contact.index') }}" class="btn btn-outline btn-sm">Back to list</a>
</div>

@if ($errors->any())
    <div class="form-alert" role="alert">{{ $errors->first() }}</div>
@endif

<div class="dash-grid dash-grid--2">
    <section class="panel">
        <div class="panel__head"><h2>Message</h2></div>
        <p class="prose-text">{{ $message->message }}</p>
        <dl class="review-list">
            <div><dt>Name</dt><dd>{{ $message->name }}@if ($message->user) · has an account: <a href="{{ route('admin.users.show', $message->user) }}">{{ $message->user->name }}</a>@endif</dd></div>
            <div><dt>Phone</dt><dd>{{ $message->phone ?: '—' }}</dd></div>
            <div><dt>Email</dt><dd>{{ $message->email ?: '—' }}</dd></div>
        </dl>
        <div class="form-actions">
            @if ($message->whatsappUrl())
                <a href="{{ $message->whatsappUrl() }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">Reply on WhatsApp</a>
            @endif
            @if ($message->email)
                <a href="mailto:{{ $message->email }}" class="btn btn-outline btn-sm">Reply by email</a>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="panel__head"><h2>Status</h2></div>
        <form method="post" action="{{ route('admin.contact.update', $message) }}" class="demo-form" novalidate>
            @csrf
            @method('PUT')
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" class="input">
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $message->status->value) === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="admin_note">Private note <span class="field__optional">(only admins see this)</span></label>
                <textarea id="admin_note" name="admin_note" rows="3" maxlength="2000" class="input">{{ old('admin_note', $message->admin_note) }}</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </section>
</div>
@endsection
