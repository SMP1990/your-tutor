<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phones: the dashboard menu is one "Menu" button, and wide tables turn into cards instead of hiding columns. */
class SmallScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_dashboard_has_a_menu_button_that_names_the_current_page(): void
    {
        $admin = User::factory()->admin()->create();
        $html = $this->actingAs($admin)->withSession(['two_factor_passed' => true])->get('/admin/users')->assertOk()->getContent();

        $this->assertStringContainsString('<input type="checkbox" id="dash-menu-open" class="dash-nav__check"', $html);
        $this->assertMatchesRegularExpression('#<label for="dash-menu-open" class="dash-nav__toggle">.*?<strong>Users</strong>#s', $html);

        foreach (['/dashboard/parent' => User::factory()->parent()->create(), '/dashboard/student' => User::factory()->create()] as $url => $user) {
            $this->actingAs($user)->get($url)->assertOk()->assertSee('dash-menu-open', false)->assertSee('<strong>Overview</strong>', false);
        }
    }

    public function test_the_menu_still_lists_every_link_without_javascript(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->withSession(['two_factor_passed' => true])->get('/admin')
            ->assertSee(route('admin.users.index'), false)->assertSee(route('admin.complaints.index'), false)->assertSee(route('admin.settings.edit'), false);
    }

    public function test_the_css_and_js_turn_table_rows_into_labelled_cards_on_phones(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $js = file_get_contents(resource_path('js/site.js'));

        $this->assertMatchesRegularExpression('/@media \(max-width: 639px\) \{[^@]*\.data-table td::before \{ content: attr\(data-label\)/s', $css);
        $this->assertStringContainsString("table.data-table", $js);
        $this->assertStringContainsString('cell.dataset.label', $js);
        // the old phone behaviour (a strip that scrolls sideways and hides most links) must not come back
        $this->assertDoesNotMatchRegularExpression('/\.dash-nav \{[^}]*overflow-x: auto/', $css);
    }

    public function test_every_admin_table_has_header_cells_so_the_cards_get_their_labels(): void
    {
        foreach (glob(resource_path('views/admin/**/*.blade.php')) + glob(resource_path('views/dashboard/admin.blade.php')) as $view) {
            $source = file_get_contents($view);
            if (str_contains($source, '<table class="data-table"')) {
                $this->assertStringContainsString('<thead>', $source, basename($view).' needs a <thead> (its names become the card labels)');
                $this->assertStringContainsString('<th scope="col"', $source, basename($view));
            }
        }
    }
}
