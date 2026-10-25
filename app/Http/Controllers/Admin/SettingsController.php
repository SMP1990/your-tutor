<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DashboardNav;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Step 6d: the admin edits the WhatsApp number, support email and hours, and the four numbers on the Home
 * page (typed by hand, or counted automatically). The brand name stays in .env (owner decision).
 */
class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        $stored = SiteSettings::stored();

        return view('admin.settings.edit', [
            'hero' => \App\Support\HeroPicture::current(),
            'whatsapp' => $stored['whatsapp_display'] ?? '',
            'support_email' => $stored['support_email'] ?? '',
            'support_hours' => $stored['support_hours'] ?? '',
            'stats' => collect(SiteSettings::STATS)->map(fn ($label, $stat) => [
                'label' => $label,
                'mode' => SiteSettings::statMode($stat),
                'value' => SiteSettings::statManual($stat),
                'auto' => SiteSettings::statAuto($stat),
            ]),
            'nav' => DashboardNav::for('admin'),
            'user' => ['name' => $request->user()->name, 'role' => $request->user()->role->label()],
        ]);
    }

    public function update(Request $request)
    {
        $rules = [
            'whatsapp' => ['nullable', 'string', 'max:25', function (string $attribute, mixed $value, \Closure $fail) {
                if (! preg_match('/^\d{10,15}$/', self::digits($value))) {
                    $fail('Enter the WhatsApp number with its country code, e.g. +92 300 1234567.');
                }
            }],
            'support_email' => ['nullable', 'email', 'max:120'],
            'support_hours' => ['nullable', 'string', 'max:100'],
        ];
        foreach (SiteSettings::STATS as $stat => $label) {
            $rules["stats.{$stat}.mode"] = ['required', Rule::in(['manual', 'auto'])];
            $rules["stats.{$stat}.value"] = array_merge(
                ['nullable', "required_if:stats.{$stat}.mode,manual"],
                $stat === 'rating' ? ['numeric', 'between:1,5'] : ['regex:/^\d[\d,.]{0,8}[KkMm]?\+?$/'],
            );
        }

        $data = $request->validate($rules, [
            'stats.*.value.required_if' => 'Type a number, or choose "Count automatically".',
            'stats.*.value.regex' => 'Use a number such as 100, 1,200 or 10K+.',
            'stats.rating.value.between' => 'The average rating must be between 1 and 5.',
        ]);

        $values = [
            'whatsapp_number' => filled($data['whatsapp'] ?? null) ? self::digits($data['whatsapp']) : null,
            'whatsapp_display' => filled($data['whatsapp'] ?? null) ? trim($data['whatsapp']) : null,
            'support_email' => filled($data['support_email'] ?? null) ? mb_strtolower(trim($data['support_email'])) : null,
            'support_hours' => filled($data['support_hours'] ?? null) ? trim($data['support_hours']) : null,
        ];
        foreach (array_keys(SiteSettings::STATS) as $stat) {
            $values["stats_{$stat}_mode"] = $data['stats'][$stat]['mode'];
            $values["stats_{$stat}_value"] = filled($data['stats'][$stat]['value'] ?? null) ? trim($data['stats'][$stat]['value']) : null;
        }

        SiteSettings::save($values);

        return back()->with('notice', 'Settings saved.');
    }

    /** "+92 300-1234567" or "0300 1234567" becomes 923001234567 (used for wa.me links). */
    private static function digits(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number);

        return match (true) {
            str_starts_with($digits, '00') => substr($digits, 2),
            str_starts_with($digits, '0') => '92'.substr($digits, 1),
            default => $digits,
        };
    }
}
