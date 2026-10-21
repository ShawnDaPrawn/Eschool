<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The shared page components (resources/views/components/page/) keep the
 * panel's conventions: escaped tables, one primary action that respects
 * permissions, labelled fields wired to their errors, forms with the CSRF token
 * and a designed empty state.
 */
class PageComponentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_the_header_has_one_title_and_hides_the_primary_action_without_permission(): void
    {
        $this->actingAs($this->superAdmin());
        $admin = $this->blade('<x-page.header title="Notices" subtitle="What parents see" primary-label="Send a notice" primary-href="#form" primary-permission="announcement-create" />');
        $admin->assertSee('<h1 class="page-head-title">Notices</h1>', false);
        $admin->assertSee('What parents see');
        $admin->assertSee('href="#form"', false);
        $admin->assertSee('data-write', false);

        $teacher = User::forceCreate(['first_name' => 'T', 'last_name' => 'T', 'email' => 't@example.com', 'password' => Hash::make('secret123'), 'gender' => 'Male', 'mobile' => '0']);
        $this->actingAs($teacher);
        $this->blade('<x-page.header title="Notices" primary-label="Send a notice" primary-href="#form" primary-permission="announcement-create" />')
            ->assertDontSee('Send a notice');
    }

    public function test_the_table_escapes_cells_and_carries_its_empty_states(): void
    {
        $this->actingAs($this->superAdmin());
        $view = $this->blade(
            '<x-page.table id="notices" url="/announcement-list" toolbar="#filters" empty-title="No notices yet" empty-message="Send your first notice." empty-action-label="Send a notice" empty-action-href="#form">'
            .'<x-slot:columns><th data-field="title">Title</th></x-slot:columns>'
            .'<x-slot:bulk><button type="button" data-bulk-action="/bulk">Delete</button></x-slot:bulk>'
            .'</x-page.table>'
        );

        $view->assertSee('data-escape="true"', false);
        $view->assertSee('data-side-pagination="server"', false);
        $view->assertSee('data-toolbar="#filters"', false);
        $view->assertSee('data-page-table-init', false);
        $view->assertDontSee('data-toggle="table"', false);
        $view->assertSee('data-bulk-bar="notices"', false);
        $view->assertSee('data-empty-template="notices"', false);
        $view->assertSee('No notices yet');
        $view->assertSee('Send your first notice.');
        $view->assertSee('data-no-matches-template="notices"', false);
    }

    public function test_fields_are_labelled_and_wired_to_their_hint_and_error(): void
    {
        $view = $this->blade('<x-page.field name="start_date" type="date" label="Starts on" help="The first school day" required />');

        $view->assertSee('<label for="field-start-date">', false);
        $view->assertSee('id="field-start-date"', false);
        $view->assertSee('aria-describedby="field-start-date-help field-start-date-error"', false);
        $view->assertSee('id="field-start-date-error" class="invalid-feedback"', false);
        $view->assertSee('required', false);

        $select = $this->blade('<x-page.field name="grade" type="select" label="Grade" :options="[1 => \'Grade <5>\']" placeholder="Choose" />');
        $select->assertSee('Grade &lt;5&gt;', false);
        $select->assertDontSee('Grade <5>', false);
    }

    public function test_the_form_card_posts_with_the_token_and_saves_without_a_reload(): void
    {
        $view = $this->blade('<x-page.form-card id="year" action="/new-year/year" method="PUT" title="Next year" submit-label="Create" reset-on-success>fields</x-page.form-card>');

        $view->assertSee('data-page-form', false);
        $view->assertSee('name="_token"', false);
        $view->assertSee('name="_method" value="PUT"', false);
        $view->assertSee('data-form-summary', false);
        $view->assertSee('data-draft="year"', false);
        $view->assertSee('data-reset-on-success', false);
        $view->assertSee('aria-labelledby="year-title"', false);
    }

    public function test_every_panel_page_has_the_confirm_dialog_and_page_script(): void
    {
        $this->superAdmin()->update(['password' => Hash::make('components-test')]);

        $this->actingAs($this->superAdmin())->get('/medium')->assertOk()
            ->assertSee('id="page-confirm"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('assets/js/custom/page.js', false);
    }
}
