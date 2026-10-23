<?php

namespace Tests\Feature;

use Tests\TestCase;

class DemoBookingTest extends TestCase
{
    private function validData(array $overrides = []): array
    {
        return $overrides + [
            'for' => 'child',
            'name' => 'Sadia Malik',
            'student_name' => 'Ahmed',
            'phone' => '0300 123-4567',
            'subject' => 'Physics',
            'grade' => 'FSc / A Level',
            'day' => 'Sat',
            'time' => 'morning',
            'contact_ok' => '1',
        ];
    }

    public function test_demo_form_loads(): void
    {
        $this->get('/tutors/ayesha-r/demo')
            ->assertOk()
            ->assertSee('Book a free demo class')
            ->assertSee('Ayesha R.');
    }

    public function test_profile_and_cards_link_to_the_demo_form(): void
    {
        $this->get('/tutors/ayesha-r')->assertSee(route('tutors.demo', 'ayesha-r'));
        $this->get('/tutors')->assertSee(route('tutors.demo', 'usman-f'));
    }

    public function test_valid_request_redirects_to_sent_page(): void
    {
        $this->post('/tutors/ayesha-r/demo', $this->validData())
            ->assertRedirect(route('tutors.demo.sent', 'ayesha-r'));

        $this->get(route('tutors.demo.sent', 'ayesha-r'))->assertOk()->assertSee('Request sent');
    }

    public function test_student_name_not_needed_when_booking_for_self(): void
    {
        $this->post('/tutors/ayesha-r/demo', $this->validData(['for' => 'self', 'student_name' => '']))
            ->assertSessionHasNoErrors();
    }

    public function test_invalid_request_shows_errors(): void
    {
        $this->post('/tutors/ayesha-r/demo', $this->validData([
            'phone' => '12345',
            'subject' => 'Chemistry',   // Ayesha does not teach it
            'day' => 'Fri',             // Ayesha is not free on Friday
            'student_name' => '',
            'contact_ok' => '',
        ]))->assertSessionHasErrors(['phone', 'subject', 'day', 'student_name', 'contact_ok']);
    }

    public function test_unknown_tutor_demo_returns_404(): void
    {
        $this->get('/tutors/nobody/demo')->assertNotFound();
    }
}
