<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\SessionYear;
use App\Models\User;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class SchoolYearWorkspaceTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->superAdmin()->givePermissionTo(['session-year-list', 'session-year-edit', 'session-year-create', 'session-year-delete']);
    }

    public function test_year_calendar_edit_and_cleanup_have_separate_authorised_surfaces(): void
    {
        $year = SessionYear::create(['name' => 'Prepared UI year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        $this->actingAs($this->superAdmin())->get('/session-years')->assertOk()->assertSee('Prepared UI year')->assertDontSee('deleteSessionModal');
        $this->get('/session-years?view=calendar&year='.$year->id)->assertOk()->assertSee('Calendar and terms')->assertDontSee('name="clear_data[]"', false);
        $this->get('/session-years?view=edit&year='.$year->id)->assertOk()->assertSee('Edit prepared year dates')->assertDontSee('name="default"', false);
        $this->get('/session-years?view=cleanup&year='.$year->id)->assertOk()->assertSee('Review selected data categories')->assertSee('name="clear_data[]"', false)->assertDontSee('name="set_as_current"', false);
        $this->assertNotSame((int) $year->id, app(SessionYearContext::class)->currentId());
    }

    public function test_school_year_context_does_not_expose_foreign_school_records(): void
    {
        $foreign = app(CurrentSchool::class)->run(2, fn () => SessionYear::create(['name' => 'Private Baobab year', 'start_date' => '2028-01-01', 'end_date' => '2028-12-31']));
        $this->actingAs($this->superAdmin())->get('/session-years')->assertOk()->assertDontSee('Private Baobab year');
        foreach (['calendar', 'edit', 'cleanup'] as $view) {
            $this->get('/session-years?view='.$view.'&year='.$foreign->id)->assertNotFound();
        }
    }

    public function test_edit_cannot_activate_and_current_year_cannot_be_cleared(): void
    {
        $current = app(SessionYearContext::class)->currentId();
        $year = SessionYear::create(['name' => 'Prepared UI year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        $this->actingAs($this->superAdmin())->putJson('/session-years/'.$year->id, ['id' => $year->id, 'name' => $year->name, 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'default' => 1])->assertUnprocessable();
        $this->postJson('/session-years/'.$current.'/clear-specific', ['clear_data' => ['attendance']])->assertStatus(409);
        $this->assertSame($current, app(SessionYearContext::class)->currentId());
    }

    public function test_a_person_without_year_permissions_cannot_open_editor_or_cleanup(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $teacher->givePermissionTo('session-year-list');
        $year = SessionYear::create(['name' => 'Prepared UI year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        $this->actingAs($teacher)->get('/session-years?view=edit&year='.$year->id)->assertForbidden();
        $this->get('/session-years?view=cleanup&year='.$year->id)->assertForbidden();
    }
}
