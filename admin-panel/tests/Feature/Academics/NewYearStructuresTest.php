<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Actions\Transfers\TransferActivityGroups;
use App\Actions\Transfers\TransferTeachingGroups;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\ClassSchool;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Services\Academics\NewYearService;
use App\Services\Academics\TeachingGroups;
use App\Services\Activities\ActivityGroups;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Year-end carries the structure, not the people: teaching groups and
 * activity groups are copied into the new year empty.
 * Specification: engineering/specs/academics/new-year-structures.md.
 */
class NewYearStructuresTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    private int $next;

    private int $grade;

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
        $this->grade = (int) GradeLevel::where('code', '8')->value('id');
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        $this->superAdmin()->givePermissionTo('activity-groups-manage');
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic enrolment']);
        $this->actingAs($this->superAdmin());
    }

    private function activity(array $extra = []): array
    {
        return app(ActivityGroups::class)->save($this->superAdmin(), null, $extra + [
            'operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'parent_id' => null,
            'name' => 'Soccer', 'discipline' => 'Sport', 'season' => 'Summer', 'starts_on' => '2026-01-20', 'ends_on' => '2026-06-30',
            'gender' => 'any', 'minimum_grade' => null, 'maximum_grade' => null, 'minimum_age' => null, 'maximum_age' => null, 'active' => true,
        ]);
    }

    public function test_teaching_groups_are_copied_into_the_new_year_empty_and_only_once(): void
    {
        app(TeachingGroups::class)->save($this->superAdmin(), ['name' => '8A Maths', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Old elective', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'active' => false]);

        app(TransferTeachingGroups::class)->execute($this->year, $this->next);
        app(TransferTeachingGroups::class)->execute($this->year, $this->next);

        $copied = TeachingGroup::where('session_year_id', $this->next)->get();
        $this->assertSame(['8A Maths'], $copied->pluck('name')->all(), 'active groups only, never twice');
        $this->assertSame($this->schoolA->ids['section_a'], (int) $copied->first()->class_section_id);
        $this->assertSame(0, DB::table('teaching_group_memberships')->where('teaching_group_id', $copied->first()->id)->count(), 'nobody is carried into the new group');
    }

    public function test_activity_groups_keep_their_tree_and_rules_but_not_their_members(): void
    {
        $parent = $this->activity(['name' => 'Soccer', 'starts_on' => '2026-01-20', 'ends_on' => '2026-06-30']);
        $child = $this->activity(['name' => 'U14 Boys', 'parent_id' => $parent['id'], 'starts_on' => '2026-02-01', 'ends_on' => '2026-06-15', 'gender' => 'male', 'minimum_grade' => 7, 'maximum_grade' => 8]);
        app(ActivityGroups::class)->allocation($this->superAdmin(), $parent['id'], null, false, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'starts_on' => '2026-02-01', 'ends_on' => '2026-06-30', 'reason' => 'Synthetic member', 'learner_id' => $this->schoolA->ids['asha'], 'role' => 'member']);

        app(TransferActivityGroups::class)->execute($this->year, $this->next);

        $newParent = ActivityGroup::where('session_year_id', $this->next)->whereNull('parent_id')->where('name', 'Soccer')->firstOrFail();
        $newChild = ActivityGroup::where('session_year_id', $this->next)->where('parent_id', $newParent->id)->firstOrFail();
        $this->assertSame('U14 Boys', $newChild->name);
        $this->assertSame(['male', 7, 8], [$newChild->gender, (int) $newChild->minimum_grade, (int) $newChild->maximum_grade]);
        // The new year starts 377 days after the old one; seasons move with it and stay within the year and the parent.
        $this->assertSame('2027-02-01', $newParent->starts_on->toDateString());
        $this->assertGreaterThanOrEqual($newParent->starts_on->toDateString(), $newChild->starts_on->toDateString());
        $this->assertLessThanOrEqual($newParent->ends_on->toDateString(), $newChild->ends_on->toDateString());
        $this->assertLessThanOrEqual('2027-12-08', $newParent->ends_on->toDateString());
        $this->assertSame(0, ActivityMembership::where('activity_group_id', $newParent->id)->count(), 'last year\'s members are not carried');
        $this->assertSame(1, ActivityMembership::where('activity_group_id', $parent['id'])->count(), 'last year keeps its history');
        $this->assertNotSame($child['id'], $newChild->id);
    }

    public function test_activity_groups_are_not_copied_twice_or_when_archived(): void
    {
        $this->activity(['name' => 'Chess']);
        $this->activity(['name' => 'Retired club', 'active' => false]);

        app(TransferActivityGroups::class)->execute($this->year, $this->next);
        app(TransferActivityGroups::class)->execute($this->year, $this->next);

        $this->assertSame(['Chess'], ActivityGroup::where('session_year_id', $this->next)->pluck('name')->all());
    }

    public function test_the_wizard_offers_both_structures_with_their_counts(): void
    {
        app(TeachingGroups::class)->save($this->superAdmin(), ['name' => '8A Maths', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $this->activity(['name' => 'Chess']);

        $options = collect(app(NewYearService::class)->carryOptions(SessionYear::findOrFail($this->year), SessionYear::findOrFail($this->next), ['teaching_groups', 'activity_groups']))->keyBy('key');

        $this->assertSame(1, $options['teaching_groups']['source']);
        $this->assertTrue($options['teaching_groups']['copies']);
        $this->assertSame(1, $options['activity_groups']['source']);
        $this->assertTrue($options['activity_groups']['copies']);
    }
}
