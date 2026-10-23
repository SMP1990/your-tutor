<?php

namespace App\Console\Commands;

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

        File::copyDirectory(public_path('images'), "{$out}/images");
        File::copyDirectory(public_path('build'), "{$out}/build");
        $this->info("Exported to {$out}");

        return self::SUCCESS;
    }

    /** @return array<string, string> URL path => file name */
    private function pages(): array
    {
        $pages = ['/' => 'index.html', '/tutors' => 'tutors.html'];
        foreach (DemoTutors::all() as $tutor) {
            $pages["/tutors/{$tutor['slug']}"] = "tutor-{$tutor['slug']}.html";
        }

        return $pages;
    }

    private function relativeLinks(string $html): string
    {
        $pages = $this->pages();
        $root = preg_quote(self::ROOT, '#');

        return preg_replace_callback("#(href|src|action)=\"{$root}([^\"]*)\"#", function ($m) use ($pages) {
            [$path, $hash] = array_pad(explode('#', $m[2], 2), 2, null);
            $path = strtok($path, '?') ?: '/';
            $file = $pages[$path] ?? ltrim($path, '/');

            return $m[1].'="'.$file.($hash !== null ? '#'.$hash : '').'"';
        }, $html);
    }
}
