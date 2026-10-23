<?php

namespace App\Http\Controllers;

use App\Support\DemoSubjects;
use App\Support\DemoTutors;

class SeoController extends Controller
{
    /** Public pages that search engines may list. Forms, logins and dashboards are left out. */
    public static function urls(): array
    {
        $routes = ['home', 'tutors.index', 'subjects.index', 'how-it-works', 'pricing', 'become-a-tutor',
            'about', 'faqs', 'contact', 'terms', 'privacy', 'refund-policy'];

        return array_merge(
            array_map(fn ($name) => route($name), $routes),
            array_map(fn ($t) => route('tutors.show', $t['slug']), DemoTutors::all()),
            array_map(fn ($s) => route('subjects.show', $s['slug']), DemoSubjects::all()),
        );
    }

    public function sitemap()
    {
        return response()
            ->view('seo.sitemap', ['urls' => self::urls()])
            ->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $lines = config('site.indexable')
            ? ['User-agent: *', 'Disallow: /student', 'Disallow: /parent', 'Disallow: /tutor', 'Disallow: /admin', '', 'Sitemap: '.route('sitemap')]
            : ['# Preview site with sample data: keep it out of search engines until launch.', 'User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain');
    }
}
