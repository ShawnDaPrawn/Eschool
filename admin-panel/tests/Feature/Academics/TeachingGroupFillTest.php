<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSchool;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Re-assignment (Action Plan 1.20): a homeroom's teaching group filled with
 * the homeroom in one step. engineering/specs/academics/teaching-groups.md.
 */
class TeachingGroupFillTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $grade = (int) GradeLevel::where('code', '7')->value('id');
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $grade]);
        ClassSchool::whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => $grade]);
        $ids = $this->schoolA->ids;
        // Asha all year; Ben moves to 7C at the end of June; Cara is in 7C.
        Enrolment::create(['learner_id' => $ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic']);
        Enrolment::create(['learner_id' => $ids['ben'], 'session_year_id' => $this->year, 'class_section_id' => $ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-06-30', 'reason' => 'Synthetic']);
        Enrolment::create(['learner_id' => $ids['ben'], 'session_year_id' => $this->year, 'class_section_id' => $ids['section_c'], 'starts_on' => '2026-07-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic']);
        Enrolment::create(['learner_id' => $ids['cara'], 'session_year_id' => $this->year, 'class_section_id' => $ids['section_c'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic']);
        $this->actingAs($this->superAdmin());
    }

    private function group(?int $room): TeachingGroup
    {
        return app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Maths 7A', 'subject_id' => $this->schoolA->ids['maths'],
            'grade_level_id' => (int) GradeLevel::where('code', '7')->value('id'), 'session_year_id' => $this->year, 'class_section_id' => $room, 'active' => true]);
    }

    public function test_the_homeroom_fills_its_group_once_with_each_learners_own_dates(): void
    {
        $group = $this->group($this->schoolA->ids['section_a']);

        $this->assertSame(['added' => 2, 'skipped' => 0], app(TeachingGroups::class)->fillFromHomeroom($this->superAdmin(), $group->id, '2026-02-10', 'catch_up'));

        $rows = TeachingGroupMembership::where('teaching_group_id', $group->id)->orderBy('learner_id')->get()->keyBy('learner_id');
        $this->assertSame([$this->schoolA->ids['asha'], $this->schoolA->ids['ben']], $rows->keys()->map(fn ($id) => (int) $id)->sort()->values()->all());
        $this->assertSame(['2026-02-10', '2026-12-31'], [$rows[$this->schoolA->ids['asha']]->starts_on->toDateString(), $rows[$this->schoolA->ids['asha']]->ends_on->toDateString()]);
        $this->assertSame('2026-06-30', $rows[$this->schoolA->ids['ben']]->ends_on->toDateString(), 'until Ben leaves the homeroom');
        $this->assertSame('catch_up', $rows[$this->schoolA->ids['ben']]->mark_rule);

        $this->assertSame(['added' => 0, 'skipped' => 2], app(TeachingGroups::class)->fillFromHomeroom($this->superAdmin(), $group->id, '2026-02-10', 'catch_up'));
    }

    public function test_only_a_homeroom_group_in_the_year_can_be_filled(): void
    {
        $elective = $this->group(null);
        try {
            app(TeachingGroups::class)->fillFromHomeroom($this->superAdmin(), $elective->id, '2026-02-10', 'catch_up');
            $this->fail('Filled a group without a homeroom');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('group', $e->errors());
        }
        $this->expectException(ValidationException::class);
        app(TeachingGroups::class)->fillFromHomeroom($this->superAdmin(), $this->group($this->schoolA->ids['section_a'])->id, '2027-02-10', 'catch_up');
    }

    public function test_the_panel_fills_and_others_cannot(): void
    {
        $group = $this->group($this->schoolA->ids['section_a']);
        $this->get(route('teaching-groups.show', $group->id))->assertOk()->assertSee('Add everyone in the homeroom');
        $this->post(route('teaching-groups.fill', $group->id), ['starts_on' => '2026-02-10', 'mark_rule' => 'excused'])
            ->assertRedirect(route('teaching-groups.show', $group->id))->assertSessionHas('success');
        $this->assertSame(2, TeachingGroupMembership::where('teaching_group_id', $group->id)->count());

        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        try {
            app(TeachingGroups::class)->fillFromHomeroom($teacher, $group->id, '2026-03-01', 'catch_up');
            $this->fail('A teacher filled a group');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->actingAs($teacher)->post(route('teaching-groups.fill', $group->id), ['starts_on' => '2026-03-01', 'mark_rule' => 'catch_up'])->assertForbidden();
        $this->assertSame(2, TeachingGroupMembership::where('teaching_group_id', $group->id)->count());
    }

    public function test_foreign_school_groups_cannot_be_filled_through_the_service_or_panel(): void
    {
        $foreign = app(CurrentSchool::class)->run(2, fn () => TeachingGroup::query()->create([
            'name' => 'Baobab Maths', 'subject_id' => $this->schoolB->ids['maths'],
            'grade_level_id' => (int) GradeLevel::where('code', '7')->value('id'),
            'session_year_id' => $this->schoolB->ids['session_year'],
            'class_section_id' => $this->schoolB->ids['section_a'], 'active' => true,
        ]));
        $before = DB::table('teaching_group_memberships')->count();
        try {
            app(TeachingGroups::class)->fillFromHomeroom($this->superAdmin(), $foreign->id, '2026-02-10', 'catch_up');
            $this->fail('Filled another school\'s group');
        } catch (ModelNotFoundException) {
            $this->assertSame($before, DB::table('teaching_group_memberships')->count());
        }
        $this->post(route('teaching-groups.fill', $foreign->id), ['starts_on' => '2026-02-10', 'mark_rule' => 'catch_up'])->assertNotFound();
        $this->assertSame($before, DB::table('teaching_group_memberships')->count(), 'Neither school receives a membership');
    }
}
