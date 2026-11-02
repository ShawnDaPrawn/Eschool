<?php

namespace Tests\Feature;

use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\SessionYearService;
use Tests\Concerns\RunsAppServer;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * Year-end promotion on the Promote Student page, then moving the school into
 * the new year. Requested in-process (the page doesn't exit()), on a fresh
 * DemoSchool: switching the current year would disturb other test classes.
 */
class PromotionTest extends TestCase
{
    use RunsAppServer;

    private DemoSchool $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freshInstall();
        $this->school = DemoSchool::build();
    }

    public function test_a_promotion_into_next_year_takes_effect_when_that_year_becomes_current(): void
    {
        $id = $this->school->ids;
        $next = SessionYear::forceCreate([
            'name' => 'Next year', 'start_date' => now()->addYear()->startOfYear(), 'end_date' => now()->addYear()->endOfYear(),
            'fee_due_date' => now()->addYear()->startOfYear()->addMonth(), 'fee_due_charges' => 0, 'include_fee_installments' => 0, 'default' => 0,
        ]);
        $promote = fn (int $student, int $status, ?int $to = null) => $this->actingAs($this->superAdmin())->postJson('/promote-student', [
            'class_section_id' => $id['section_a'], 'student_id' => [$student], "status$student" => $status, "result$student" => 1,
            'new_class_section_id' => $to, 'session_year_id' => $next->id, 'previous_session_id_for_student' => 1,
        ])->assertJson(['error' => false]);

        $promote($id['asha'], 1, $id['section_c']);
        $promote($id['ben'], 0);

        // The class changed and leavers were locked out at once, while this year was still
        // running: the parent app showed Grade 6 next to Grade 5's timetable.
        $this->assertSame($id['section_a'], Students::find($id['asha'])->class_section_id);
        $this->assertSame(1, (int) User::find($id['ben_user'])->status);

        app(SessionYearService::class)->makeCurrent($next);

        $this->assertSame($id['section_c'], Students::find($id['asha'])->class_section_id);
        // A leaver is not deactivated: read-only until 31 March (Action Plan 1.21).
        $this->assertSame(1, (int) User::find($id['ben_user'])->status);
        $leaver = fn () => SchoolMembership::query()->where('user_id', $id['ben_user'])->where('role', 'Student')->sole();
        $this->assertSame(SchoolMembership::LEAVER, $leaver()->status);
        $this->assertSame(now()->addYear()->format('Y').'-03-31', $leaver()->leaver_until->toDateString());

        // Changing a leave back to continue makes Ben a current learner again.
        $promote($id['ben'], 1, $id['section_a']);
        $this->assertSame(1, (int) User::find($id['ben_user'])->status);
        $this->assertSame([SchoolMembership::ACTIVE, null], [$leaver()->status, $leaver()->leaver_until]);
        // Marking him as leaving the current year makes him a leaver at once.
        $promote($id['ben'], 0);
        $this->assertSame(SchoolMembership::LEAVER, $leaver()->status);
        $promote($id['ben'], 1, $id['section_a']);
        $this->assertSame($id['section_a'], StudentSessions::where(['student_id' => $id['ben'], 'session_year_id' => $next->id])->value('class_section_id'));
    }

    public function test_the_promote_page_opens_on_the_current_year(): void
    {
        $next = SessionYear::forceCreate([
            'name' => 'Next year', 'start_date' => now()->addYear()->startOfYear(), 'end_date' => now()->addYear()->endOfYear(),
            'fee_due_date' => now()->addYear()->startOfYear()->addMonth(), 'fee_due_charges' => 0, 'include_fee_installments' => 0, 'default' => 0,
        ]);
        app(SessionYearService::class)->makeCurrent($next);

        // It opened on the oldest year, which on a new install is the installer's empty one.
        $html = $this->actingAs($this->superAdmin())->get('/promote-student')->assertOk()->getContent();

        preg_match('/<select name="filter_session_year".*?<\/select>/s', $html, $filter);
        $this->assertMatchesRegularExpression('/<option value="'.$next->id.'"\s+selected>/', $filter[0] ?? '');
    }
}
