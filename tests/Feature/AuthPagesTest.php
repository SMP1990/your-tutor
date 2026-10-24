<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    public function test_login_pages_load(): void
    {
        $pages = [
            '/login' => 'Welcome back',
            '/register' => 'Create your account',
            '/verify-phone' => 'Enter your code',
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

    public function test_phone_code_page_is_still_a_preview(): void
    {
        $this->from('/verify-phone')->post('/verify-phone', ['code' => '123456'])
            ->assertRedirect('/verify-phone')
            ->assertSessionHas('notice');

        $this->assertGuest();
    }
}
