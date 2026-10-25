<?php

namespace Tests\Feature;

use App\Http\Controllers\SeoController;
use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

/** Step 7d: what Google reads. Every page in the sitemap must have a sensible title, description, h1 and canonical. */
class SeoPolishTest extends TestCase
{
    use SeedsDemoContent;

    public function test_every_sitemap_page_has_a_good_title_description_h1_and_canonical(): void
    {
        $urls = SeoController::urls();
        $this->assertGreaterThan(20, count($urls));

        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('#<title>([^<]{10,70})</title>#', $html, "title of {$url}");
            $this->assertSame(1, preg_match('#<meta name="description" content="([^"]*)"#', $html, $d), "description of {$url}");
            $length = mb_strlen(html_entity_decode($d[1]));
            $this->assertGreaterThanOrEqual(70, $length, "description of {$url} is too short ({$length})");
            $this->assertLessThanOrEqual(160, $length, "description of {$url} is too long ({$length})");
            $this->assertSame(1, preg_match_all('#<h1[\s>]#', $html), "one h1 on {$url}");
            $this->assertStringContainsString('<link rel="canonical" href="', $html, "canonical of {$url}");
        }
    }

    public function test_titles_are_unique(): void
    {
        $titles = [];
        foreach (SeoController::urls() as $url) {
            preg_match('#<title>([^<]*)</title>#', $this->get($url)->getContent(), $m);
            $titles[$url] = $m[1];
        }

        $this->assertSame(count($titles), count(array_unique($titles)), 'two pages share a title: '.json_encode(array_diff_assoc($titles, array_unique($titles))));
    }

    public function test_subject_descriptions_keep_the_call_to_action_when_the_intro_is_long(): void
    {
        foreach (['mathematics', 'physics', 'quran-islamiat'] as $slug) {
            $this->get("/subjects/{$slug}")->assertOk()->assertSee('Free demo class.');
            preg_match('#<meta name="description" content="([^"]*)"#', $this->get("/subjects/{$slug}")->getContent(), $d);
            $this->assertLessThanOrEqual(160, mb_strlen(html_entity_decode($d[1])), $slug);
            $this->assertStringEndsWith('Free demo class.', html_entity_decode($d[1]));
        }
    }

    public function test_after_launch_robots_txt_blocks_the_real_private_paths_but_not_the_site(): void
    {
        config(['site.indexable' => true]);
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        foreach (['/dashboard', '/admin', '/two-factor', '/email/', '/profile/', '/login', '/register', '/forgot-password', '/reset-password', '/*/demo'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}", $robots);
        }
        $this->assertDoesNotMatchRegularExpression('#^Disallow: /$#m', $robots, 'the whole site must not be blocked');
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $robots);
    }

    public function test_the_sitemap_lists_no_private_or_form_pages(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (['/login', '/register', '/dashboard', '/admin', '/demo', '/contact/sent', '/forgot-password'] as $path) {
            $this->assertStringNotContainsString($path, $sitemap);
        }
    }

    public function test_pages_for_logged_in_families_are_kept_out_of_search(): void
    {
        $slug = \App\Models\TutorProfile::approved()->first()->slug;

        $this->get("/tutors/{$slug}/demo")->assertSee('<meta name="robots" content="noindex">', false);
        $this->get('/login')->assertSee('<meta name="robots" content="noindex">', false);
        $this->get("/tutors/{$slug}")->assertDontSee('<meta name="robots" content="noindex">', false);
    }
}
