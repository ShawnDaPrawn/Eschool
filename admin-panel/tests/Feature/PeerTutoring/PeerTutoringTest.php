<?php

declare(strict_types=1);

namespace Tests\Feature\PeerTutoring;

use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryRecord;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\PeerTutoring\ServiceHour;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Recognition\HousePoint;
use App\Models\Recognition\HousePointLimit;
use App\Models\Students;
use App\Models\User;
use App\Services\PeerTutoring\PeerMatches;
use App\Services\PeerTutoring\PeerQueries;
use App\Services\PeerTutoring\PeerSessions;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\HousePoints;
use App\Services\Recognition\RecognitionCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class PeerTutoringTest extends TestCase
{
    use UsesMasteryScenario, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T08:00:00Z'));
        config(['identity.approval_enforced' => true]);
        SchoolModule::updateOrCreate(['module' => 'peer_tutoring'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        foreach (['tara', 'theo', 'asha', 'ben', 'farid', 'gina'] as $name) {
            $this->actor($name)->forceFill(['password_change_required' => false, 'email_verified_at' => now()])->save();
        }
        Enrolment::create(['learner_id' => $this->schoolA->ids['ben'], 'class_section_id' => $this->schoolA->ids['section_a'],
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        TeachingGroupMembership::create(['teaching_group_id' => $this->group, 'learner_id' => $this->schoolA->ids['ben'],
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up']);
        foreach (['asha' => 'farid', 'ben' => 'gina'] as $learner => $guardian) {
            GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids[$learner], 'guardian_user_id' => $this->actor($guardian)->id],
                ['relationship' => 'guardian', 'status' => 'active', 'can_approve' => true]);
            app(ApprovalService::class)->decide($this->actor($guardian), Students::findOrFail($this->schoolA->ids[$learner]), 'approve', config('identity.consent_version'));
        }
        foreach ($this->nodes as $topic) {
            MasteryRecord::create(['subject_id' => $this->subject, 'learner_id' => $this->schoolA->ids['ben'], 'topic_id' => $topic,
                'audience' => 'staff', 'level' => 4, 'score_bp' => 9000, 'confidence_bp' => 6000, 'evidence_count' => 2, 'computed_at' => now()]);
        }
    }

    private function input(array $changes = []): array
    {
        return $changes + ['operation_id' => (string) Str::uuid(), 'teaching_group_id' => $this->group,
            'tutor_learner_id' => $this->schoolA->ids['ben'], 'tutee_learner_id' => $this->schoolA->ids['asha'], 'topic_id' => $this->nodes[$this->keys[1]],
            'venue' => 'Supervised school library', 'starts_at' => '2026-02-10T14:00', 'ends_at' => '2026-02-10T15:00'];
    }

    private function create(array $changes = []): PeerTutoringSession
    {
        return app(PeerSessions::class)->create($this->actor(), $this->input($changes));
    }

    private function points(int $cap = 100): int
    {
        SchoolModule::updateOrCreate(['module' => 'house_points'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $catalog = app(RecognitionCatalog::class);
        $admin = $this->superAdmin();
        $op = fn (array $data) => ['operation_id' => (string) Str::uuid()] + $data;
        $house = $catalog->house($admin, $op(['name' => 'Synthetic service house', 'emblem' => 'star', 'primary_colour' => '#123456', 'secondary_colour' => '#ffffff', 'active' => true]));
        $category = $catalog->category($admin, $op(['name' => 'Supervised service', 'default_points' => 5, 'minimum_points' => 1, 'maximum_points' => 10, 'active' => true]));
        $catalog->membership($admin, $op(['learner_id' => $this->schoolA->ids['ben'], 'house_id' => $house->id, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => null, 'active' => true, 'reason' => 'Synthetic current house']));
        $catalog->configure($admin, 'house_points', $op(['revision' => $catalog->settings()->revision, 'daily_staff_cap' => $cap,
            'public_points_allowed' => false, 'principal_approval_required' => true]));

        return (int) $category->id;
    }

    private function refused(callable $call, array $codes = [403, 404, 409, 422]): void
    {
        try {
            $call();
            $this->fail('A refused peer-session action succeeded.');
        } catch (ModelNotFoundException|HttpExceptionInterface $error) {
            $this->assertContains($error instanceof HttpExceptionInterface ? $error->getStatusCode() : 404, $codes);
        }
    }

    public function test_matching_uses_real_topic_evidence_and_excludes_self_and_weak_peers(): void
    {
        $matches = app(PeerMatches::class)->forLearner($this->actor(), $this->group, $this->schoolA->ids['asha']);
        $this->assertNotEmpty($matches);
        $this->assertSame($this->schoolA->ids['ben'], $matches[0]['tutor_learner_id']);
        MasteryRecord::where('learner_id', $this->schoolA->ids['ben'])->update(['level' => 1]);
        $this->assertSame([], app(PeerMatches::class)->forLearner($this->actor(), $this->group, $this->schoolA->ids['asha']));
        $this->refused(fn () => $this->create(), [409]);
        $this->assertSame(0, PeerTutoringSession::count());
    }

    public function test_exact_creation_replay_still_recovers_after_the_session_start_without_another_effect(): void
    {
        $input = $this->input();
        $first = app(PeerSessions::class)->create($this->actor(), $input);
        $this->travelTo(CarbonImmutable::parse('2026-02-10T12:30:00Z'));
        $again = app(PeerSessions::class)->create($this->actor(), $input);
        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, PeerTutoringSession::count());
        $this->refused(fn () => app(PeerSessions::class)->create($this->actor(), array_replace($input, ['venue' => 'Different submitted request'])), [409]);
    }

    public function test_overlapping_participant_or_supervisor_is_refused(): void
    {
        $this->create();
        $this->refused(fn () => $this->create(), [409]);
        $this->assertSame(1, PeerTutoringSession::count());
    }

    public function test_actual_completion_and_correction_append_integer_minutes_once_without_manufacturing_mastery(): void
    {
        $row = $this->create();
        $before = MasteryRecord::count();
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'minutes' => 45];
        $this->refused(fn () => app(PeerSessions::class)->change($this->actor(), $row->id, 'complete', $input), [409]);
        $this->travelTo(CarbonImmutable::parse('2026-02-10T14:00:00Z'));
        $result = app(PeerSessions::class)->change($this->actor(), $row->id, 'complete', $input);
        app(PeerSessions::class)->change($this->actor(), $row->id, 'complete', $input);
        $this->assertSame('completed', $result->status);
        $this->assertSame(45, (int) ServiceHour::sum('minutes'));
        $this->assertSame(1, ServiceHour::count());
        $this->assertSame($before, MasteryRecord::count());
        $correction = ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'Attendance was recorded against the wrong arrangement.'];
        app(PeerSessions::class)->change($this->actor(), $row->id, 'reverse', $correction);
        app(PeerSessions::class)->change($this->actor(), $row->id, 'reverse', $correction);
        $this->assertSame(0, (int) ServiceHour::sum('minutes'));
        $this->assertSame(2, ServiceHour::count());
        $this->assertSame(0, HousePoint::count());
        $this->expectException(HttpExceptionInterface::class);
        ServiceHour::firstOrFail()->update(['minutes' => 99]);
    }

    public function test_family_receipts_hide_the_other_learners_service_and_never_expose_mastery(): void
    {
        $row = $this->create();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T14:00:00Z'));
        app(PeerSessions::class)->change($this->actor(), $row->id, 'complete', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'minutes' => 45]);
        $tutor = app(PeerQueries::class)->read($this->actor('ben'), $row->id, 'student');
        $tutee = app(PeerQueries::class)->read($this->actor('farid'), $row->id, 'parent');
        $this->assertSame(45, $tutor['service_minutes']);
        $this->assertNull($tutee['service_minutes']);
        $this->assertFalse($tutee['service_for_reader']);
        $this->assertFalse($tutee['can_complete']);
        $this->assertArrayNotHasKey('level', $tutee);
        $this->assertArrayNotHasKey('confidence_bp', $tutee);
        $this->assertArrayNotHasKey('correction_reason', $tutee);
    }

    public function test_wrong_staff_or_family_cannot_match_schedule_or_confirm(): void
    {
        $row = $this->create();
        foreach (['theo', 'farid', 'asha'] as $name) {
            $this->refused(fn () => app(PeerMatches::class)->forLearner($this->actor($name), $this->group, $this->schoolA->ids['asha']));
            $this->refused(fn () => app(PeerSessions::class)->change($this->actor($name), $row->id, 'cancel',
                ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Refused']));
        }
        $this->assertSame('scheduled', $row->fresh()->status);
    }

    public function test_wrong_school_cannot_read_or_cancel_a_guessed_session(): void
    {
        $row = $this->create();
        app(CurrentSchool::class)->run(2, function () use ($row) {
            $this->refused(fn () => app(PeerQueries::class)->read($this->actor(), $row->id, 'staff'));
            $this->refused(fn () => app(PeerSessions::class)->change($this->actor(), $row->id, 'cancel',
                ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Refused']));
        });
        $this->assertSame('scheduled', $row->fresh()->status);
    }

    public function test_guardian_withdrawal_removes_family_access_and_new_match(): void
    {
        $row = $this->create();
        app(ApprovalService::class)->decide($this->actor('gina'), Students::findOrFail($this->schoolA->ids['ben']), 'withdraw', config('identity.consent_version'));
        $this->refused(fn () => app(PeerQueries::class)->read($this->actor('ben'), $row->id, 'student'));
        $this->assertSame([], app(PeerMatches::class)->forLearner($this->actor(), $this->group, $this->schoolA->ids['asha']));
        $this->assertSame([], app(PeerQueries::class)->configuration($this->actor('gina'), 'parent')['children']);
    }

    public function test_house_points_use_real_caps_and_reversals_without_duplicate_service_or_restored_allowance(): void
    {
        $category = $this->points();
        $row = $this->create();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T14:00:00Z'));
        $completion = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'minutes' => 45, 'point_category_id' => $category, 'points' => 5];
        $done = app(PeerSessions::class)->change($this->actor(), $row->id, 'complete', $completion);
        app(PeerSessions::class)->change($this->actor(), $row->id, 'complete', $completion);
        $this->assertNotNull($done->house_point_id);
        $this->assertSame(1, HousePoint::count());
        $this->assertSame(5, (int) HousePoint::sum('points'));
        $this->assertSame(1, ServiceHour::count());
        $this->assertNull(app(PeerQueries::class)->read($this->actor('farid'), $row->id, 'parent')['house_point_id']);
        $reverse = ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'Corrected the confirmed arrangement.'];
        app(PeerSessions::class)->change($this->actor(), $row->id, 'reverse', $reverse);
        app(PeerSessions::class)->change($this->actor(), $row->id, 'reverse', $reverse);
        $this->assertSame(2, HousePoint::count());
        $this->assertSame(0, (int) HousePoint::sum('points'));
        $this->assertSame(0, (int) ServiceHour::sum('minutes'));
        $this->assertSame(5, (int) HousePointLimit::sum('spent'));
    }

    public function test_point_cap_failure_rolls_back_the_entire_confirmation(): void
    {
        $category = $this->points(4);
        $row = $this->create();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T14:00:00Z'));
        $this->refused(fn () => app(PeerSessions::class)->change($this->actor(), $row->id, 'complete',
            ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'minutes' => 45, 'point_category_id' => $category, 'points' => 5]), [422]);
        $this->assertSame('scheduled', $row->fresh()->status);
        $this->assertSame(0, HousePoint::count());
        $this->assertSame(0, ServiceHour::count());
    }

    public function test_service_correction_does_not_reverse_an_already_corrected_point_twice(): void
    {
        $category = $this->points();
        $row = $this->create();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T14:00:00Z'));
        $row = app(PeerSessions::class)->change($this->actor(), $row->id, 'complete',
            ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'minutes' => 45, 'point_category_id' => $category, 'points' => 5]);
        app(HousePoints::class)->reverse($this->actor(), (int) $row->house_point_id,
            ['operation_id' => (string) Str::uuid(), 'reason' => 'Corrected through the recognition workbench.']);
        app(PeerSessions::class)->change($this->actor(), $row->id, 'reverse',
            ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'reason' => 'Correct the service record as well.']);
        $this->assertSame(2, HousePoint::count());
        $this->assertSame(0, (int) HousePoint::sum('points'));
        $this->assertSame(0, (int) ServiceHour::sum('minutes'));
        $this->assertSame('reversed', $row->fresh()->status);
    }

    public function test_readonly_allows_authorised_cancellation_but_not_new_scheduling(): void
    {
        $row = $this->create();
        SchoolModule::where('module', 'peer_tutoring')->update(['read_only' => true]);
        $this->refused(fn () => $this->create());
        $cancelled = app(PeerSessions::class)->change($this->actor(), $row->id, 'cancel',
            ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'No longer meeting.']);
        $this->assertSame('cancelled', $cancelled->status);
        $this->assertSame(0, ServiceHour::count());
    }

    private function api(User $actor, string $purpose, int $school = 1): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken($purpose)->plainTextToken)
            ->withHeaders(['X-School-Id' => (string) $school, 'X-Account-Id' => (string) $actor->id]);
    }

    public function test_http_staff_schedule_and_purpose_bound_family_reader_are_private(): void
    {
        $tokens = AppTokens::class;
        $this->api($this->actor(), $tokens::TEACHER)->getJson('/api/v2/peer-tutoring/configuration?persona=staff')
            ->assertOk()->assertJsonPath('data.reader_id', $this->actor()->id)->assertJsonPath('data.groups.0.id', $this->group);
        $reply = $this->api($this->actor(), $tokens::TEACHER)->postJson('/api/v2/peer-tutoring/sessions', ['persona' => 'staff'] + $this->input())
            ->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertJsonPath('data.session.status', 'scheduled');
        $id = $reply->json('data.session.id');
        $response = $this->api($this->actor('farid'), $tokens::PARENT)->getJson('/api/v2/peer-tutoring/sessions/'.$id.'?persona=parent')
            ->assertOk()->assertJsonPath('data.reader_id', $this->actor('farid')->id)->assertJsonPath('data.session.can_complete', false);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->api($this->actor('farid'), $tokens::PARENT)->postJson('/api/v2/peer-tutoring/sessions/'.$id.'/cancel',
            ['persona' => 'staff', 'operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Forbidden'])->assertForbidden();
        $this->assertSame('scheduled', PeerTutoringSession::findOrFail($id)->status);
    }

    public function test_http_query_writes_and_wrong_account_are_refused_without_effects(): void
    {
        $teacher = AppTokens::TEACHER;
        $this->api($this->actor(), $teacher)->postJson('/api/v2/peer-tutoring/sessions?persona=staff', $this->input())->assertUnprocessable();
        $this->api($this->actor(), $teacher)->withHeaders(['X-Account-Id' => (string) $this->actor('farid')->id])
            ->getJson('/api/v2/peer-tutoring/configuration?persona=staff')->assertForbidden();
        $this->assertSame(0, PeerTutoringSession::count());
    }

    public function test_known_parent_credential_cannot_borrow_its_accounts_teacher_persona(): void
    {
        $this->api($this->actor(), AppTokens::PARENT)
            ->getJson('/api/v2/peer-tutoring/configuration?persona=staff')->assertForbidden();
        $this->assertSame(0, PeerTutoringSession::count());
    }

    public function test_nonscalar_audience_is_a_private_input_refusal_not_a_server_error(): void
    {
        $this->api($this->actor(), AppTokens::TEACHER)
            ->getJson('/api/v2/peer-tutoring/configuration?persona[]=staff')->assertUnprocessable();
    }

    public function test_active_other_school_staff_cannot_read_or_cancel_a_guessed_session(): void
    {
        $row = $this->create();
        $other = app(CurrentSchool::class)->run(2, function () {
            foreach (['peer_tutoring', 'mastery'] as $module) {
                SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
            }
            SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
            $actor = User::findOrFail($this->schoolB->ids['tara_user']);
            $actor->forceFill(['password_change_required' => false])->save();

            return $actor;
        });
        $teacher = AppTokens::TEACHER;
        $this->api($other, $teacher, 2)->getJson('/api/v2/peer-tutoring/configuration?persona=staff')
            ->assertOk()->assertJsonPath('data.school_id', 2);
        $this->api($other, $teacher, 2)->getJson('/api/v2/peer-tutoring/sessions/'.$row->id.'?persona=staff')->assertNotFound();
        $this->api($other, $teacher, 2)->postJson('/api/v2/peer-tutoring/sessions/'.$row->id.'/cancel',
            ['persona' => 'staff', 'operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Refused'])->assertNotFound();
        $this->assertSame('scheduled', $row->fresh()->status);
    }
}
