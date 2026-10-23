<?php

namespace Tests\Feature;

use Tests\TestCase;

class InfoPagesTest extends TestCase
{
    public function test_info_and_legal_pages_load(): void
    {
        $pages = [
            '/about' => 'Good teaching, wherever you live',
            '/faqs' => 'Frequently asked questions',
            '/contact' => 'We are here to help',
            '/terms' => 'Terms &amp; Conditions',
            '/privacy' => 'Privacy Policy',
            '/refund-policy' => 'Refund Policy',
        ];

        foreach ($pages as $url => $text) {
            $this->get($url)->assertOk()->assertSee($text, false);
        }
    }

    public function test_legal_pages_are_marked_as_draft(): void
    {
        $this->get('/terms')->assertSee('has not yet been reviewed by a lawyer');
    }

    public function test_footer_links_to_info_pages(): void
    {
        $response = $this->get('/');

        foreach (['about', 'contact', 'faqs', 'terms', 'privacy', 'refund-policy'] as $route) {
            $response->assertSee(route($route));
        }
    }

    public function test_contact_form_accepts_valid_message(): void
    {
        $this->post('/contact', [
            'name' => 'Sadia',
            'phone' => '0300 1234567',
            'topic' => 'Finding a tutor',
            'message' => 'I need a Physics tutor for my son.',
        ])->assertRedirect(route('contact.sent'));
    }

    public function test_contact_form_needs_phone_or_email(): void
    {
        $this->post('/contact', [
            'name' => 'Sadia',
            'topic' => 'Finding a tutor',
            'message' => 'I need a Physics tutor for my son.',
        ])->assertSessionHasErrors(['phone', 'email']);
    }

    public function test_whatsapp_button_falls_back_to_contact_page(): void
    {
        config(['site.whatsapp_number' => null]);
        $this->get('/')->assertSee('class="wa-button"', false)->assertSee(route('contact'));

        config(['site.whatsapp_number' => '923001234567']);
        $this->get('/')->assertSee('https://wa.me/923001234567');
    }

    public function test_missing_page_shows_custom_404(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found');
    }
}
