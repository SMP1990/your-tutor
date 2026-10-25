<?php

namespace Tests\Feature;

use Tests\Concerns\SeedsDemoContent;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use SeedsDemoContent;

    public function test_login_pages_load(): void
    {
        $pages = [
            '/login' => 'Welcome back',
            '/register' => 'Create your account',
            '/forgot-password' => 'Forgot your password?',
        ];

        foreach ($pages as $url => $heading) {
            $this->get($url)->assertOk()->assertSee($heading);
        }
    }

    public function test_header_links_to_login_and_register(): void
    {
        $this->get('/')->assertSee(route('login'))->assertSee(route('register'));
    }

    public function test_become_a_tutor_links_to_tutor_registration(): void
    {
        $this->get('/become-a-tutor')->assertSee(route('register', ['role' => 'tutor']));
        $this->get('/register?role=tutor')->assertSee('value="tutor" checked', false);
    }

    public function test_temporary_preview_pages_are_gone(): void
    {
        foreach (['/verify-phone', '/preview/student', '/preview/parent', '/preview/tutor', '/preview/admin'] as $url) {
            $this->get($url)->assertNotFound();
        }

        $this->get('/login')->assertDontSee('Preview only');
    }
}
