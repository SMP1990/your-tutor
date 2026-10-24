<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

class StaticExportTest extends TestCase
{
    use SeedsDemoContent;

    private string $out;

    protected function setUp(): void
    {
        parent::setUp();
        $this->out = storage_path('framework/testing/static-export');
        $this->artisan('site:export', ['--path' => $this->out, '--url' => 'https://example.com'])->assertSuccessful();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->out);
        parent::tearDown();
    }

    public function test_login_forms_never_put_personal_data_in_the_url(): void
    {
        foreach (['register', 'login', 'forgot-password', 'verify-phone'] as $page) {
            $html = File::get("{$this->out}/{$page}.html");
            preg_match('#<form class="auth-form"[^>]*>(.*?)</form>#s', $html, $form);

            $this->assertNotEmpty($form, "{$page}: auth form not found");
            $this->assertStringContainsString('method="get"', $form[0]);
            foreach (['name', 'email', 'phone', 'password', 'login', 'code', '_token'] as $field) {
                $this->assertStringNotContainsString("name=\"{$field}\"", $form[1], "{$page} still sends {$field}");
            }
        }
    }

    public function test_export_has_relative_links_and_no_internal_addresses(): void
    {
        foreach (File::glob("{$this->out}/*.html") as $file) {
            $this->assertStringNotContainsString('export.local', File::get($file), basename($file));
        }
        $this->assertStringContainsString('href="tutors.html"', File::get("{$this->out}/index.html"));
        $this->assertFileExists("{$this->out}/.htaccess");
        $this->assertFileExists("{$this->out}/sitemap.xml");
    }
}
