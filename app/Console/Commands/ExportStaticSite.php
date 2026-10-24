<?php

namespace App\Console\Commands;

use App\Http\Middleware\SecurityHeaders;
use App\Support\DemoSubjects;
use App\Support\DemoTutors;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

/**
 * Renders the public pages to plain HTML for the static preview on shared hosting.
 * Temporary: remove once the Laravel app itself is deployed.
 *
 * Links between pages are relative, so the files work on any domain. --url is only used where
 * search engines and link previews need a full address (canonical, og:image, sitemap.xml).
 */
class ExportStaticSite extends Command
{
    protected $signature = 'site:export
        {--path= : Output folder (default: static-export)}
        {--url= : Public address of the site, e.g. https://example.com (for canonical, link previews and sitemap)}';

    protected $description = 'Export the public pages as a static HTML site with relative links';

    private const ROOT = 'http://export.local';

    private ?string $site = null;

    public function handle(Kernel $kernel): int
    {
        $out = $this->option('path') ?: base_path('static-export');
        $this->site = rtrim((string) $this->option('url'), '/') ?: null;
        File::deleteDirectory($out);
        File::ensureDirectoryExists($out);

        URL::forceRootUrl(self::ROOT);

        foreach ($this->pages() as $path => $file) {
            $response = $kernel->handle(Request::create(self::ROOT.$path));
            if ($response->getStatusCode() !== 200) {
                $this->error("{$path} returned {$response->getStatusCode()}");

                return self::FAILURE;
            }
            File::put("{$out}/{$file}", $this->convert($response->getContent()));
            $this->line("  {$path} -> {$file}");
        }

        // 404 page: shown for any missing URL, so its links start at the site root ("/").
        $missing = $kernel->handle(Request::create(self::ROOT.'/__missing__'));
        File::put("{$out}/404.html", $this->convert($missing->getContent(), '/'));
        File::put("{$out}/.htaccess", $this->htaccess());

        File::put("{$out}/robots.txt", $this->absolute($kernel->handle(Request::create(self::ROOT.'/robots.txt'))->getContent()));
        if ($this->site) {
            File::put("{$out}/sitemap.xml", $this->absolute($kernel->handle(Request::create(self::ROOT.'/sitemap.xml'))->getContent()));
        } else {
            $this->warn('  No --url given: sitemap.xml skipped, link previews use relative image paths.');
        }
        $this->line('  404.html, .htaccess, robots.txt'.($this->site ? ', sitemap.xml' : ''));

        File::copyDirectory(public_path('images'), "{$out}/images");
        File::copyDirectory(public_path('build'), "{$out}/build");
        File::copy(public_path('favicon.svg'), "{$out}/favicon.svg");
        $this->info("Exported to {$out}");

        return self::SUCCESS;
    }

    /** @return array<string, string> URL path => file name */
    private function pages(): array
    {
        $pages = [
            '/' => 'index.html',
            '/tutors' => 'tutors.html',
            '/how-it-works' => 'how-it-works.html',
            '/pricing' => 'pricing.html',
            '/become-a-tutor' => 'become-a-tutor.html',
            '/about' => 'about.html',
            '/faqs' => 'faqs.html',
            '/contact' => 'contact.html',
            '/contact/sent' => 'contact-sent.html',
            '/terms' => 'terms.html',
            '/privacy' => 'privacy.html',
            '/refund-policy' => 'refund-policy.html',
            '/subjects' => 'subjects.html',
            '/login' => 'login.html',
            '/register' => 'register.html',
            '/verify-phone' => 'verify-phone.html',
            '/forgot-password' => 'forgot-password.html',
            '/preview/student' => 'dashboard-student.html',
            '/preview/parent' => 'dashboard-parent.html',
            '/preview/tutor' => 'dashboard-tutor.html',
            '/preview/admin' => 'dashboard-admin.html',
        ];
        foreach (DemoSubjects::all() as $subject) {
            $pages["/subjects/{$subject['slug']}"] = "subject-{$subject['slug']}.html";
        }
        foreach (DemoTutors::all() as $tutor) {
            $pages["/tutors/{$tutor['slug']}"] = "tutor-{$tutor['slug']}.html";
            $pages["/tutors/{$tutor['slug']}/demo"] = "demo-{$tutor['slug']}.html";
            $pages["/tutors/{$tutor['slug']}/demo/sent"] = "demo-{$tutor['slug']}-sent.html";
        }

        return $pages;
    }

    private function convert(string $html, string $prefix = ''): string
    {
        $root = preg_quote(self::ROOT, '#');

        $html = preg_replace('#<input type="hidden" name="_token"[^>]*>#', '', $html);

        // Login forms: drop every field name so no email, phone or password ever lands in a URL,
        // and only move on to the next page (register -> verify phone).
        $html = preg_replace_callback("#<form class=\"auth-form\" method=\"post\" action=\"{$root}([^\"]*)\"[^>]*>(.*?)</form>#s", function ($m) {
            $next = $m[1] === '/register' ? '/verify-phone' : $m[1];
            // Keep names on radios/checkboxes (role, consent): they hold no personal data.
            $body = preg_replace_callback('#<input(?![^>]*type="(?:radio|checkbox)")[^>]*>#', fn ($i) => preg_replace('# name="[^"]*"#', '', $i[0]), $m[2]);

            return '<form class="auth-form" method="get" action="'.self::ROOT.$next.'">'.$body.'</form>';
        }, $html);

        // A static host cannot process POST forms: send them to their "sent" page instead.
        $html = preg_replace("#method=\"post\" action=\"({$root}[^\"]*)\"#", 'method="get" action="$1/sent"', $html);
        $html = str_replace(' novalidate>', '>', $html); // let the browser check required fields

        // Canonical must be a full address when we know the domain.
        $html = preg_replace_callback("#(rel=\"canonical\" href=\"){$root}([^\"]*)\"#", fn ($m) => $m[1].$this->file($m[2], $this->site ? $this->site.'/' : $prefix).'"', $html);

        // Links between pages: relative, so the files work on any domain.
        $html = preg_replace_callback("#(href|src|action)=\"{$root}([^\"]*)\"#", fn ($m) => $m[1].'="'.$this->file($m[2], $prefix).'"', $html);

        // Everything else (og:url, og:image, structured data) needs a full address.
        return $this->absolute($html, $prefix);
    }

    private function absolute(string $text, string $fallbackPrefix = ''): string
    {
        $root = preg_quote(self::ROOT, '#');

        return preg_replace_callback("#{$root}(/[^\"'<\s]*)?#", fn ($m) => $this->file($m[1] ?? '/', $this->site ? $this->site.'/' : $fallbackPrefix), $text);
    }

    /** Turns a site path (with optional ?query and #hash) into the exported file name. */
    private function file(string $url, string $prefix): string
    {
        [$url, $hash] = array_pad(explode('#', $url, 2), 2, null);
        [$path, $query] = array_pad(explode('?', $url, 2), 2, null);
        $pages = $this->pages();
        $file = $pages[$path ?: '/'] ?? ltrim($path, '/');
        if ($file === 'index.html' && $prefix !== '' && $prefix !== '/') {
            $file = ''; // https://example.com/ rather than .../index.html
        }

        return $prefix.$file.($query !== null ? '?'.$query : '').($hash !== null ? '#'.$hash : '');
    }

    private function htaccess(): string
    {
        $headers = SecurityHeaders::HEADERS + [
            'Content-Security-Policy' => SecurityHeaders::CSP,
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        ];
        $lines = ['ErrorDocument 404 /404.html', '', '# Always use HTTPS', 'RewriteEngine On',
            'RewriteCond %{HTTPS} !=on', 'RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]', '',
            '# Block hidden files such as .git (but not .well-known, used for SSL certificates)',
            'RewriteCond %{REQUEST_URI} !^/\.well-known/', 'RewriteRule (^|/)\. - [F]', '', '<IfModule mod_headers.c>'];
        foreach ($headers as $name => $value) {
            $lines[] = "    Header always set {$name} \"{$value}\"";
        }
        $lines[] = '</IfModule>';

        return implode("\n", $lines)."\n";
    }
}
