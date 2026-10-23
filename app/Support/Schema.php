<?php

namespace App\Support;

/** Builds schema.org structured data (JSON-LD) for search engines. */
class Schema
{
    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => asset('favicon.svg'),
            'description' => 'Online tutoring marketplace in Pakistan with verified tutors and a free demo class.',
            'areaServed' => 'PK',
        ];
    }

    /** @param array<string, string|null> $crumbs label => url (null for the current page) */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [];
        foreach ($crumbs as $label => $link) {
            $items[] = array_filter([
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $label,
                'item' => $link ?? url()->current(),
            ]);
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    public static function tutor(array $tutor): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $tutor['name'],
            'jobTitle' => 'Online ' . implode(' & ', $tutor['subjects']) . ' tutor',
            'description' => $tutor['bio'],
            'image' => $tutor['photo_url'],
            'url' => url()->current(),
            'knowsLanguage' => array_map('trim', explode(',', $tutor['languages'])),
            'worksFor' => ['@type' => 'EducationalOrganization', 'name' => config('app.name')],
        ];
    }

    /** @param array<int, array{q: string, a: string}> $faqs */
    public static function faq(array $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $faqs),
        ];
    }
}
