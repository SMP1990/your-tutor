<?php

namespace Tests\Feature;

use App\Http\Controllers\SeoController;
use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

class SeoAndSecurityTest extends TestCase
{
    use SeedsDemoContent;

    public function test_pages_send_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("script-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_pages_have_no_inline_javascript(): void
    {
        foreach (['/', '/tutors', '/tutors/ayesha-r/demo', '/register'] as $url) {
            $this->assertDoesNotMatchRegularExpression('#<script>#', $this->get($url)->getContent(), $url);
        }
    }

    public function test_public_pages_have_unique_titles_and_descriptions(): void
    {
        $titles = $descriptions = [];
        foreach (SeoController::urls() as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            preg_match('#<title>(.*?)</title>#', $html, $t);
            preg_match('#<meta name="description" content="(.*?)">#', $html, $d);
            $titles[] = $t[1];
            $descriptions[] = $d[1];
            $this->assertStringContainsString('rel="canonical"', $html, $url);
            $this->assertStringContainsString('property="og:image"', $html, $url);
        }

        $this->assertSame(count($titles), count(array_unique($titles)), 'Duplicate page titles');
        $this->assertSame(count($descriptions), count(array_unique($descriptions)), 'Duplicate descriptions');
    }

    public function test_sitemap_lists_public_pages_only(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('tutors.show', 'ayesha-r'))
            ->assertSee(route('subjects.show', 'physics'))
            ->assertDontSee('/admin')
            ->assertDontSee('/demo');
    }

    public function test_preview_is_hidden_from_search_engines_until_launch(): void
    {
        config(['site.indexable' => false]);
        $this->get('/robots.txt')->assertSee('Disallow: /');
        $this->get('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        config(['site.indexable' => true]);
        $this->get('/robots.txt')->assertSee('Sitemap: ' . route('sitemap'))->assertSee('Disallow: /admin');
        $this->get('/')->assertDontSee('noindex, nofollow');
    }

    public function test_structured_data_on_key_pages(): void
    {
        $this->get('/')->assertSee('"@type":"EducationalOrganization"', false);
        $this->get('/tutors/ayesha-r')->assertSee('"@type":"Person"', false)->assertSee('"@type":"BreadcrumbList"', false);
        $this->get('/faqs')->assertSee('"@type":"FAQPage"', false);
    }
}
