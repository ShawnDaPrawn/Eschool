<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\Announcement;
use App\Models\Mediums;
use App\Models\SessionYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A year other than the current one is read-only in the admin panel
 * (EnforceReadOnlyYears): writes are refused on the server, the year switch,
 * sign-out, the profile and year-independent settings still work, and lists
 * follow the selected year.
 */
class ReadOnlyYearsTest extends TestCase
{
    use RefreshDatabase;

    private SessionYear $current;

    private SessionYear $past;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        $this->current = SessionYear::findOrFail(getSettings('session_year')['session_year']);
        $this->past = SessionYear::create(['name' => '2021-22', 'start_date' => '2021-06-01', 'end_date' => '2022-04-30']);
    }

    public function test_writes_work_while_the_current_year_is_selected(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['session_year' => $this->current->id])
            ->postJson('/medium', ['name' => 'English'])
            ->assertOk()
            ->assertJson(['error' => false]);

        $this->assertDatabaseHas('mediums', ['name' => 'English']);
    }

    public function test_a_past_year_refuses_every_write_with_a_clear_message(): void
    {
        $medium = Mediums::forceCreate(['name' => 'isiZulu']);
        $admin = $this->actingAs($this->superAdmin())->withSession(['session_year' => $this->past->id]);

        $created = $admin->postJson('/medium', ['name' => 'English']);
        $created->assertStatus(423)->assertJson(['error' => true, 'code' => 423]);
        $this->assertStringContainsString('2021-22', $created->json('message'));
        $this->assertStringContainsString($this->current->name, $created->json('message'));

        $admin->putJson('/medium/'.$medium->id, ['id' => $medium->id, 'name' => 'Changed'])->assertStatus(423);
        $admin->deleteJson('/medium/'.$medium->id)->assertStatus(423);

        $this->assertDatabaseMissing('mediums', ['name' => 'English']);
        $this->assertDatabaseHas('mediums', ['id' => $medium->id, 'name' => 'isiZulu', 'deleted_at' => null]);
    }

    public function test_a_plain_form_post_in_a_past_year_goes_back_with_the_message_and_the_input(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['session_year' => $this->past->id])
            ->from('/medium')
            ->post('/medium', ['name' => 'English'])
            ->assertRedirect('/medium')
            ->assertSessionHasErrors('read_only_year')
            ->assertSessionHasInput('name', 'English');

        $this->assertDatabaseMissing('mediums', ['name' => 'English']);
    }

    public function test_switching_back_signing_out_and_the_profile_still_work_in_a_past_year(): void
    {
        $admin = $this->actingAs($this->superAdmin())->withSession(['session_year' => $this->past->id]);

        $admin->postJson('/checkPassword', ['old_password' => 'superadmin'])->assertOk();
        $admin->postJson('/set-session-year', ['session_year_id' => $this->current->id])->assertOk();
        $this->assertSame($this->current->id, (int) session('session_year'));

        // Back in the current year, writes work again.
        $this->postJson('/medium', ['name' => 'English'])->assertOk()->assertJson(['error' => false]);

        $this->withSession(['session_year' => $this->past->id])->post('/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_year_independent_settings_can_be_saved_in_a_past_year(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['session_year' => $this->past->id])
            ->post('/roles', ['name' => 'Finance', 'permission' => ['fees-paid']])
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'Finance']);
    }

    public function test_the_year_switch_form_without_javascript_goes_back(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['session_year' => $this->past->id])
            ->from('/announcement')
            ->post('/set-session-year', ['session_year_id' => $this->current->id])
            ->assertRedirect('/announcement');
    }

    public function test_lists_follow_the_selected_year(): void
    {
        $this->notice('Prize-giving last year', $this->past);
        $this->notice('Sports day this year', $this->current);
        $admin = $this->actingAs($this->superAdmin());

        $past = $admin->withSession(['session_year' => $this->past->id])->getJson('/announcement-list');
        $this->assertSame(['Prize-giving last year'], array_column($past->json('rows'), 'title'));

        $current = $admin->withSession(['session_year' => $this->current->id])->getJson('/announcement-list');
        $this->assertSame(['Sports day this year'], array_column($current->json('rows'), 'title'));

        // An explicit year in the request still wins.
        $explicit = $admin->withSession(['session_year' => $this->past->id])->getJson('/announcement-list?session_year_id='.$this->current->id);
        $this->assertSame(['Sports day this year'], array_column($explicit->json('rows'), 'title'));
        $this->assertArrayNotHasKey('session_year_id', $_GET, 'The selected year must not leak into $_GET after the request');
    }

    public function test_the_banner_shows_on_panel_pages_only_for_another_year(): void
    {
        $admin = $this->actingAs($this->superAdmin());

        $admin->withSession(['session_year' => $this->past->id])->get('/medium')
            ->assertOk()
            ->assertSee('data-read-only-year', false)
            ->assertSee('2021-22');

        $admin->withSession(['session_year' => $this->current->id])->get('/medium')
            ->assertOk()
            ->assertDontSee('data-read-only-year', false);
    }

    private function notice(string $title, SessionYear $year): void
    {
        Announcement::forceCreate(['title' => $title, 'description' => $title, 'table_type' => '', 'table_id' => null, 'session_year_id' => $year->id]);
    }
}
