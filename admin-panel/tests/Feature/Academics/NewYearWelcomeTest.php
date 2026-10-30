<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Services\Academics\NewYearService;
use App\Services\Platform\Tenancy\SchoolTable;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Switch day (Action Plan 1.21): families are welcomed to the new year, and
 * the year-end checks name classes without a grade.
 * Specification: engineering/specs/academics/new-year-structures.md.
 */
class NewYearWelcomeTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    private int $next;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 12, 20));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->next = (int) SchoolTable::query('session_years')->insertGetId([
            'name' => '2027', 'start_date' => '2027-01-13', 'end_date' => '2027-12-08', 'free_app_use_date' => '2027-01-13', 'fee_due_date' => '2027-02-13', 'fee_due_charges' => 5, 'default' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['asha', 'ben'] as $learner) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$learner], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic enrolment']);
        }
        // Asha continues into 2027; Ben leaves.
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->next, 'class_section_id' => $this->schoolA->ids['section_c'], 'starts_on' => '2027-01-13', 'ends_on' => '2027-12-08', 'reason' => 'Synthetic promotion']);
        $this->actingAs($this->superAdmin());
    }

    public function test_families_of_learners_in_the_new_year_are_welcomed_when_it_becomes_current(): void
    {
        $result = app(NewYearService::class)->switchTo(SessionYear::findOrFail($this->year), SessionYear::findOrFail($this->next), [], true);

        $notice = Notification::where('type', NewYearService::WELCOME)->sole();
        $this->assertSame(['Welcome to 2027', '/academics/timetable'], [$notice->title, $notice->delivery_payload['link']]);
        $this->assertStringContainsString('13 Jan 2027', $notice->message);
        $users = OutboxEvent::where('type', 'notification.requested')->where('payload->notification_id', $notice->id)->sole()->payload['user_ids'];
        $ids = $this->schoolA->ids;
        sort($users);
        $expected = [$ids['asha_user'], $ids['farid_user'], $ids['mona_user']];
        sort($expected);
        $this->assertSame($expected, $users, 'Asha and her guardians; not Ben, who left, nor his guardian');
        $this->assertSame(3, $result['welcomed']);
    }

    public function test_no_welcome_unless_chosen(): void
    {
        $result = app(NewYearService::class)->switchTo(SessionYear::findOrFail($this->year), SessionYear::findOrFail($this->next), []);

        $this->assertSame(0, $result['welcomed']);
        $this->assertSame(0, Notification::where('type', NewYearService::WELCOME)->count());
    }

    public function test_the_wizard_sends_the_welcome_and_checks_class_grades(): void
    {
        $checks = collect(app(NewYearService::class)->yearEndChecklist(SessionYear::findOrFail($this->year), SessionYear::findOrFail($this->next), $this->superAdmin()))->keyBy('key');
        $this->assertFalse($checks['grades']['ok']);
        $this->assertStringContainsString('1 class has no grade', $checks['grades']['detail']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => GradeLevel::where('code', '8')->value('id')]);
        $checks = collect(app(NewYearService::class)->yearEndChecklist(SessionYear::findOrFail($this->year), SessionYear::findOrFail($this->next), $this->superAdmin()))->keyBy('key');
        $this->assertTrue($checks['grades']['ok']);

        $this->get(route('new-year.index'))->assertOk()->assertSee('Welcome families to 2027');
        $this->postJson(route('new-year.make-current'), ['year_id' => $this->next, 'carry' => [], 'welcome' => 1])->assertOk()->assertJsonPath('data.welcomed', 3);
    }
}
