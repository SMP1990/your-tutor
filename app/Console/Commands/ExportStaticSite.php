<?php

namespace App\Console\Commands;

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
 */
class ExportStaticSite extends Command
{
    protected $signature = 'site:export {--path= : Output folder (default: static-export)}';

    protected $description = 'Export the public pages as a static HTML site with relative links';

    private const ROOT = 'http://export.local';

    public function handle(Kernel $kernel): int
    {
        $out = $this->option('path') ?: base_path('static-export');
        File::deleteDirectory($out);
        File::ensureDirectoryExists($out);

        URL::forceRootUrl(self::ROOT);

        foreach ($this->pages() as $path => $file) {
            $response = $kernel->handle(Request::create(self::ROOT.$path));
            if ($response->getStatusCode() !== 200) {
                $this->error("{$path} returned {$response->getStatusCode()}");

                return self::FAILURE;
            }
            File::put("{$out}/{$file}", $this->relativeLinks($response->getContent()));
            $this->line("  {$path} -> {$file}");
        }

        // 404 page: shown for any missing URL, so its links start at the site root ("/").
        $missing = $kernel->handle(Request::create(self::ROOT.'/__missing__'));
        File::put("{$out}/404.html", $this->relativeLinks($missing->getContent(), '/'));
        File::put("{$out}/.htaccess", "ErrorDocument 404 /404.html\n");
        $this->line('  404 page -> 404.html + .htaccess');

        File::copyDirectory(public_path('images'), "{$out}/images");
        File::copyDirectory(public_path('build'), "{$out}/build");
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
            '/student' => 'dashboard-student.html',
            '/parent' => 'dashboard-parent.html',
            '/tutor' => 'dashboard-tutor.html',
            '/admin' => 'dashboard-admin.html',
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

    private function relativeLinks(string $html, string $prefix = ''): string
    {
        $pages = $this->pages();
        $root = preg_quote(self::ROOT, '#');

        $html = preg_replace('#<input type="hidden" name="_token"[^>]*>#', '', $html);

        // Login forms: drop every field name so no email, phone or password ever lands in a URL,
        // and only move on to the next page (register -> verify phone).
        $html = preg_replace_callback("#<form class=\"auth-form\" method=\"post\" action=\"{$root}([^\"]*)\">(.*?)</form>#s", function ($m) {
            $next = $m[1] === '/register' ? '/verify-phone' : $m[1];
            // Keep names on radios/checkboxes (role, consent): they hold no personal data.
            $body = preg_replace_callback('#<input(?![^>]*type="(?:radio|checkbox)")[^>]*>#', fn ($i) => preg_replace('# name="[^"]*"#', '', $i[0]), $m[2]);

            return '<form class="auth-form" method="get" action="'.self::ROOT.$next.'">'.$body.'</form>';
        }, $html);

        // A static host cannot process POST forms: send them to their "sent" page instead.
        $html = preg_replace("#method=\"post\" action=\"({$root}[^\"]*)\"#", 'method="get" action="$1/sent"', $html);
        $html = preg_replace('#<input type="hidden" name="_token"[^>]*>#', '', $html);
        $html = str_replace(' novalidate>', '>', $html); // let the browser check required fields

        return preg_replace_callback("#(href|src|action)=\"{$root}([^\"]*)\"#", function ($m) use ($pages, $prefix) {
            [$url, $hash] = array_pad(explode('#', $m[2], 2), 2, null);
            [$path, $query] = array_pad(explode('?', $url, 2), 2, null);
            $file = $pages[$path ?: '/'] ?? ltrim($path, '/');

            return $m[1].'="'.$prefix.$file.($query !== null ? '?'.$query : '').($hash !== null ? '#'.$hash : '').'"';
        }, $html);
    }
}
