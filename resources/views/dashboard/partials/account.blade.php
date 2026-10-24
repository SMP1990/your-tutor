{{-- Expects $account (User). --}}
<section class="panel">
    <div class="panel__head"><h2>Your account</h2></div>
    <ul class="item-list">
        <li>
            <div class="item-list__main"><strong>Email</strong><span>{{ $account->email }}</span></div>
            <div class="item-list__side"><span class="status status--green">Verified</span></div>
        </li>
        <li>
            <div class="item-list__main"><strong>WhatsApp number</strong><span>{{ $account->phone ?: 'Not added' }}</span></div>
            <div class="item-list__side">
                @if ($account->phone_verified_at)
                    <span class="status status--green">Verified</span>
                @else
                    <span class="status status--grey">Not verified yet</span>
                @endif
            </div>
        </li>
        <li>
            <div class="item-list__main"><strong>WhatsApp reminders</strong><span>{{ $account->whatsapp_opt_in ? 'You agreed to get reminders' : 'Off' }}</span></div>
        </li>
    </ul>
</section>
