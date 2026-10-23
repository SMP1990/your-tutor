<?php

// Public contact details. Placeholders until the owner provides real ones; later editable in admin.
// WHATSAPP_NUMBER is digits only with country code, e.g. 923001234567 (used for wa.me links).

return [
    'whatsapp_number' => env('WHATSAPP_NUMBER') ?: null,
    'whatsapp_display' => env('WHATSAPP_DISPLAY') ?: '[WhatsApp number]',
    'support_email' => env('SUPPORT_EMAIL') ?: '[support email]',
    'support_hours' => 'Monday to Saturday, 10 am to 8 pm (PKT)',
    'legal_updated' => '24 October 2026',

    // Let search engines index the site. Keep false while the site shows dummy data;
    // set SITE_INDEXABLE=true at launch (adds robots.txt "Allow" and removes the noindex tag).
    'indexable' => (bool) env('SITE_INDEXABLE', false),
];
