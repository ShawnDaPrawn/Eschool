<?php

namespace Tests\Feature;

use App\Models\ClassSchool;
use App\Models\InstallmentFee;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Shift;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Setting up a school from a fresh install, as found by driving the admin
 * panel in a browser: session years, classes, shifts and staff roles.
 */
class SchoolSetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_school_year_creation_ignores_retired_installment_inputs(): void
    {
        // include_fee_installments wasn't fillable: the installments were saved, the flag wasn't.
        $this->actingAs($this->superAdmin())
            ->postJson('/session-years', $this->newYear('2031', [
                'fees_installment' => '1',
                'installment_data' => [1 => ['name' => 'Term 1', 'due_date' => '31-03-2031', 'due_charges' => '2']],
            ]))
            ->assertOk();

        $year = SessionYear::where('name', '2031')->firstOrFail();
        $this->assertSame(0, (int) $year->include_fee_installments);
        $this->assertSame(0, InstallmentFee::where('session_year_id', $year->id)->count());
    }

    public function test_session_year_names_are_unique(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/session-years', $this->newYear('2031'))->assertOk();

        // The page reloaded before saying it had worked, so people submitted twice.
        $this->postJson('/session-years', $this->newYear('2031'))
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', trans('session_year_name_already_exists'));
        $this->assertSame(1, SessionYear::where('name', '2031')->count());
    }

    public function test_a_new_session_year_can_be_made_the_current_one_when_it_is_created(): void
    {
        // The installer's only year (2022-23) has ended; without this, everything set up
        // afterwards was saved under it.
        $this->actingAs($this->superAdmin())
            ->postJson('/session-years', $this->newYear('2031', ['set_as_current' => '1']))
            ->assertOk();

        $year = SessionYear::where('name', '2031')->firstOrFail();
        $this->assertCurrentYear($year);
        $this->assertSame($year->id, session('session_year'), "The panel's year picker follows the new current year");
    }

    public function test_an_ongoing_session_year_can_be_made_current_but_an_ended_one_cannot(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/session-years', $this->newYear('2031'))->assertOk();
        $year = SessionYear::where('name', '2031')->firstOrFail();
        $installed = SessionYear::where('name', '2022-23')->firstOrFail();

        $this->postJson("/session-years/{$year->id}/make-current")->assertJson(['error' => false]);
        $this->assertCurrentYear($year);

        $this->postJson("/session-years/{$installed->id}/make-current")
            ->assertJson(['error' => true, 'message' => trans('session_year_has_ended')]);
        $this->assertCurrentYear($year);
    }

    public function test_the_running_session_year_can_be_edited_after_its_free_app_date(): void
    {
        $year = SessionYear::where('name', '2022-23')->firstOrFail();
        $year->forceFill([
            'start_date' => now()->subMonths(3)->toDateString(), 'end_date' => now()->addMonths(8)->toDateString(),
            'fee_due_date' => now()->addMonth()->toDateString(), 'free_app_use_date' => now()->subDays(10)->toDateString(),
        ])->save();
        $form = [
            'id' => $year->id, 'name' => 'This year', 'start_date' => now()->subMonths(3)->format('d-m-Y'),
            'end_date' => now()->addMonths(8)->format('d-m-Y'), 'fees_due_date' => now()->addMonth()->format('d-m-Y'),
            'fees_due_charges' => '0', 'free_app_use_date' => now()->subDays(10)->format('d-m-Y'),
        ];

        // Keeping the (past) free-app date used to fail "must be a date after today".
        $this->actingAs($this->superAdmin())->putJson("/session-years/{$year->id}", $form)->assertJson(['error' => false]);
        $this->assertSame('This year', $year->fresh()->name);

        // Retired fee-lock dates cannot change or block the academic year.
        $this->putJson("/session-years/{$year->id}", ['free_app_use_date' => now()->subDays(3)->format('d-m-Y')] + $form)
            ->assertJson(['error' => false]);
        $this->assertSame(now()->subDays(10)->toDateString(), $year->fresh()->free_app_use_date);
    }

    public function test_the_migration_flags_existing_years_that_have_installments(): void
    {
        $year = SessionYear::where('name', '2022-23')->firstOrFail();
        InstallmentFee::insert(['name' => 'Term 1', 'due_date' => '2023-01-31', 'due_charges' => 2, 'session_year_id' => $year->id]);
        $this->assertSame(0, (int) $year->fresh()->include_fee_installments);

        (require database_path('migrations/2026_10_21_000000_flag_session_years_that_have_fee_installments.php'))->up();

        $this->assertSame(1, (int) $year->fresh()->include_fee_installments);
    }

    public function test_class_names_can_be_written_as_schools_write_them(): void
    {
        $medium = SchoolTable::query('mediums')->insertGetId(['name' => 'English']);
        $section = SchoolTable::query('sections')->insertGetId(['name' => 'A']);
        $this->actingAs($this->superAdmin());

        // Only [A-Za-z0-9_] was allowed, so "Grade 1" was refused.
        foreach (['Grade 1', 'Grade R-A', "Gr. 7 (St Mary's)"] as $name) {
            $this->postJson('/class', ['name' => $name, 'medium_id' => $medium, 'section_id' => [$section]])
                ->assertJson(['error' => false]);
            $this->assertTrue(ClassSchool::where('name', $name)->exists(), $name);
        }

        $this->postJson('/class', ['name' => '<b>Grade 2</b>', 'medium_id' => $medium, 'section_id' => [$section]])
            ->assertJson(['error' => true, 'message' => trans('name_has_invalid_characters')]);
    }

    public function test_shift_names_can_have_spaces_and_an_edit_cannot_end_before_it_starts(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/shifts', ['name' => 'Morning Shift', 'start_time' => '07:30', 'end_time' => '13:00', 'status' => 1])
            ->assertJson(['error' => false]);
        $shift = Shift::where('title', 'Morning Shift')->firstOrFail();

        // The end time was compared with a field the edit form doesn't send.
        $this->putJson("/shifts/{$shift->id}", ['edit_name' => 'Morning Shift', 'edit_start_time' => '13:00', 'edit_end_time' => '07:30', 'status' => 1])
            ->assertJson(['error' => true]);
        $this->assertSame('13:00:00', $shift->fresh()->end_time);
    }

    public function test_staff_can_be_given_any_custom_role(): void
    {
        foreach (range(1, 7) as $i) {
            Role::create(['name' => "Office role $i", 'custom_role' => 1]);
        }

        // Only the five newest were offered.
        $page = $this->actingAs($this->superAdmin())->get('/staff')->assertOk();
        foreach (range(1, 7) as $i) {
            $page->assertSee("Office role $i");
        }
    }

    public function test_the_class_subject_edit_link_keeps_the_listed_session_year(): void
    {
        $medium = SchoolTable::query('mediums')->insertGetId(['name' => 'English']);
        $section = SchoolTable::query('sections')->insertGetId(['name' => 'A']);
        $this->actingAs($this->superAdmin())->postJson('/session-years', $this->newYear('2031'))->assertOk();
        $year = SessionYear::where('name', '2031')->firstOrFail();
        $this->postJson('/class', ['name' => 'Grade 1', 'medium_id' => $medium, 'section_id' => [$section]])->assertJson(['error' => false]);

        // The edit page showed the default year's subjects but saved to the listed year.
        $row = $this->getJson("/class/subject/list?session_year_id={$year->id}")->json('rows.0');
        $this->assertStringContainsString("session_year_id={$year->id}", $row['operate']);
        $this->get(route('class-subject-edit.index', ['id' => $row['id'], 'session_year_id' => $year->id]))
            ->assertOk()
            ->assertSee('name="session_year_id" value="'.$year->id.'"', false)
            ->assertSee('2031');
    }

    public function test_forms_say_what_to_create_first_on_a_fresh_install(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get('/subject')->assertSee(trans('before_using_this_form_create'))->assertSee(url('medium'));
        $this->get('/class')->assertSee(url('medium'))->assertSee(url('section'));
        $this->get('/staff')->assertSee(url('roles/create'));
    }

    /** Form fields for a new session year, as the create modal sends them. */
    private function newYear(string $name, array $extra = []): array
    {
        $year = (int) $name;

        return $extra + [
            'name' => $name, 'start_date' => "01-01-$year", 'end_date' => "31-12-$year",
            'fees_due_date' => "31-03-$year", 'fees_due_charges' => '5', 'fees_installment' => '0',
        ];
    }

    private function assertCurrentYear(SessionYear $year): void
    {
        $this->assertSame((string) $year->id, Settings::where('type', 'session_year')->value('message'));
        $this->assertSame([$year->id], SessionYear::where('default', 1)->pluck('id')->all());
    }
}
