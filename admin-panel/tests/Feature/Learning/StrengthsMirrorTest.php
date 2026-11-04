<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\Growth\MasteryEvidence;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeMemory;
use App\Models\Learning\StrengthsMirrorShare;
use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorSession;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\Platform\AuditLog;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolModule;
use App\Services\Activities\ActivityGroups;
use App\Services\Growth\MasteryRecompute;
use App\Services\Learning\Strengths\StrengthsMirror;
use App\Services\Learning\Strengths\StrengthsShares;
use App\Services\Pathways\CareerProfiles;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\DemoSchool;
use Tests\TestCase;

/**
 * Action Plan 4.8, the interests and strengths mirror:
 * engineering/specs/learning/strengths-mirror.md.
 *
 * UsesPracticeScenario: on 10 February 2026 Asha is in Grade 7 (junior) and
 * in Tara's maths group (topics Number foundation, Fractions, Decimals);
 * Farid is her approving guardian. Ben is in the same class but not in the
 * group; Theo teaches another class. School B mirrors everything, marked
 * "Baobab".
 */
class StrengthsMirrorTest extends TestCase
{
    use UsesPracticeScenario;
    use ValidatesOpenApiResponses;

    private const SECRET = 'Synthetic private tutor question about home';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        foreach (['strengths_mirror', 'tutor', 'groups', 'peer_tutoring', 'pathway'] as $module) {
            SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
        }
        app(ModuleGate::class)->forget();
        foreach (['ben', 'tara', 'theo', 'mona', 'gina'] as $name) {
            $this->actor($name)->forceFill(['password_change_required' => false, 'email_verified_at' => now(), 'status' => 1])->save();
        }
        // Ben is approved by Gina; Mona is Asha's mother but her link cannot approve.
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('gina')->id], ['relationship' => 'guardian', 'status' => 'active', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->actor('gina')->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('mona')->id], ['relationship' => 'mother', 'status' => 'active', 'can_approve' => false]);
        Http::preventStrayRequests();
    }

    private function as(string $who, ?DemoSchool $school = null, string $schoolId = '1'): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $school ??= $this->schoolA;

        return $this->withToken($school->tokens[$who])->withHeaders(['X-School-Id' => $schoolId]);
    }

    private function mirror(string $who = 'asha'): TestResponse
    {
        return $this->as($who)->getJson('/api/v2/strengths');
    }

    private function share(string $recipient, array $sections = ['growing', 'remembering', 'explored', 'activities', 'reading', 'might_enjoy'], array $extra = [], string $who = 'asha'): TestResponse
    {
        return $this->as($who)->postJson('/api/v2/strengths/shares', $extra + ['operation_id' => (string) Str::uuid(),
            'recipient_user_id' => $this->actor($recipient)->id, 'sections' => $sections]);
    }

    private function topic(int $index): int
    {
        return (int) $this->nodes[$this->keys[$index]];
    }

    private function evidence(int $topic, string $at, int $achieved, int $times): void
    {
        $grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        for ($i = 0; $i < $times; $i++) {
            MasteryEvidence::create(['subject_id' => $this->subject, 'learner_id' => $this->schoolA->ids['asha'], 'topic_id' => $this->topic($topic),
                'grade_level_id' => $grade, 'session_year_id' => $this->year, 'audience' => 'practice', 'source_type' => 'practice_review',
                'source_key' => 'synthetic:'.Str::uuid(), 'achieved_units' => $achieved, 'maximum_units' => 1, 'observed_at' => $at]);
        }
    }

    /**
     * Fractions rises from level 0 to level 2 (growth). Decimals stays at the
     * top level throughout (high, but no growth). Number foundation has only
     * recent evidence (a first result is not growth).
     */
    private function growth(): void
    {
        $this->evidence(1, '2025-12-01 08:00:00', 0, 2);
        $this->evidence(1, '2026-02-05 08:00:00', 1, 3);
        $this->evidence(2, '2025-12-01 08:00:00', 1, 3);
        $this->evidence(2, '2026-02-05 08:00:00', 1, 2);
        $this->evidence(0, '2026-02-05 08:00:00', 1, 3);
        app(MasteryRecompute::class)->learner($this->schoolA->ids['asha'], $this->subject);
    }

    /** A stable Decimals memory (remembered) and a fresh Fractions one (not yet). */
    private function memories(): void
    {
        $this->approve();
        $fractions = PracticeItem::query()->latest('id')->firstOrFail();
        $this->approve(['topic_id' => $this->topic(2), 'definition' => ['kind' => 'number', 'prompt' => 'One quarter as a decimal?',
            'explanation' => 'Divide one by four.', 'options' => [], 'answers' => ['0.25']]]);
        $decimals = PracticeItem::query()->latest('id')->firstOrFail();
        $state = fn (float $stability, int $rating) => ['version' => 'fsrs', 'rating' => $rating, 'difficulty' => 5.0, 'stability_days' => $stability,
            'retrievability_before' => null, 'interval_days' => (int) $stability];
        PracticeMemory::create(['learner_id' => $this->schoolA->ids['asha'], 'practice_item_id' => $decimals->id, 'state' => $state(20.0, 3),
            'reviewed_on' => '2026-02-08', 'due_on' => '2026-02-28']);
        PracticeMemory::create(['learner_id' => $this->schoolA->ids['asha'], 'practice_item_id' => $fractions->id, 'state' => $state(2.4, 3),
            'reviewed_on' => '2026-02-08', 'due_on' => '2026-02-10']);
    }

    /** A tutor session Asha started on Fractions, with a private message. */
    private function tutor(int $topic = 1): void
    {
        $session = TutorSession::create(['id' => (string) Str::uuid(), 'learner_id' => $this->schoolA->ids['asha'], 'user_id' => $this->actor('asha')->id,
            'teaching_group_id' => $this->group, 'subject_id' => $this->subject, 'session_year_id' => $this->year, 'operation_id' => (string) Str::uuid(),
            'request_hash' => str_repeat('c', 64), 'source_version_ids' => [$this->source], 'topic_id' => $this->topic($topic)]);
        TutorMessage::create(['id' => (string) Str::uuid(), 'tutor_session_id' => $session->id, 'user_id' => $this->actor('asha')->id,
            'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('d', 64), 'learner_text' => self::SECRET,
            'response' => ['explanation' => 'Synthetic private tutor answer'], 'state' => 'completed', 'attempt_id' => (string) Str::uuid(), 'lease_until' => now()]);
    }

    /** Asha captains the chess club and has tutored Ben in Decimals. */
    private function activities(): void
    {
        $admin = $this->superAdmin();
        $admin->givePermissionTo('activity-groups-manage');
        $group = app(ActivityGroups::class)->save($admin, null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year,
            'parent_id' => null, 'name' => 'Chess club', 'discipline' => 'Chess', 'season' => 'All year', 'starts_on' => '2026-01-10', 'ends_on' => '2026-12-10',
            'gender' => 'any', 'minimum_grade' => null, 'maximum_grade' => null, 'minimum_age' => null, 'maximum_age' => null, 'active' => true]);
        app(ActivityGroups::class)->allocation($admin, (int) $group['id'], null, false, ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'starts_on' => '2026-01-10', 'ends_on' => '2026-12-10', 'reason' => 'Synthetic chess allocation', 'learner_id' => $this->schoolA->ids['asha'], 'role' => 'captain']);
        PeerTutoringSession::create(['session_year_id' => $this->year, 'teaching_group_id' => $this->group, 'topic_id' => $this->topic(2),
            'tutor_learner_id' => $this->schoolA->ids['asha'], 'tutee_learner_id' => $this->schoolA->ids['ben'], 'supervisor_user_id' => $this->actor()->id,
            'starts_at' => '2026-02-03 12:00:00', 'ends_at' => '2026-02-03 13:00:00', 'venue' => 'Library', 'status' => 'completed', 'revision' => 2, 'verified_minutes' => 30]);
        app(ModuleGate::class)->forget();
    }

    private function career(): array
    {
        return app(CareerProfiles::class)->save($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'status' => 'published',
            'title' => 'Synthetic actuary', 'summary' => 'Synthetic source fixture: works with numbers and risk.', 'source_title' => 'Synthetic 2026 career guide',
            'source_url' => 'https://careers.example.test/actuary', 'source_year' => 2026, 'reviewed_on' => '2026-02-01', 'expires_on' => '2026-12-31',
            'details' => ['subjects' => [['subject_ids' => [$this->subject], 'minimum_bp' => null, 'requirement' => 'recommended']],
                'topic_ids' => [$this->topic(1)],
                'routes' => [['type' => 'university', 'title' => 'Synthetic BSc Actuarial Science', 'provider' => 'Synthetic university',
                    'entry_note' => 'As the source states.', 'source_title' => 'Synthetic prospectus', 'source_url' => 'https://uni.example.test/actuarial', 'programme_id' => null]]]]);
    }

    private function everything(): void
    {
        $this->growth();
        $this->memories();
        $this->tutor();
        $this->activities();
        $this->career();
    }

    /** No key or number in the payload invites a comparison; only ids are numbers. */
    private function assertNothingComparative(array $data, string $path = 'data'): void
    {
        foreach ($data as $key => $value) {
            $where = $path.'.'.$key;
            if (is_string($key)) {
                $this->assertDoesNotMatchRegularExpression('/score|level|percent|rank|average|mean|count|confidence|_bp$|position|compare|class|peer|total|evidence/i', $key, $where);
            }
            if (is_array($value)) {
                $this->assertNothingComparative($value, $where);
            } elseif (is_int($value) || is_float($value)) {
                $this->assertMatchesRegularExpression('/(^|_)id$/', (string) $key, "Only ids are numbers: {$where}");
            }
        }
    }

    private function refused(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('The strengths mirror was read by someone it does not belong to.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }

    public function test_the_learner_sees_their_own_growth_memory_choices_activities_and_ideas(): void
    {
        $this->everything();
        $data = $this->mirror()->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');

        $this->assertSame([1, $this->schoolA->ids['asha'], $this->actor('asha')->id], [$data['school_id'], $data['learner_id'], $data['reader_id']]);
        $sections = $data['sections'];
        // Velocity, not level: Fractions grew; Decimals is high but steady; a first result is not growth.
        $this->assertSame(['Fractions'], array_column($sections['growing']['items'], 'topic'));
        $this->assertSame('Where you are growing', $sections['growing']['title']);
        // Remembered: the stable Decimals memory, not the fresh Fractions one.
        $this->assertSame(['Decimals'], array_column($sections['remembering']['items'], 'topic'));
        $this->assertSame([['topic_id' => $this->topic(1), 'topic' => 'Fractions']], $sections['explored']['items'][0]['topics']);
        $this->assertSame([['activity_group_id' => $sections['activities']['items'][0]['activity_group_id'], 'name' => 'Chess club', 'discipline' => 'Chess',
            'season' => 'All year', 'role' => 'captain']], $sections['activities']['items']);
        $this->assertSame(['Leading others in Chess club', 'Helping others learn Decimals'], array_column($sections['activities']['skills'], 'label'));
        // Reading is a clear, empty seam until the reading library is built.
        $this->assertFalse($sections['reading']['available']);
        $this->assertSame([], $sections['reading']['items']);
        $idea = $sections['might_enjoy']['items'][0];
        $this->assertSame(['Synthetic actuary', 'Fractions', 'https://careers.example.test/actuary'], [$idea['title'], $idea['because'], $idea['source_url']]);
        $this->assertStringContainsString('never a plan made for you', $sections['might_enjoy']['message']);
        $this->assertNothingComparative($data);
        $this->assertStringNotContainsString(self::SCHOOL_B_MARKER, json_encode($data));
        // Reading the mirror writes nothing.
        $this->assertSame(0, StrengthsMirrorShare::count());
    }

    public function test_tutor_conversations_never_appear_only_the_topics_chosen(): void
    {
        $this->tutor(1);
        $this->tutor(2);
        $mirror = $this->mirror()->assertOk();
        $json = $mirror->getContent();
        $this->assertStringNotContainsString(self::SECRET, $json);
        $this->assertStringNotContainsString('Synthetic private tutor answer', $json);
        $this->assertSame(['Decimals', 'Fractions'], array_column($mirror->json('data.sections.explored.items.0.topics'), 'topic'));
        $this->assertSame(['subject_id', 'subject', 'topics'], array_keys($mirror->json('data.sections.explored.items.0')));

        $share = $this->share('farid', ['explored'])->assertOk()->json('data');
        $shared = $this->as('farid')->getJson('/api/v2/strengths/shared-with-me/'.$share['id'])->assertOk()->getContent();
        $this->assertStringNotContainsString(self::SECRET, $shared);
        $this->assertStringContainsString('Fractions', $shared);
        // The stored copy is encrypted, so the topic names are not readable in the table.
        $this->assertStringNotContainsString('Fractions', (string) DB::table('strengths_mirror_shares')->value('snapshot'));
    }

    public function test_guardians_teachers_leaders_and_hq_cannot_read_the_mirror_without_a_share(): void
    {
        $this->everything();
        foreach (['farid', 'mona', 'tara', 'theo'] as $who) {
            $this->mirror($who)->assertForbidden();
            $this->as($who)->getJson('/api/v2/strengths/recipients')->assertForbidden();
            $this->as($who)->getJson('/api/v2/strengths/shares')->assertForbidden();
            $this->assertSame([], $this->as($who)->getJson('/api/v2/strengths/shared-with-me')->assertOk()->json('data.shares'));
        }
        // A school leader (the school's super admin), with or without an HQ identity.
        $leader = $this->superAdmin();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->withToken($leader->createToken('test')->plainTextToken)->withHeaders(['X-School-Id' => '1'])->getJson('/api/v2/strengths')->assertForbidden();
        Sanctum::actingAs($leader);
        $this->refused(fn () => app(StrengthsMirror::class)->forLearner($leader), 403);
        PlatformStaffIdentity::create(['user_id' => $leader->id, 'active' => true]);
        $this->refused(fn () => app(StrengthsMirror::class)->forLearner($leader), 403);
        $this->refused(fn () => app(StrengthsShares::class)->sharedWithMe($leader), 403);

        // The guard: another service (or a job) cannot pass the learner's account in for someone else.
        Sanctum::actingAs($this->actor('tara'));
        $this->refused(fn () => app(StrengthsMirror::class)->forLearner($this->actor('asha')), 403);
        $this->app['auth']->forgetGuards();
        $this->refused(fn () => app(StrengthsMirror::class)->forLearner($this->actor('asha')), 403);
        $this->assertSame(0, StrengthsMirrorShare::count());
    }

    public function test_a_share_to_an_approving_guardian_is_a_read_only_snapshot_and_audited(): void
    {
        $this->growth();
        $recipients = $this->as('asha')->getJson('/api/v2/strengths/recipients')->assertOk()->json('data.recipients');
        $this->assertSame([[$this->actor('farid')->id, 'guardian'], [$this->actor('tara')->id, 'teacher']],
            array_map(fn ($row) => [$row['user_id'], $row['kind']], $recipients), 'Mona cannot approve, so she is not offered; Theo does not teach Asha.');

        $operation = (string) Str::uuid();
        $share = $this->share('farid', ['growing', 'explored'], ['operation_id' => $operation, 'days' => 14])->assertOk()->json('data');
        $this->assertSame(['open', 'guardian', ['growing', 'explored']], [$share['status'], $share['kind'], $share['sections']]);
        // A retry returns the same share; the same operation for a different share is refused.
        $this->assertSame($share['id'], $this->share('farid', ['growing', 'explored'], ['operation_id' => $operation, 'days' => 14])->assertOk()->json('data.id'));
        $this->share('farid', ['growing'], ['operation_id' => $operation])->assertStatus(409);
        $this->assertSame(1, StrengthsMirrorShare::count());

        $list = $this->as('farid')->getJson('/api/v2/strengths/shared-with-me')->assertOk()->json('data.shares');
        $this->assertSame([$share['id']], array_column($list, 'id'));
        $view = $this->as('farid')->getJson('/api/v2/strengths/shared-with-me/'.$share['id'])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');
        $this->assertSame(['growing', 'explored'], array_keys($view['mirror']['sections']), 'Only the sections Asha chose.');
        $this->assertSame(['Fractions'], array_column($view['mirror']['sections']['growing']['items'], 'topic'));
        $this->assertSame('2026-02-10', $view['mirror']['as_of']);
        $this->assertNothingComparative($view['mirror']);

        // A snapshot, not live data: Asha's mirror moves on, Farid's copy does not.
        $this->tutor();
        $this->assertNotSame([], $this->mirror()->json('data.sections.explored.items'));
        $again = $this->as('farid')->getJson('/api/v2/strengths/shared-with-me/'.$share['id'])->assertOk()->json('data.mirror');
        $this->assertSame($view['mirror'], $again);

        // Asha sees when it was viewed; every step is audited without the copy's content.
        $mine = $this->as('asha')->getJson('/api/v2/strengths/shares')->assertOk()->json('data.shares.0');
        $this->assertNotNull($mine['last_viewed_at']);
        $actions = AuditLog::query()->where('subject_type', (new StrengthsMirrorShare)->getMorphClass())->pluck('action')->all();
        $this->assertSame(['strengths.share_created', 'strengths.share_viewed', 'strengths.share_viewed'], $actions);
        $this->assertStringNotContainsString('Fractions', json_encode(AuditLog::query()->where('action', 'like', 'strengths.%')->get(['before', 'after'])));

        // Read-only: there is no way for the viewer to change or end it.
        $this->as('farid')->postJson('/api/v2/strengths/shares/'.$share['id'].'/revoke')->assertForbidden();
        $this->assertSame('open', $this->as('asha')->getJson('/api/v2/strengths/shares')->json('data.shares.0.status'));
    }

    public function test_a_share_to_a_teacher_who_teaches_the_learner_works_and_others_are_refused(): void
    {
        $this->growth();
        $share = $this->share('tara', ['growing'])->assertOk()->json('data');
        $this->assertSame('teacher', $share['kind']);
        $this->as('tara')->getJson('/api/v2/strengths/shared-with-me/'.$share['id'])->assertOk()->assertJsonPath('data.mirror.sections.growing.items.0.topic', 'Fractions');

        // Another teacher, the other guardian, a non-approving guardian, another learner and an unknown id are refused with nothing written.
        foreach (['theo', 'mona', 'ben'] as $who) {
            $this->share($who)->assertStatus(422)->assertJsonPath('data.errors.recipient_user_id.0', 'Choose someone from your list.');
        }
        $this->as('asha')->postJson('/api/v2/strengths/shares', ['operation_id' => (string) Str::uuid(), 'recipient_user_id' => 999999, 'sections' => ['growing']])->assertStatus(422);
        $this->assertSame(1, StrengthsMirrorShare::count());
        // Nobody but the named viewer reaches the copy.
        foreach (['theo', 'farid', 'mona'] as $who) {
            $this->as($who)->getJson('/api/v2/strengths/shared-with-me/'.$share['id'])->assertNotFound();
        }
    }

    public function test_a_revoked_or_expired_share_is_refused(): void
    {
        $first = $this->share('farid', ['growing'])->assertOk()->json('data');
        $this->as('asha')->postJson('/api/v2/strengths/shares/'.$first['id'].'/revoke')->assertOk()->assertJsonPath('data.status', 'revoked');
        $this->as('asha')->postJson('/api/v2/strengths/shares/'.$first['id'].'/revoke')->assertOk()->assertJsonPath('data.status', 'revoked');
        $this->as('farid')->getJson('/api/v2/strengths/shared-with-me/'.$first['id'])->assertNotFound();
        $this->assertSame([], $this->as('farid')->getJson('/api/v2/strengths/shared-with-me')->json('data.shares'));
        $this->assertSame(1, AuditLog::query()->where('action', 'strengths.share_revoked')->count());

        $second = $this->share('farid', ['growing'], ['days' => 3])->assertOk()->json('data');
        $this->as('farid')->getJson('/api/v2/strengths/shared-with-me/'.$second['id'])->assertOk();
        $this->travel(4)->days();
        $this->as('farid')->getJson('/api/v2/strengths/shared-with-me/'.$second['id'])->assertNotFound();
        $this->assertSame('expired', collect($this->as('asha')->getJson('/api/v2/strengths/shares')->json('data.shares'))->firstWhere('id', $second['id'])['status']);
        // Shares last at most 30 days.
        $this->share('farid', ['growing'], ['days' => 31])->assertStatus(422);

        // A guardian who loses the approving link loses the share too.
        $third = $this->share('farid', ['growing'])->assertOk()->json('data');
        GuardianLink::query()->where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $this->actor('farid')->id)->update(['can_approve' => false]);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('mona')->id], ['can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->actor('mona')->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        $this->as('farid')->getJson('/api/v2/strengths/shared-with-me/'.$third['id'])->assertNotFound();
    }

    public function test_another_learner_never_reaches_this_learners_mirror_or_shares(): void
    {
        $this->everything();
        $share = $this->share('farid', ['growing'])->assertOk()->json('data');

        $ben = $this->mirror('ben')->assertOk()->json('data');
        $this->assertSame($this->schoolA->ids['ben'], $ben['learner_id']);
        foreach (['growing', 'remembering', 'explored', 'activities', 'might_enjoy'] as $section) {
            $this->assertSame([], $ben['sections'][$section]['items'], $section);
        }
        $this->assertStringNotContainsString('Chess club', json_encode($ben));
        $this->assertSame([], $this->as('ben')->getJson('/api/v2/strengths/shares')->json('data.shares'));
        $this->as('ben')->postJson('/api/v2/strengths/shares/'.$share['id'].'/revoke')->assertNotFound();
        $this->as('ben')->getJson('/api/v2/strengths/shared-with-me/'.$share['id'])->assertForbidden();
        $this->assertSame('open', $this->as('asha')->getJson('/api/v2/strengths/shares')->json('data.shares.0.status'));
    }

    public function test_school_b_never_reaches_school_a_and_a_teacher_at_another_school_cannot_be_named(): void
    {
        $this->everything();
        $share = $this->share('tara', ['growing'])->assertOk()->json('data');

        // A School B teacher (same first name) cannot be named, and nothing is written.
        $this->as('asha')->postJson('/api/v2/strengths/shares', ['operation_id' => (string) Str::uuid(), 'recipient_user_id' => $this->schoolB->ids['tara_user'],
            'sections' => ['growing']])->assertStatus(422);
        $this->assertSame(1, StrengthsMirrorShare::count());

        // School B accounts asking for School A are refused before anything is read.
        foreach (['asha', 'tara', 'farid'] as $who) {
            $this->as($who, $this->schoolB, '1')->getJson('/api/v2/strengths')->assertForbidden();
            $this->as($who, $this->schoolB, '1')->getJson('/api/v2/strengths/shared-with-me/'.$share['id'])->assertForbidden();
        }
        // In their own school, School A's share id does not exist.
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () {
            SchoolModule::updateOrCreate(['module' => 'strengths_mirror'], ['in_package' => true, 'enabled' => true]);
        });
        $this->as('tara', $this->schoolB, (string) self::SCHOOL_B_ID)->getJson('/api/v2/strengths/shared-with-me/'.$share['id'])->assertNotFound();
        $this->assertSame([], $this->as('tara', $this->schoolB, (string) self::SCHOOL_B_ID)->getJson('/api/v2/strengths/shared-with-me')->json('data.shares'));
        // School A's own teacher still sees it, in School A.
        $this->as('tara')->getJson('/api/v2/strengths/shared-with-me/'.$share['id'])->assertOk();
    }

    public function test_the_module_is_off_by_default_and_each_source_follows_its_own_switch(): void
    {
        $this->assertFalse(config('modules.strengths_mirror.default'));
        $this->assertSame(['junior', 'high_school'], config('modules.strengths_mirror.stages'));
        $this->everything();

        SchoolModule::where('module', 'daily_practice')->update(['enabled' => false]);
        SchoolModule::where('module', 'pathway')->update(['enabled' => false]);
        $sections = $this->mirror()->assertOk()->json('data.sections');
        $this->assertFalse($sections['remembering']['available']);
        $this->assertSame('This is not switched on at your school yet.', $sections['remembering']['message']);
        $this->assertFalse($sections['might_enjoy']['available']);
        $this->assertTrue($sections['growing']['available']);

        SchoolModule::where('module', 'strengths_mirror')->update(['enabled' => false]);
        $this->mirror()->assertNotFound();
        $this->share('farid')->assertNotFound();
        $this->as('farid')->getJson('/api/v2/strengths/shared-with-me')->assertNotFound();
        Sanctum::actingAs($this->actor('asha'));
        $this->refused(fn () => app(StrengthsMirror::class)->forLearner($this->actor('asha')), 403);
    }

    public function test_an_early_years_learner_has_no_mirror(): void
    {
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => (int) GradeLevel::where('code', '1')->value('id')]);
        app(SchoolStages::class)->forget();

        $this->mirror()->assertNotFound();
        $this->as('asha')->getJson('/api/v2/strengths/recipients')->assertNotFound();
        Sanctum::actingAs($this->actor('asha'));
        $this->refused(fn () => app(StrengthsMirror::class)->forLearner($this->actor('asha')), 403);
        $this->assertSame(0, StrengthsMirrorShare::count());
    }

    public function test_requests_are_strict(): void
    {
        $this->as('asha')->getJson('/api/v2/strengths?learner_id='.$this->schoolA->ids['ben'])->assertStatus(422);
        $this->share('farid', [])->assertStatus(422);
        $this->share('farid', ['growing', 'diary'])->assertStatus(422);
        $this->share('farid', ['growing', 'growing'])->assertStatus(422);
        $this->share('farid', ['growing'], ['learner_id' => $this->schoolA->ids['ben']])->assertStatus(422);
        $this->as('asha')->postJson('/api/v2/strengths/shares/1/revoke', ['reason' => 'x'])->assertStatus(422);
        $this->assertSame(0, StrengthsMirrorShare::count());
    }

    /**
     * The explicit guard: nothing outside this feature reads mirror data or
     * shares, so they cannot feed streaming, placement, risk or reports.
     */
    public function test_no_other_code_reads_the_mirror_or_its_shares(): void
    {
        $allowed = [
            'app/Services/Learning/Strengths/StrengthsAccess.php', 'app/Services/Learning/Strengths/StrengthsMirror.php',
            'app/Services/Learning/Strengths/StrengthsShares.php', 'app/Models/Learning/StrengthsMirrorShare.php',
            'app/Http/Controllers/Api/V2/Learning/StrengthsMirrorController.php', 'app/Http/Requests/Learning/StrengthsRequest.php',
            'app/Services/Platform/Tenancy/SchoolOwnedTables.php', 'routes/api_v2.php',
        ];
        $found = [];
        foreach (['app', 'routes', 'database/seeders'] as $root) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($root), \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $relative = str_replace(base_path().'/', '', $file->getPathname());
                $source = (string) file_get_contents($file->getPathname());
                if (! in_array($relative, $allowed, true) && preg_match('/Strengths(Mirror|Shares|Access|Request)|strengths_mirror_shares/', $source)) {
                    $found[] = $relative;
                }
            }
        }
        $this->assertSame([], $found, 'Only the learner\'s own API reaches the strengths mirror.');
    }
}
