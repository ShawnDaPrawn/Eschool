<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\StaffDrafts;
use App\Services\Academics\TeachingGroups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Staff drafts (Action Plan 1.20): the new year's subject and homeroom
 * teachers drafted from last year's, edited and applied in one step.
 * engineering/specs/academics/teaching-groups.md.
 */
class StaffDraftTest extends TestCase
{
    use UsesTwoSchools;

    private int $last;

    private SessionYear $next;

    private int $grade;

    private int $tara;

    private int $theo;

    private TeachingGroup $maths;

    private TeachingGroup $science;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 11, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $ids = $this->schoolA->ids;
        $this->last = $ids['session_year'];
        SessionYear::whereKey($this->last)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->next = SessionYear::create(['name' => 'Next year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        ClassSchool::whereKey($ids['class_a'])->update(['grade_level_id' => $this->grade]);
        ClassSchool::whereKey($ids['class_c'])->update(['grade_level_id' => $this->grade]);
        $this->tara = (int) $ids['tara_teacher'];
        $this->theo = (int) $ids['theo_teacher'];
        $this->actingAs($this->superAdmin());
        $groups = app(TeachingGroups::class);
        // Last year: Theo taught Maths 7A until June, then Tara to the end; Tara held 7A, Theo 7C.
        $old = $this->group('Maths 7A', $ids['maths'], $this->last, $ids['section_a']);
        $groups->dated($this->superAdmin(), 'teachers', ['teacher_id' => $this->theo, 'starts_on' => '2026-01-01', 'ends_on' => '2026-06-30', 'reason' => 'Synthetic'], $old->id);
        $groups->dated($this->superAdmin(), 'teachers', ['teacher_id' => $this->tara, 'starts_on' => '2026-07-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic'], $old->id);
        foreach (['section_a' => $this->tara, 'section_c' => $this->theo] as $room => $teacher) {
            $groups->dated($this->superAdmin(), 'homerooms', ['teacher_id' => $teacher, 'class_section_id' => $ids[$room], 'session_year_id' => $this->last,
                'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic']);
        }
        // Next year: Maths 7A carried over, Science 7 is new.
        $this->maths = $this->group('Maths 7A', $ids['maths'], $this->next->id, $ids['section_a']);
        $this->science = $this->group('Science 7', $ids['science'], $this->next->id, null);
    }

    private function group(string $name, int $subject, int $year, ?int $room): TeachingGroup
    {
        return app(TeachingGroups::class)->save($this->superAdmin(), ['name' => $name, 'subject_id' => $subject,
            'grade_level_id' => $this->grade, 'session_year_id' => $year, 'class_section_id' => $room, 'active' => true]);
    }

    private function teacher(string $name = 'tara'): User
    {
        return User::findOrFail($this->schoolA->ids[$name.'_user']);
    }

    private function nextYearAllocations(): int
    {
        return TeachingAllocation::whereIn('teaching_group_id', [$this->maths->id, $this->science->id])->count()
            + HomeroomAllocation::where('session_year_id', $this->next->id)->count();
    }

    private function row(array $plan, string $kind, int $id): array
    {
        return collect($plan[$kind])->firstWhere('id', $id);
    }

    public function test_the_draft_prefills_last_years_staff_and_applying_writes_the_new_year(): void
    {
        $ids = $this->schoolA->ids;
        $drafts = app(StaffDrafts::class);
        $plan = $drafts->plan($this->superAdmin(), $this->next);

        $this->assertSame($this->last, (int) $plan['from']->id);
        $maths = $this->row($plan, 'groups', $this->maths->id);
        $this->assertSame([$this->tara], $maths['draft'], 'whoever taught it at the end of last year');
        $this->assertSame([$this->tara], array_column($maths['last_year'], 'teacher_id'));
        $science = $this->row($plan, 'groups', $this->science->id);
        $this->assertNull($science['source_id']);
        $this->assertSame([], $science['draft']);
        $this->assertSame([$this->tara], $this->row($plan, 'homerooms', $ids['section_a'])['draft']);
        $this->assertSame([$this->theo], $this->row($plan, 'homerooms', $ids['section_c'])['draft']);

        // Nothing is written by building the draft, so teachers see nothing yet.
        $this->assertSame(0, $this->nextYearAllocations());
        try {
            app(AcademicAuthority::class)->group($this->teacher(), $this->maths->id);
            $this->fail('A teacher opened a drafted group before it was applied');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // The leader keeps Maths, adds Tara to Science and gives 7C to Tara instead of Theo.
        $done = $drafts->apply($this->superAdmin(), $this->next, [
            'groups' => [$this->maths->id => [$this->tara], $this->science->id => [$this->tara, '']],
            'homerooms' => [$ids['section_a'] => [$this->tara], $ids['section_c'] => [$this->tara]],
        ], $plan['fingerprint']);

        $this->assertSame(['teachers' => 2, 'homerooms' => 2], $done);
        $row = TeachingAllocation::where('teaching_group_id', $this->maths->id)->sole();
        $this->assertSame([$this->tara, '2027-01-01', '2027-12-31', StaffDrafts::REASON],
            [(int) $row->teacher_id, $row->starts_on->toDateString(), $row->ends_on->toDateString(), $row->reason]);
        $this->assertSame([$this->tara, $this->tara], HomeroomAllocation::where('session_year_id', $this->next->id)->orderBy('class_section_id')->pluck('teacher_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(1, TeachingAllocation::where('teaching_group_id', $this->science->id)->count());

        // Applying the same draft again writes nothing; the new draft shows them as allocated.
        try {
            $drafts->apply($this->superAdmin(), $this->next, ['groups' => [$this->maths->id => [$this->tara]]], $plan['fingerprint']);
            $this->fail('The same draft was applied twice');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fingerprint', $e->errors());
        }
        $this->assertSame(4, $this->nextYearAllocations());
        $again = $drafts->plan($this->superAdmin(), $this->next);
        $this->assertSame('Tara', explode(' ', $this->row($again, 'groups', $this->maths->id)['staffed'][0]['name'])[0]);
        $this->assertSame([], $this->row($again, 'groups', $this->maths->id)['draft']);
    }

    public function test_a_teacher_who_left_the_staff_is_shown_but_not_drafted(): void
    {
        User::whereKey($this->schoolA->ids['theo_user'])->update(['status' => 0]);
        $plan = app(StaffDrafts::class)->plan($this->superAdmin(), $this->next);
        $room = $this->row($plan, 'homerooms', $this->schoolA->ids['section_c']);

        $this->assertSame([['teacher_id' => $this->theo, 'on_staff' => false]], array_map(fn ($t) => array_diff_key($t, ['name' => true]), $room['last_year']));
        $this->assertSame([], $room['draft']);
        $this->assertArrayNotHasKey($this->theo, $plan['teachers']);
        try {
            app(StaffDrafts::class)->apply($this->superAdmin(), $this->next, ['homerooms' => [$this->schoolA->ids['section_c'] => [$this->theo]]], $plan['fingerprint']);
            $this->fail('Drafted a teacher who left');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('homerooms', $e->errors());
        }
        $this->assertSame(0, $this->nextYearAllocations());
    }

    public function test_the_allocations_page_builds_and_applies_the_draft(): void
    {
        $this->get(route('academic-allocations.index'))->assertOk()->assertSee('Draft from last year');
        $page = $this->get(route('academic-allocations.draft', ['year' => $this->next->id]))->assertOk()
            ->assertSee('Staff draft for Next year')->assertSee('Maths 7A')->assertSee('New this year: there was no such group last year.');
        preg_match('/name="fingerprint" value="([a-f0-9]{64})"/', $page->getContent(), $match);
        $this->assertNotEmpty($match);

        $this->post(route('academic-allocations.draft.apply'), ['complete' => 1, 'year' => $this->next->id, 'fingerprint' => $match[1],
            'groups' => [$this->maths->id => [$this->tara]], 'homerooms' => [$this->schoolA->ids['section_a'] => [$this->tara]]])
            ->assertRedirect(route('academic-allocations.draft', ['year' => $this->next->id]))->assertSessionHas('success');
        $this->assertSame(2, $this->nextYearAllocations());
        $this->get(route('academic-allocations.draft', ['year' => $this->next->id]))->assertOk()->assertSee('Already allocated')->assertSee('Apply the draft');

        // Once every row has a teacher, the page says so instead of offering an empty apply.
        $drafts = app(StaffDrafts::class);
        $plan = $drafts->plan($this->superAdmin(), $this->next);
        $choices = [];
        foreach (['groups', 'homerooms'] as $kind) {
            foreach ($plan[$kind] as $row) {
                if ($row['staffed'] === [] && $row['in_form']) {
                    $choices[$kind][$row['id']] = [$this->tara];
                }
            }
        }
        $drafts->apply($this->superAdmin(), $this->next, $choices, $plan['fingerprint']);
        $this->get(route('academic-allocations.draft', ['year' => $this->next->id]))->assertOk()
            ->assertSee('there is nothing to apply')->assertDontSee('Apply the draft');
    }

    public function test_leaving_the_first_900_fields_empty_does_not_hide_later_homerooms(): void
    {
        $attributes = $this->maths->getAttributes();
        unset($attributes['id']);
        // There are already two groups; make exactly 900 before the homerooms.
        for ($index = 2; $index < StaffDrafts::FIELDS_PER_SUBMISSION; $index++) {
            $attributes['name'] = 'Synthetic batch group '.$index;
            DB::table('teaching_groups')->insert($attributes);
        }
        $drafts = app(StaffDrafts::class);
        $first = $drafts->plan($this->superAdmin(), $this->next);
        $this->assertCount(900, $first['groups']);
        $this->assertSame(900, $first['next_offset']);
        $room = $this->schoolA->ids['section_a'];
        $this->assertFalse($this->row($first, 'homerooms', $room)['in_form']);
        $this->get(route('academic-allocations.draft', ['year' => $this->next->id]))
            ->assertOk()->assertSee(route('academic-allocations.draft', ['year' => $this->next->id, 'offset' => 900]));
        $nobody = array_fill_keys(array_column($first['groups'], 'id'), ['']);
        $drafts->apply($this->superAdmin(), $this->next, ['groups' => $nobody], $first['fingerprint']);
        $this->assertSame(0, $this->nextYearAllocations());

        $second = $drafts->plan($this->superAdmin(), $this->next, 900);
        $this->assertTrue($this->row($second, 'homerooms', $room)['in_form']);
        $this->assertNull($second['next_offset']);
        $this->assertNotSame($first['fingerprint'], $second['fingerprint'], 'the fingerprint binds the batch');
        $page = $this->get(route('academic-allocations.draft', ['year' => $this->next->id, 'offset' => 900]))->assertOk();
        $page->assertSee('name="offset" value="900"', false)->assertSee('name="homerooms['.$room.'][]"', false);
        $this->post(route('academic-allocations.draft.apply'), ['year' => $this->next->id, 'offset' => 900, 'complete' => 1,
            'fingerprint' => $first['fingerprint'], 'homerooms' => [$room => [$this->tara]]])->assertSessionHasErrors('fingerprint');
        $this->assertSame(0, $this->nextYearAllocations());
        $this->post(route('academic-allocations.draft.apply'), ['year' => $this->next->id, 'offset' => 900, 'complete' => 1,
            'fingerprint' => $second['fingerprint'], 'homerooms' => [$room => [$this->tara]]])
            ->assertRedirect(route('academic-allocations.draft', ['year' => $this->next->id, 'offset' => 900]));
        $this->assertSame(1, $this->nextYearAllocations());
        $this->assertSame(0, TeachingAllocation::whereIn('teaching_group_id', array_column($first['groups'], 'id'))->count());
    }

    public function test_a_draft_cut_short_in_transit_is_refused_and_nothing_is_written(): void
    {
        $page = $this->get(route('academic-allocations.draft', ['year' => $this->next->id]))->assertOk();
        preg_match('/name="fingerprint" value="([a-f0-9]{64})"/', $page->getContent(), $match);
        // One field per drafted teacher, one empty field for a row with none, and the marker last.
        $this->assertSame(1, substr_count($page->getContent(), 'name="groups['.$this->maths->id.'][]"'));
        $this->assertSame(1, substr_count($page->getContent(), 'name="groups['.$this->science->id.'][]"'));
        $this->assertGreaterThan(strrpos($page->getContent(), 'name="homerooms['), strpos($page->getContent(), 'name="complete"'));

        // Past the server's input limit the trailing fields, the marker first, are dropped.
        $this->post(route('academic-allocations.draft.apply'), ['year' => $this->next->id, 'fingerprint' => $match[1],
            'groups' => [$this->maths->id => [$this->tara]]])->assertSessionHasErrors('complete');
        $this->assertSame(0, $this->nextYearAllocations());
    }

    public function test_a_teacher_cannot_build_or_apply_a_draft(): void
    {
        $plan = app(StaffDrafts::class)->plan($this->superAdmin(), $this->next);
        foreach ([fn () => app(StaffDrafts::class)->plan($this->teacher(), $this->next),
            fn () => app(StaffDrafts::class)->apply($this->teacher(), $this->next, ['groups' => [$this->maths->id => [$this->tara]]], $plan['fingerprint'])] as $call) {
            try {
                $call();
                $this->fail('A teacher used the staff draft');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->actingAs($this->teacher());
        $this->get(route('academic-allocations.draft', ['year' => $this->next->id]))->assertForbidden();
        $this->post(route('academic-allocations.draft.apply'), ['complete' => 1, 'year' => $this->next->id, 'fingerprint' => $plan['fingerprint'],
            'groups' => [$this->maths->id => [$this->tara]]])->assertForbidden();
        $this->assertSame(0, $this->nextYearAllocations());
    }

    public function test_another_schools_groups_teachers_and_years_are_refused(): void
    {
        $b = $this->schoolB->ids;
        $foreign = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => TeachingGroup::query()->create([
            'name' => 'Baobab Maths', 'subject_id' => $b['maths'], 'grade_level_id' => $this->grade,
            'session_year_id' => $b['session_year'], 'class_section_id' => $b['section_a'], 'active' => true,
        ]));
        $foreignTeacher = (int) app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => Teacher::query()->where('user_id', $b['tara_user'])->value('id'));
        $before = [DB::table('teaching_allocations')->count(), DB::table('homeroom_allocations')->count()];
        $plan = app(StaffDrafts::class)->plan($this->superAdmin(), $this->next);

        foreach ([['groups' => [$foreign->id => [$this->tara]]], ['groups' => [$this->maths->id => [$foreignTeacher]]],
            ['homerooms' => [$b['section_a'] => [$this->tara]]]] as $choices) {
            try {
                app(StaffDrafts::class)->apply($this->superAdmin(), $this->next, $choices, $plan['fingerprint']);
                $this->fail('Another school\'s record was drafted');
            } catch (ValidationException) {
                $this->assertSame($before, [DB::table('teaching_allocations')->count(), DB::table('homeroom_allocations')->count()]);
            }
        }
        $this->get(route('academic-allocations.draft', ['year' => $b['session_year']]))->assertNotFound();
        $this->post(route('academic-allocations.draft.apply'), ['complete' => 1, 'year' => $b['session_year'], 'fingerprint' => $plan['fingerprint']])->assertNotFound();
        $this->post(route('academic-allocations.draft.apply'), ['complete' => 1, 'year' => $this->next->id, 'fingerprint' => $plan['fingerprint'],
            'groups' => [$foreign->id => [$this->tara]]])->assertSessionHasErrors('groups');
        $this->assertSame($before, [DB::table('teaching_allocations')->count(), DB::table('homeroom_allocations')->count()], 'Neither school gains an allocation');
        $this->assertSame(0, app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => TeachingAllocation::where('teaching_group_id', $foreign->id)->count()));
    }

    public function test_a_draft_built_before_allocations_changed_is_refused(): void
    {
        $plan = app(StaffDrafts::class)->plan($this->superAdmin(), $this->next);
        // Someone allocates Science by hand after the draft was built.
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => $this->theo, 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'By hand'], $this->science->id);

        try {
            app(StaffDrafts::class)->apply($this->superAdmin(), $this->next, ['groups' => [$this->maths->id => [$this->tara]]], $plan['fingerprint']);
            $this->fail('A stale draft was applied');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fingerprint', $e->errors());
        }
        $this->assertSame(1, $this->nextYearAllocations(), 'only the allocation made by hand');
        $this->post(route('academic-allocations.draft.apply'), ['complete' => 1, 'year' => $this->next->id, 'fingerprint' => $plan['fingerprint'],
            'groups' => [$this->maths->id => [$this->tara]]])->assertSessionHasErrors('fingerprint');
        $this->assertSame(1, $this->nextYearAllocations());
    }

    public function test_an_ended_year_or_one_without_an_earlier_year_has_no_draft(): void
    {
        try {
            app(StaffDrafts::class)->plan($this->superAdmin(), SessionYear::findOrFail($this->last));
            $this->fail('Drafted a year with no year before it');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('year', $e->errors());
        }
        $this->travelTo(now()->setDate(2028, 1, 10));
        $this->expectException(ValidationException::class);
        app(StaffDrafts::class)->plan($this->superAdmin(), $this->next);
    }
}
