<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Actions\Transfers\TransferAwardWindows;
use App\Actions\Transfers\TransferBellSchedules;
use App\Actions\Transfers\TransferDisciplineFramework;
use App\Actions\Transfers\TransferHouseMemberships;
use App\Models\Academics\BellSchedule;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Discipline\DisciplineFramework;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardWindow;
use App\Models\Recognition\House;
use App\Models\Recognition\HouseMembership;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\Academics\NewYearService;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Discipline\DisciplineFrameworks;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Year-end carries bell schedules, house memberships (the one structure that
 * keeps its people), award windows and the discipline framework.
 * Specification: engineering/specs/academics/new-year-structures.md.
 */
class NewYearCarryTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    private int $next;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->next = (int) SchoolTable::query('session_years')->insertGetId([
            'name' => '2027', 'start_date' => '2027-01-13', 'end_date' => '2027-12-08', 'free_app_use_date' => '2027-01-13', 'fee_due_date' => '2027-02-13', 'fee_due_charges' => 5, 'default' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => (int) GradeLevel::where('code', '8')->value('id')]);
        ClassSchool::whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => (int) GradeLevel::where('code', '9')->value('id')]);
        foreach (['house_points', 'awards', 'discipline'] as $module) {
            app(ModuleGate::class)->change($module, true, $this->superAdmin());
        }
        foreach (['asha', 'ben'] as $learner) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$learner], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic enrolment']);
        }
        // Asha is promoted into next year; Ben leaves.
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->next, 'class_section_id' => $this->schoolA->ids['section_c'], 'starts_on' => '2027-01-13', 'ends_on' => '2027-12-08', 'reason' => 'Synthetic promotion']);
        $this->actingAs($this->superAdmin());
    }

    private function house(string $name, bool $active = true): House
    {
        return House::query()->create(['name' => $name, 'emblem' => 'star', 'primary_colour' => '#112233', 'secondary_colour' => '#445566', 'active' => $active]);
    }

    private function member(House $house, string $learner, string $starts = '2026-01-01', ?string $ends = null, bool $active = true): void
    {
        HouseMembership::query()->create(['learner_id' => $this->schoolA->ids[$learner], 'house_id' => $house->id, 'session_year_id' => $this->year,
            'starts_on' => $starts, 'ends_on' => $ends, 'active' => $active, 'reason' => 'Synthetic house']);
    }

    private function bells(): void
    {
        app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Normal day', 'section' => 'High school', 'session_year_id' => $this->year, 'periods' => [
            ['name' => 'Period 1', 'starts_at' => '07:45', 'ends_at' => '08:30', 'is_break' => false],
            ['name' => 'Break', 'starts_at' => '10:00', 'ends_at' => '10:20', 'is_break' => true],
        ]]);
    }

    private function framework(): void
    {
        app(DisciplineFrameworks::class)->save($this->superAdmin(), $this->year, [
            'title' => 'Code of conduct', 'expected_revision' => 0, 'counter_scope' => 'same_infraction', 'reset' => 'school_year',
            'levels' => [
                ['code' => 'warning', 'name' => 'Warning', 'threshold' => 1, 'kind' => 'sanction', 'sanctions' => ['notice']],
                ['code' => 'hearing', 'name' => 'Hearing', 'threshold' => 3, 'kind' => 'hearing', 'sanctions' => []],
            ],
            'sanctions' => [['code' => 'notice', 'name' => 'Letter home', 'kind' => 'notice']],
            'infractions' => [['code' => 'late', 'name' => 'Late for class', 'base_level' => 'warning']],
        ]);
    }

    public function test_learners_who_continue_stay_in_their_house_and_leavers_do_not(): void
    {
        $red = $this->house('Red');
        $blue = $this->house('Blue');
        $this->member($blue, 'asha', '2026-01-01', '2026-03-31');
        $this->member($red, 'asha', '2026-04-01');
        $this->member($red, 'ben');

        app(TransferHouseMemberships::class)->execute($this->year, $this->next);
        app(TransferHouseMemberships::class)->execute($this->year, $this->next);

        $carried = HouseMembership::where('session_year_id', $this->next)->get();
        $this->assertCount(1, $carried, 'only the continuing learner, once');
        $this->assertSame([$this->schoolA->ids['asha'], $red->id, '2027-01-13'], [(int) $carried[0]->learner_id, (int) $carried[0]->house_id, $carried[0]->starts_on->toDateString()]);
        $this->assertSame(3, HouseMembership::where('session_year_id', $this->year)->count(), 'last year keeps its history');
    }

    public function test_a_learner_promoted_and_then_marked_as_leaving_is_not_carried(): void
    {
        $this->member($this->house('Red'), 'asha');
        // The legacy promotion page records the leaving decision without ending the enrolment.
        StudentSessions::withoutEvents(fn () => StudentSessions::query()->updateOrCreate(
            ['student_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->next], ['class_section_id' => null, 'previous_session_year_id' => $this->year]));
        StudentSessions::query()->where('student_id', $this->schoolA->ids['asha'])->where('session_year_id', $this->next)->update(['status' => 0]);

        app(TransferHouseMemberships::class)->execute($this->year, $this->next);

        $this->assertSame(0, HouseMembership::where('session_year_id', $this->next)->count());
    }

    public function test_a_closed_house_or_an_ungraded_homeroom_is_not_carried(): void
    {
        $old = $this->house('Old house');
        $this->member($old, 'asha');
        $old->update(['active' => false]);
        app(TransferHouseMemberships::class)->execute($this->year, $this->next);
        $this->assertSame(0, HouseMembership::where('session_year_id', $this->next)->count());

        $old->update(['active' => true]);
        ClassSchool::whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => null]);
        app(TransferHouseMemberships::class)->execute($this->year, $this->next);
        $this->assertSame(0, HouseMembership::where('session_year_id', $this->next)->count(), 'left for the school to place by hand');
    }

    public function test_bell_schedules_are_copied_with_their_periods_once(): void
    {
        $this->bells();

        app(TransferBellSchedules::class)->execute($this->year, $this->next);
        app(TransferBellSchedules::class)->execute($this->year, $this->next);

        $copied = BellSchedule::with('periods')->where('session_year_id', $this->next)->get();
        $this->assertCount(1, $copied);
        $this->assertSame(['Normal day', 'High school'], [$copied[0]->name, $copied[0]->section]);
        $this->assertSame([['Period 1', '07:45', false], ['Break', '10:00', true]],
            $copied[0]->periods->sortBy('position')->map(fn ($p) => [$p->name, substr((string) $p->starts_at, 0, 5), (bool) $p->is_break])->values()->all());
    }

    public function test_award_windows_move_with_the_year_as_drafts(): void
    {
        $category = AwardCategory::query()->create(['name' => 'Academic honours', 'scope_kind' => 'school', 'active' => true, 'revision' => 1]);
        $group = AwardCategory::query()->create(['name' => 'Team of the year', 'scope_kind' => 'group', 'active' => true, 'revision' => 1]);
        AwardWindow::query()->create(['award_category_id' => $category->id, 'session_year_id' => $this->year, 'opens_on' => '2026-10-01', 'closes_on' => '2026-10-31', 'status' => 'closed']);
        AwardWindow::query()->create(['award_category_id' => $group->id, 'session_year_id' => $this->year, 'opens_on' => '2026-10-01', 'closes_on' => '2026-10-31', 'status' => 'closed']);

        app(TransferAwardWindows::class)->execute($this->year, $this->next);

        $copied = AwardWindow::where('session_year_id', $this->next)->get();
        $this->assertCount(1, $copied, 'group windows wait for the new season\'s group');
        // The new year starts 377 days later; the window keeps its place in the year, as a draft.
        $this->assertSame([$category->id, '2027-10-13', '2027-11-12', 'draft'], [(int) $copied[0]->award_category_id, substr((string) $copied[0]->opens_on, 0, 10), substr((string) $copied[0]->closes_on, 0, 10), $copied[0]->status]);
    }

    public function test_switching_years_carries_everything_chosen_and_the_discipline_framework_after_the_switch(): void
    {
        $this->bells();
        $this->member($this->house('Red'), 'asha');
        $this->framework();
        $from = SessionYear::findOrFail($this->year);
        $to = SessionYear::findOrFail($this->next);
        $chosen = ['bell_schedules', 'houses', 'discipline'];

        $options = collect(app(NewYearService::class)->carryOptions($from, $to, $chosen))->keyBy('key');
        $this->assertSame([1, 1, 1], [$options['bell_schedules']['source'], $options['houses']['source'], $options['discipline']['source']]);

        $result = app(NewYearService::class)->switchTo($from, $to, $chosen);

        $this->assertSame(['bell_schedules', 'houses', 'discipline'], $result['copied']);
        $this->assertSame(1, BellSchedule::where('session_year_id', $this->next)->count());
        $this->assertSame(1, HouseMembership::where('session_year_id', $this->next)->count());
        $framework = DisciplineFramework::where('session_year_id', $this->next)->firstOrFail();
        $this->assertSame(['Code of conduct', 1, 'late'], [$framework->title, (int) $framework->revision, $framework->definition['infractions'][0]['code']]);
        $this->assertSame((string) $this->next, (string) getSettings('session_year')['session_year']);
    }

    public function test_modules_that_are_off_offer_nothing_to_carry(): void
    {
        $this->member($this->house('Red'), 'asha');
        app(ModuleGate::class)->change('house_points', false, $this->superAdmin());

        $options = collect(app(NewYearService::class)->carryOptions(SessionYear::findOrFail($this->year), SessionYear::findOrFail($this->next), ['houses']))->keyBy('key');

        $this->assertSame(0, $options['houses']['source']);
        $this->assertFalse($options['houses']['copies']);
    }

    public function test_people_without_the_management_permission_cannot_carry(): void
    {
        $this->bells();
        $this->member($this->house('Red'), 'asha');
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']));

        $this->framework();
        foreach ([TransferBellSchedules::class, TransferHouseMemberships::class, TransferAwardWindows::class, TransferDisciplineFramework::class] as $action) {
            try {
                app($action)->execute($this->year, $this->next);
                $this->fail("{$action} ran for a teacher");
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertSame(0, BellSchedule::where('session_year_id', $this->next)->count());
        $this->assertSame(0, HouseMembership::where('session_year_id', $this->next)->count());
    }

    public function test_nothing_is_carried_into_another_schools_year(): void
    {
        $this->bells();
        $this->member($this->house('Red'), 'asha');
        $this->framework();
        $category = AwardCategory::query()->create(['name' => 'Academic honours', 'scope_kind' => 'school', 'active' => true, 'revision' => 1]);
        AwardWindow::query()->create(['award_category_id' => $category->id, 'session_year_id' => $this->year, 'opens_on' => '2026-10-01', 'closes_on' => '2026-10-31', 'status' => 'closed']);
        $foreign = (int) $this->schoolB->ids['session_year'];
        $before = $this->everywhere();

        foreach ([TransferBellSchedules::class, TransferHouseMemberships::class, TransferAwardWindows::class, TransferDisciplineFramework::class] as $action) {
            try {
                app($action)->execute($this->year, $foreign);
                $this->fail("{$action} wrote into another school's year");
            } catch (ModelNotFoundException) {
                // The other school's year does not exist here.
            }
        }
        $this->assertSame($before, $this->everywhere(), 'nothing changed in either school');
    }

    /** @return list<int> rows of each carried kind, across both schools */
    private function everywhere(): array
    {
        return [BellSchedule::query()->withoutGlobalScopes()->count(), HouseMembership::query()->withoutGlobalScopes()->count(),
            AwardWindow::query()->withoutGlobalScopes()->count(), DisciplineFramework::query()->withoutGlobalScopes()->count()];
    }
}
