<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Speed: fonts come from our own server, so no page waits for (or sends visitors to) another company's server. */
class FontsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_dashboard_pages_use_our_own_fonts_only(): void
    {
        $pages = [$this->get('/'), $this->get('/login'), $this->actingAs(User::factory()->parent()->create())->get('/dashboard/parent')];

        foreach ($pages as $response) {
            $html = $response->assertOk()->getContent();
            $this->assertStringNotContainsString('fonts.googleapis.com', $html);
            $this->assertStringNotContainsString('fonts.gstatic.com', $html);
            $this->assertMatchesRegularExpression('#<link rel="preload" href="[^"]*roboto-latin-400-normal[^"]*\.woff2" as="font" type="font/woff2" crossorigin>#', $html);
            $this->assertMatchesRegularExpression('#<link rel="preload" href="[^"]*roboto-latin-700-normal[^"]*\.woff2" as="font" type="font/woff2" crossorigin>#', $html);
        }
    }

    public function test_the_page_rules_no_longer_allow_google_fonts(): void
    {
        $this->assertStringNotContainsString('google', SecurityHeaders::CSP);
        $this->assertStringContainsString("font-src 'self';", SecurityHeaders::CSP);
        $this->assertStringContainsString("style-src 'self';", SecurityHeaders::CSP);
    }

    public function test_every_font_in_the_css_exists_with_its_licence(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        preg_match_all("#url\('\.\./fonts/([^']+\.woff2)'\)#", $css, $m);

        $this->assertCount(3, array_unique($m[1]));
        foreach ($m[1] as $file) {
            $this->assertFileExists(resource_path("fonts/{$file}"));
        }
        $this->assertFileExists(resource_path('fonts/LICENSE-Roboto.txt'));
        $this->assertStringContainsString('font-display: swap', $css);
    }

    public function test_fingerprinted_files_get_a_one_year_cache_rule(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('max-age=31536000, immutable', $htaccess);
        $this->assertTrue((bool) preg_match('#FilesMatch "([^"]+)"#', $htaccess, $rule));
        $pattern = '#'.str_replace('#', '\#', $rule[1]).'#';
        $this->assertSame(1, preg_match($pattern, 'app-CBl8OWmA.css'));
        $this->assertSame(1, preg_match($pattern, 'roboto-latin-400-normal-CW0RaeGs.woff2'));
        $this->assertSame(0, preg_match($pattern, 'favicon.svg'), 'files without a fingerprint must not be cached for a year');
        $this->assertSame(0, preg_match($pattern, 'og-image.png'));
    }
}
