<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Jobs\Platform\ApplyRetention;
use App\Models\Academics\AcademicExitPackage;
use App\Models\Academics\AcademicTransition;
use App\Models\Academics\AssessmentMark;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\ReportCard;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSchool;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\ProfileFile;
use App\Models\Platform\RetentionRule;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\CurriculumPlans;
use App\Services\Academics\MarkInputs;
use App\Services\Academics\MarkRecording;
use App\Services\Academics\Reports\ReportArtifacts;
use App\Services\Academics\Reports\ReportBatches;
use App\Services\Academics\Reports\ReportDesigns;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\Transitions\ExitArtifacts;
use App\Services\Academics\Transitions\ExitPackages;
use App\Services\Academics\Transitions\ExitSigning;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\Academics\Transitions\TransitionFlow;
use App\Services\Academics\Transitions\TransitionPolicies;
use App\Services\Academics\Transitions\TransitionQueries;
use App\Services\ParentPolicyService;
use App\Services\People\EnrolmentService;
use App\Services\People\Profiles\ProfileDefinitions;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;
use ZipArchive;

class TransitionEngineTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    private int $grade;

    private int $group;

    private int $membership;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 08:00:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Term::whereKey($this->schoolA->ids['term'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'marks_lock_on' => null]);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        app(EnrolmentService::class)->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_a'], $this->year, '2026-01-01', 'Synthetic starting enrolment');
        $this->group = $this->group('Starting mathematics');
        $this->membership = (int) app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids['asha'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up', 'reason' => 'Synthetic membership'], $this->group)->id;
    }

    private function teacher(): User
    {
        return User::findOrFail($this->schoolA->ids['tara_user']);
    }

    private function group(string $name, ?int $subject = null): int
    {
        return (int) app(TeachingGroups::class)->save($this->superAdmin(), ['name' => $name, 'subject_id' => $subject ?? $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true])->id;
    }

    private function input(string $kind, array $changes = []): array
    {
        return $changes + ['kind' => $kind, 'learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'effective_on' => '2026-02-10', 'reason' => 'Verified private educational change'];
    }

    private function execute(array $input): array
    {
        $flow = app(TransitionFlow::class);
        $preview = $flow->preview($this->superAdmin(), $input);

        return $flow->commit($this->superAdmin(), $input + ['fingerprint' => $preview['fingerprint'], 'operation_id' => (string) Str::uuid()]);
    }

    private function denied(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected refusal '.$status);
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        }
    }

    public function test_join_queues_no_money_and_never_activates_an_unapproved_account(): void
    {
        $learner = $this->schoolA->ids['ben'];
        $user = User::findOrFail($this->schoolA->ids['ben_user']);
        $user->update(['status' => 0]);
        $input = $this->input('join', ['learner_id' => $learner, 'class_section_id' => $this->schoolA->ids['section_a'], 'group_ids' => [$this->group], 'fee_prorata_requested' => true]);
        $result = $this->execute($input);
        $this->assertTrue($result['result']['fee_prorata_requested']);
        $this->assertFalse($result['result']['billing_posted']);
        $this->assertSame('2026-02-10', $result['result']['fee_prorata_from_on']);
        $this->assertSame(0, (int) $user->fresh()->status);
        $this->assertSame(0, (int) StudentSessions::where('student_id', $learner)->where('session_year_id', $this->year)->value('status'));
        $this->assertTrue(app(LearnerAcademicAccess::class)->allows(Students::findOrFail($learner)));
        $this->assertFalse(app(ApprovalService::class)->approved(Students::findOrFail($learner)));
        $raw = DB::table('academic_transitions')->where('id', $result['id'])->first();
        foreach (['reason', 'payload', 'result'] as $field) {
            $this->assertStringNotContainsString($input['reason'], (string) $raw->$field);
        }
        $this->assertCount(1, TeachingGroupMembership::where('learner_id', $learner)->get());
    }

    public function test_join_can_progress_before_subjects_are_allocated_and_future_access_starts_on_its_date(): void
    {
        $input = $this->input('join', ['learner_id' => $this->schoolA->ids['ben'], 'effective_on' => '2026-02-12', 'class_section_id' => $this->schoolA->ids['section_a'], 'group_ids' => [], 'fee_prorata_requested' => false]);
        $result = $this->execute($input);
        $this->assertSame([], $result['result']['membership_ids']);
        $learner = Students::findOrFail($input['learner_id']);
        $this->assertFalse(app(LearnerAcademicAccess::class)->allows($learner, '2026-02-11'));
        $this->assertTrue(app(LearnerAcademicAccess::class)->allows($learner, '2026-02-12'));
    }

    public function test_leave_keeps_inclusive_last_day_and_cancels_future_enrolments_without_erasing_history(): void
    {
        $next = SessionYear::forceCreate(['name' => '2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'default' => 0]);
        $future = app(EnrolmentService::class)->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_a'], (int) $next->id, '2027-01-01', 'Scheduled next year');
        $result = $this->execute($this->input('leave'));
        $this->assertContains((int) $future->id, $result['result']['cancelled_enrolment_ids']);
        $this->assertTrue(Enrolment::withTrashed()->findOrFail($future->id)->trashed());
        $this->assertSame(2, Enrolment::withTrashed()->where('learner_id', $this->schoolA->ids['asha'])->count());
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        $this->assertTrue(app(LearnerAcademicAccess::class)->allows($learner, '2026-02-10'));
        $this->assertFalse(app(LearnerAcademicAccess::class)->allows($learner, '2026-02-11'));
        $this->assertSame('2026-02-10', TeachingGroupMembership::findOrFail($this->membership)->ends_on->toDateString());
        $this->assertSame(0, (int) StudentSessions::where('student_id', $learner->id)->where('session_year_id', $next->id)->value('status'));
        $this->assertFalse($result['result']['records_deleted']);
        $this->assertSame(1, (int) $learner->user->status);
    }

    public function test_declared_leave_cannot_be_undone_by_a_legacy_projection_but_explicit_rejoin_can_open_a_new_interval(): void
    {
        $this->execute($this->input('leave', ['effective_on' => '2026-02-09']));
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        app(EnrolmentService::class)->place((int) $learner->id, $this->schoolA->ids['section_a'], $this->year, '2026-02-10', 'Old client import');
        $this->assertFalse(app(LearnerAcademicAccess::class)->allows($learner));
        Enrolment::where('learner_id', $learner->id)->whereDate('starts_on', '2026-02-10')->delete();
        $this->execute($this->input('join', ['class_section_id' => $this->schoolA->ids['section_a'], 'group_ids' => [], 'fee_prorata_requested' => false]));
        $this->assertTrue(app(LearnerAcademicAccess::class)->allows($learner));
        $this->assertSame(3, Enrolment::withTrashed()->where('learner_id', $learner->id)->count());
    }

    public function test_leave_is_possible_for_an_unmapped_legacy_homeroom(): void
    {
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => null]);
        $result = $this->execute($this->input('leave'));
        $this->assertNull($result['grade_level_id']);
        $this->assertSame('leave', $result['kind']);
    }

    public function test_exact_retry_is_immutable_and_changed_or_stale_requests_never_apply_twice(): void
    {
        $flow = app(TransitionFlow::class);
        $input = $this->input('subject_change', ['source_membership_id' => $this->membership, 'target_group_id' => $this->group('Other mathematics')]);
        $preview = $flow->preview($this->superAdmin(), $input);
        $body = $input + ['fingerprint' => $preview['fingerprint'], 'operation_id' => (string) Str::uuid()];
        $one = $flow->commit($this->superAdmin(), $body);
        $this->assertSame($one, $flow->commit($this->superAdmin(), $body));
        $this->assertSame(1, AcademicTransition::count());
        $this->denied(409, fn () => $flow->commit($this->superAdmin(), array_replace($body, ['reason' => 'Different reason'])));
        $this->denied(409, fn () => $flow->commit($this->superAdmin(), array_replace($body, ['operation_id' => (string) Str::uuid()])));
        $this->denied(409, fn () => AcademicTransition::firstOrFail()->update(['reason' => 'Rewrite history']));
        $this->denied(409, fn () => AcademicTransition::firstOrFail()->delete());
    }

    public function test_grade_defaults_and_stale_previews_are_versioned_and_carried_cannot_invent_a_different_subject_score(): void
    {
        $policies = app(TransitionPolicies::class);
        $saved = $policies->save($this->superAdmin(), $this->grade, ['join_mark_rule' => 'excused', 'change_mark_rule' => 'carried', 'revision' => 0]);
        $this->assertSame(1, $saved['revision']);
        $input = $this->input('subject_change', ['source_membership_id' => $this->membership, 'target_group_id' => $this->group('New science', $this->schoolA->ids['science'])]);
        try {
            app(TransitionFlow::class)->preview($this->superAdmin(), $input);
            $this->fail('Unrelated marks cannot carry');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('mark_rule_override', $error->errors());
        }
        $input['mark_rule_override'] = 'excused';
        $preview = app(TransitionFlow::class)->preview($this->superAdmin(), $input);
        $this->assertSame('excused', $preview['mark_rule']);
        $policies->save($this->superAdmin(), $this->grade, ['join_mark_rule' => 'catch_up', 'change_mark_rule' => 'catch_up', 'revision' => 1]);
        $this->denied(409, fn () => app(TransitionFlow::class)->commit($this->superAdmin(), $input + ['operation_id' => (string) Str::uuid(), 'fingerprint' => $preview['fingerprint']]));
        $this->assertSame(0, AcademicTransition::count());
        $this->denied(409, fn () => $policies->save($this->superAdmin(), $this->grade, ['join_mark_rule' => 'excused', 'change_mark_rule' => 'carried', 'revision' => 1]));
    }

    private function task(int $subject): int
    {
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), ['subject_id' => $subject, 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'title' => 'Transition task',
            'annual_components' => ['sba' => 10000], 'rounding' => 'half_up', 'absent_policy' => 'incomplete', 'topics' => [], 'assessments' => [['term_id' => $this->schoolA->ids['term'], 'components' => ['sba' => 10000],
                'tasks' => [['name' => 'Earlier task', 'type' => 'test', 'component' => 'sba', 'annual_component' => 'sba', 'annual_weight_bp' => 10000, 'weight_bp' => 10000, 'max_mark' => '20.00', 'due_on' => '2026-02-08', 'topic_keys' => [], 'questions' => []]]]]]);
        $plan = app(CurriculumPlans::class)->publish($this->superAdmin(), $plan->id, $plan->assessments->sole()->id, 1);

        return (int) $plan->assessments->sole()->tasks->sole()->id;
    }

    public function test_same_subject_carried_rule_reuses_exact_canonical_marks_without_copying_them(): void
    {
        $task = $this->task($this->schoolA->ids['maths']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->teacher()->id)->value('id'), 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Current subject teacher'], $this->group);
        app(MarkRecording::class)->capture($this->teacher(), $this->group, $task, ['rows' => [['learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => 0, 'disposition' => 'recorded', 'mark' => '15.00', 'question_marks' => []]]]);
        $original = AssessmentMark::sole()->getAttributes();
        $target = $this->group('Transferred mathematics');
        $result = $this->execute($this->input('subject_change', ['source_membership_id' => $this->membership, 'target_group_id' => $target, 'mark_rule_override' => 'carried']));
        $this->assertTrue($result['result']['canonical_marks_reused']);
        $this->assertFalse($result['result']['marks_copied']);
        $this->assertSame($original, AssessmentMark::sole()->getAttributes());
        $input = app(MarkInputs::class)->forLearner(TeachingGroup::findOrFail($target), $this->schoolA->ids['asha'], $this->schoolA->ids['term']);
        $this->assertSame(7500, app(MarkInputs::class)->calculate($input)['percentage_bp']);
        $this->assertSame('2026-02-09', TeachingGroupMembership::findOrFail($this->membership)->ends_on->toDateString());
    }

    public function test_join_defaults_explain_earlier_tasks_without_zero_scores_or_account_activation(): void
    {
        $this->task($this->schoolA->ids['maths']);
        foreach ([['catch_up', 'ben'], ['excused', 'cara']] as [$rule, $key]) {
            $learner = $this->schoolA->ids[$key];
            $this->execute($this->input('join', ['learner_id' => $learner, 'class_section_id' => $this->schoolA->ids['section_a'], 'group_ids' => [$this->group], 'fee_prorata_requested' => false, 'mark_rule_override' => $rule]));
            $input = app(MarkInputs::class)->forLearner(TeachingGroup::findOrFail($this->group), $learner, $this->schoolA->ids['term']);
            $this->assertSame($rule, $input['tasks'][0]['disposition']);
            $this->assertNull($input['tasks'][0]['mark_units']);
            $this->assertSame($this->schoolA->ids['class_a'], (int) Students::findOrFail($learner)->class_id);
        }
        $this->assertSame(0, AssessmentMark::count());
    }

    public function test_wrong_role_school_and_other_learner_membership_cannot_change_enrolments(): void
    {
        $input = $this->input('leave');
        $this->denied(403, fn () => app(TransitionFlow::class)->preview($this->teacher(), $input));
        $this->denied(404, fn () => app(TransitionFlow::class)->preview($this->superAdmin(), array_replace($input, ['learner_id' => $this->schoolB->ids['asha']])));
        $this->denied(404, fn () => app(TransitionFlow::class)->preview($this->superAdmin(), $this->input('subject_change', ['learner_id' => $this->schoolA->ids['ben'], 'source_membership_id' => $this->membership, 'target_group_id' => $this->group])));
        $this->assertSame(0, AcademicTransition::count());
    }

    public function test_dated_learner_access_is_checked_before_issuing_tokens_and_does_not_disable_parent_or_other_school_roles(): void
    {
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        $user = $learner->user;
        $guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        $guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::create(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id, 'relationship' => 'father', 'status' => 'active', 'can_approve' => true, 'can_see_fees' => true]);
        app(ApprovalService::class)->decide($guardian, $learner, 'approve', config('identity.consent_version'));
        $token = AppTokens::issue($user, AppTokens::STUDENT);
        $this->execute($this->input('leave', ['effective_on' => '2026-02-09']));
        $this->denied(403, fn () => AppTokens::issue($user, AppTokens::STUDENT));
        $this->withToken($token)->getJson('/api/v2/me/context')->assertForbidden();
        $this->assertSame(1, (int) $user->fresh()->status);
        app(CurrentSchool::class)->run(2, function () use ($user): void {
            $user->unsetRelation('roles');
            $user->assignRole('Student');
            SchoolMembership::where('user_id', $user->id)->where('school_id', 2)->update(['starts_on' => '2026-01-01']);
            $sample = Students::findOrFail($this->schoolB->ids['asha']);
            $second = Students::create(['user_id' => $user->id, 'class_id' => $sample->class_id, 'class_section_id' => $sample->class_section_id,
                'category_id' => $sample->category_id, 'admission_no' => 'CROSS-SCHOOL', 'roll_number' => 99, 'admission_date' => '2026-01-01']);
            $guardian = User::findOrFail($this->schoolB->ids['farid_user']);
            $guardian->forceFill(['email_verified_at' => now()])->save();
            GuardianLink::create(['learner_id' => $second->id, 'guardian_user_id' => $guardian->id, 'relationship' => 'father', 'status' => 'active', 'can_approve' => true, 'can_see_fees' => true]);
            app(ApprovalService::class)->decide($guardian, $second, 'approve', config('identity.consent_version'));
            $this->assertTrue(app(LearnerAcademicAccess::class)->allows($second));
        });
        $this->assertTrue($user->tokens()->exists());
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->withHeader('X-School-Id', '2')->getJson('/api/v2/me/context')->assertOk();
        app(CurrentSchool::class)->run(1, function () use ($user): void {
            $user->unsetRelation('roles');
            $user->assignRole('Parent');
        });
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        app(CurrentSchool::class)->set(1);
        $parentToken = AppTokens::issue(User::findOrFail($user->id), AppTokens::PARENT);
        $this->withToken($parentToken)->withHeader('X-School-Id', '1')->getJson('/api/v2/me/context')->assertOk();

    }

    private function exitSelection(array $changes = []): array
    {
        $leave = $this->execute($this->input('leave'));

        return $changes + ['transition_id' => $leave['id'], 'report_ids' => [], 'document_ids' => []];
    }

    private function exitPackage(?array $selection = null): AcademicExitPackage
    {
        Queue::fake();
        $selection ??= $this->exitSelection();
        $preview = app(ExitPackages::class)->preview($this->superAdmin(), $selection);
        $this->assertTrue($preview['can_prepare']);
        $input = $selection + ['fingerprint' => $preview['fingerprint'], 'operation_id' => (string) Str::uuid()];
        $data = app(ExitPackages::class)->prepare($this->superAdmin(), $input);
        $this->assertSame($data, app(ExitPackages::class)->prepare($this->superAdmin(), $input));

        return AcademicExitPackage::findOrFail($data['id']);
    }

    private function releasedReport(): int
    {
        $task = $this->task($this->schoolA->ids['maths']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->teacher()->id)->value('id'), 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Subject appointment'], $this->group);
        app(MarkRecording::class)->capture($this->teacher(), $this->group, $task, ['rows' => [['learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => 0, 'disposition' => 'recorded', 'mark' => '15.00', 'question_marks' => []]]]);
        $design = app(ReportDesigns::class)->save($this->superAdmin(), ['name' => 'Departure report', 'design' => ['heading' => 'Progress', 'introduction' => '', 'footer' => '', 'sections' => ['subjects'], 'show_crest' => false]]);
        $selection = ['template_id' => $design['id'], 'session_year_id' => $this->year, 'term_id' => $this->schoolA->ids['term'], 'class_section_id' => $this->schoolA->ids['section_a'], 'learner_ids' => [$this->schoolA->ids['asha']], 'mode' => 'official'];
        $reports = app(ReportBatches::class);
        $preview = $reports->preview($this->superAdmin(), $selection);
        $batch = $reports->prepare($this->superAdmin(), $selection + ['fingerprint' => $preview['fingerprint']]);
        foreach (['grade_head', 'principal'] as $kind) {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', ['user_id' => $this->superAdmin()->id, 'session_year_id' => $this->year, 'kind' => $kind, 'grade_level_id' => $kind === 'principal' ? null : $this->grade, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic signing appointment']);
            $batch = $reports->approve($this->superAdmin(), $batch['id'], $kind, $batch);
        }
        $id = $batch['cards'][0]['id'];
        app(ReportArtifacts::class)->generate($id, $batch['fingerprint']);
        $reports->release($this->superAdmin(), $batch['id'], $reports->releasePreview($this->superAdmin(), $batch['id']));

        return $id;
    }

    public function test_exit_archive_contains_real_released_pdf_and_verifiable_manifest_and_wipes_scratch(): void
    {
        Storage::fake(ExitArtifacts::DISK);
        Queue::fake();
        $report = $this->releasedReport();
        $row = $this->exitPackage($this->exitSelection(['report_ids' => [$report]]));
        app(ExitArtifacts::class)->generate((int) $row->id, $row->generation_id);
        $this->assertSame('ready', $row->fresh()->state);
        $bytes = app(ExitArtifacts::class)->read($this->superAdmin(), (int) $row->id);
        $path = tempnam(sys_get_temp_dir(), 'exit-test-');
        try {
            file_put_contents($path, $bytes);
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $manifest = $zip->getFromName('manifest.json');
            $verification = json_decode($zip->getFromName('verification.json'), true, flags: JSON_THROW_ON_ERROR);
            $signature = base64_decode(trim($zip->getFromName('manifest.signature')), true);
            $key = base64_decode($verification['public_key'], true);
            $this->assertTrue(sodium_crypto_sign_verify_detached($signature, $manifest, $key));
            $this->assertFalse(sodium_crypto_sign_verify_detached($signature, $manifest.'changed', $key));
            foreach (json_decode($manifest, true, flags: JSON_THROW_ON_ERROR)['files'] as $file) {
                $this->assertSame($file['sha256'], hash('sha256', $zip->getFromName($file['path'])));
            }
            $this->assertSame(app(ReportArtifacts::class)->read(ReportCard::with('batch')->findOrFail($report)), $zip->getFromName('reports/report-'.$report.'.pdf'));
            $zip->close();
        } finally {
            unlink($path);
        }
        $this->assertSame([], glob(app(ExitArtifacts::class)->scratchRoot().'/exit-*') ?: []);
        $this->assertFalse(str_starts_with(Storage::disk(ExitArtifacts::DISK)->get($row->fresh()->artifact_path), "PK\x03\x04"));
        $this->assertStringNotContainsString('Asha', DB::table('academic_exit_packages')->where('id', $row->id)->value('blueprint'));
        $meta = app(ExitPackages::class)->data($this->superAdmin(), $row->fresh());
        $this->assertSame($verification['public_key'], $meta['signing_public_key']);
        $this->assertNotNull($meta['download_url']);
    }

    public function test_exit_source_changes_and_revoked_requesters_fail_without_publishing_a_partial_file(): void
    {
        Storage::fake(ExitArtifacts::DISK);
        $row = $this->exitPackage();
        DB::table('users')->where('id', $this->schoolA->ids['asha_user'])->update(['first_name' => 'Corrected name']);
        app(ExitArtifacts::class)->generate((int) $row->id, $row->generation_id);
        $this->assertSame('source_changed', $row->fresh()->failure_code);
        $this->assertSame([], Storage::disk(ExitArtifacts::DISK)->allFiles());
        $this->denied(409, fn () => app(ExitPackages::class)->retry($this->superAdmin(), (int) $row->id));
        $selection = ['transition_id' => $row->academic_transition_id, 'report_ids' => [], 'document_ids' => []];
        $fresh = $this->exitPackage($selection);
        SchoolMembership::where('user_id', $this->superAdmin()->id)->update(['status' => 'inactive']);
        app(ExitArtifacts::class)->generate((int) $fresh->id, $fresh->generation_id);
        $this->assertSame('source_or_authority_unavailable', $fresh->fresh()->failure_code);
        $this->assertSame([], Storage::disk(ExitArtifacts::DISK)->allFiles());
    }

    public function test_exit_generation_attempts_do_not_overwrite_a_retry_or_ready_artifact(): void
    {
        Storage::fake(ExitArtifacts::DISK);
        $row = $this->exitPackage();
        $old = $row->generation_id;
        app(ExitArtifacts::class)->failed((int) $row->id, $old, 'generation_failed');
        app(ExitPackages::class)->retry($this->superAdmin(), (int) $row->id);
        $next = $row->fresh()->generation_id;
        $this->assertNotSame($old, $next);
        app(ExitArtifacts::class)->generate((int) $row->id, $old);
        app(ExitArtifacts::class)->failed((int) $row->id, $old, 'stale_failure');
        $this->assertSame('queued', $row->fresh()->state);
        app(ExitArtifacts::class)->generate((int) $row->id, $next);
        $saved = $row->fresh()->artifact_path;
        app(ExitArtifacts::class)->generate((int) $row->id, $next);
        $this->assertSame($saved, $row->fresh()->artifact_path);
        $this->assertCount(1, Storage::disk(ExitArtifacts::DISK)->allFiles());
        Storage::disk(ExitArtifacts::DISK)->put($saved, 'corrupt');
        $this->denied(409, fn () => app(ExitArtifacts::class)->read($this->superAdmin(), (int) $row->id));
    }

    public function test_exit_copies_expire_without_removing_source_records_or_another_schools_storage(): void
    {
        Storage::fake(ExitArtifacts::DISK);
        $row = $this->exitPackage();
        app(ExitArtifacts::class)->generate((int) $row->id, $row->generation_id);
        $row->refresh();
        $this->travelTo($row->expires_at->addSecond());
        $this->denied(409, fn () => app(ExitArtifacts::class)->read($this->superAdmin(), (int) $row->id));
        $other = 'schools/2/exit-packages/999/'.Str::uuid().'.enc';
        Storage::disk(ExitArtifacts::DISK)->put($other, 'other school artifact');
        app(ApplyRetention::class)->handle(app(CurrentSchool::class), app(ExitArtifacts::class));
        $this->assertSame('expired', $row->fresh()->state);
        $this->assertNull($row->fresh()->blueprint);
        $this->assertNull($row->fresh()->manifest);
        $this->assertNotNull($row->fresh()->artifact_sha256);
        Storage::disk(ExitArtifacts::DISK)->assertMissing($row->artifact_path);
        Storage::disk(ExitArtifacts::DISK)->assertExists($other);
        $this->assertSame(1, AcademicTransition::count());
        $this->assertSame(1, Enrolment::withTrashed()->count());
        $this->assertSame('retain', RetentionRule::where('record_type', 'academic_records')->sole()->action);
    }

    public function test_transition_api_and_signed_export_recheck_role_school_owner_and_expiry(): void
    {
        Storage::fake(ExitArtifacts::DISK);
        $row = $this->exitPackage();
        app(ExitArtifacts::class)->generate((int) $row->id, $row->generation_id);
        $meta = app(ExitPackages::class)->data($this->superAdmin(), $row->fresh());
        $token = $this->superAdmin()->createToken('office')->plainTextToken;
        $this->withToken($token)->getJson('/api/v2/academics/transitions/configuration')->assertOk()->assertJsonPath('data.session_year_id', $this->year);
        $this->getJson('/api/v2/academics/transitions/learners?search=Asha')->assertOk()->assertJsonCount(1, 'data.learners');
        $this->get($meta['download_url'])->assertOk()->assertHeader('Content-Type', 'application/zip');
        $this->get(str_replace('reader=', 'reader=9', $meta['download_url']))->assertForbidden();
        $this->getJson('/api/v2/academics/transitions/learners/'.$this->schoolB->ids['asha'])->assertNotFound();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($this->teacher()->createToken('teacher')->plainTextToken)->getJson('/api/v2/academics/transitions/configuration')->assertForbidden();
        $this->get($meta['download_url'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($token)->withHeader('X-School-Id', '2')->getJson('/api/v2/academics/transitions/exports/'.$row->id)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($token);
        $this->travel(11)->minutes();
        $this->get($meta['download_url'])->assertForbidden();
    }

    public function test_export_retention_default_is_editable_with_cas_and_retains_educational_records(): void
    {
        $service = app(TransitionPolicies::class);
        $this->assertSame(7, $service->retention()['days']);
        $saved = $service->saveRetention($this->superAdmin(), ['days' => 2, 'revision' => 0]);
        $this->assertSame(2, $saved['days']);
        $this->denied(409, fn () => $service->saveRetention($this->superAdmin(), ['days' => 3, 'revision' => 0]));
        $row = $this->exitPackage();
        $this->assertSame(now()->addDays(2)->getTimestamp(), $row->expires_at->getTimestamp());
        $this->assertSame('retain', RetentionRule::where('record_type', 'academic_records')->sole()->action);
        $this->assertNotEmpty(app(TransitionQueries::class)->learner($this->superAdmin(), $this->schoolA->ids['asha'])['transitions']);
        $one = app(ExitSigning::class)->sign('manifest');
        $two = app(CurrentSchool::class)->run(2, fn () => app(ExitSigning::class)->sign('manifest'));
        $this->assertNotSame($one['public_key'], $two['public_key']);
    }

    public function test_current_selected_profile_documents_are_pinned_private_and_replaced_ids_cannot_export(): void
    {
        Storage::fake(ExitArtifacts::DISK);
        Storage::fake(ProfileFiles::DISK);
        $category = ProfileFieldCategory::where('profile_type', 'learner')->where('key', 'health')->firstOrFail();
        $definition = app(ProfileDefinitions::class)->field($this->superAdmin(), ['category_id' => $category->id, 'key' => 'exit_support', 'label' => 'Support file', 'type' => 'file', 'sensitive' => true, 'visible_to' => ['owner', 'guardian', 'school_admin'], 'editable_by' => ['school_admin']]);
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        $pdf = "%PDF-1.4\nPRIVATE EXIT DOCUMENT\n%%EOF";
        app(ProfileFiles::class)->upload($this->superAdmin(), $learner->user, 'learner', 'exit_support', UploadedFile::fake()->createWithContent('support.pdf', $pdf));
        $document = ProfileFile::where('definition_id', $definition->id)->sole();
        $selection = $this->exitSelection(['document_ids' => [$document->id]]);
        $row = $this->exitPackage($selection);
        app(ExitArtifacts::class)->generate((int) $row->id, $row->generation_id);
        $this->assertSame('ready', $row->fresh()->state);
        $this->assertCount(1, $row->fresh()->manifest['files'] === [] ? [] : array_filter($row->fresh()->manifest['files'], fn ($f) => $f['sha256'] === hash('sha256', $pdf)));
        $otherSelection = array_replace($selection, ['document_ids' => [(string) Str::uuid()]]);
        $this->denied(404, fn () => app(ExitPackages::class)->preview($this->superAdmin(), $otherSelection));
        ProfileFieldValue::where('definition_id', $definition->id)->where('user_id', $learner->user_id)->delete();
        $this->denied(404, fn () => app(ExitPackages::class)->preview($this->superAdmin(), $selection));
        // A prepared archive is an immutable copy; new jobs must recheck source.
        $this->assertSame('ready', $row->fresh()->state);
        $definition->update(['visible_to' => ['owner']]);
        $this->denied(404, fn () => app(ExitArtifacts::class)->read($this->superAdmin(), (int) $row->id));
        $this->assertSame([], app(TransitionQueries::class)->learner($this->superAdmin(), (int) $learner->id)['exports']);
    }

    public function test_new_routes_happy_paths_match_contract_and_panel_has_one_reviewed_workflow(): void
    {
        Queue::fake();
        $token = $this->superAdmin()->createToken('office')->plainTextToken;
        $this->withToken($token);
        $input = $this->input('join', ['learner_id' => $this->schoolA->ids['ben'], 'class_section_id' => $this->schoolA->ids['section_a'], 'group_ids' => [], 'fee_prorata_requested' => false]);
        $preview = $this->postJson('/api/v2/academics/transitions/preview', $input)->assertOk()->json('data');
        $body = $input + ['fingerprint' => $preview['fingerprint'], 'operation_id' => (string) Str::uuid()];
        $this->postJson('/api/v2/academics/transitions/commit', $body)->assertOk()->assertJsonPath('data.kind', 'join');
        $this->postJson('/api/v2/academics/transitions/commit', $body)->assertOk();
        $this->putJson('/api/v2/academics/transitions/policies/'.$this->grade, ['join_mark_rule' => 'excused', 'change_mark_rule' => 'catch_up', 'revision' => 0])->assertOk();
        $this->putJson('/api/v2/academics/transitions/retention', ['days' => 3, 'revision' => 0])->assertOk();
        $leave = $this->exitSelection();
        $preview = $this->postJson('/api/v2/academics/transitions/exports/preview', $leave)->assertOk()->json('data');
        $queued = $this->postJson('/api/v2/academics/transitions/exports/prepare', $leave + ['fingerprint' => $preview['fingerprint'], 'operation_id' => (string) Str::uuid()])->assertOk()->json('data');
        $this->getJson('/api/v2/academics/transitions/exports/'.$queued['id'])->assertOk();
        $this->postJson('/api/v2/academics/transitions/exports/'.$queued['id'].'/retry')->assertOk();
        $this->getJson('/api/v2/academics/transitions/learners/'.$this->schoolA->ids['asha'])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->app['auth']->shouldUse('web');
        $this->actingAs($this->superAdmin(), 'web')->get('/academic-transitions?learner_id='.$this->schoolA->ids['asha'].'&kind=leave')->assertOk()->assertSee('Signed exit package')->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->teacher(), 'web')->get('/academic-transitions')->assertForbidden();
    }

    public function test_another_office_actor_cannot_read_a_requesters_export_and_revoked_retry_fails_fresh(): void
    {
        $row = $this->exitPackage();
        $teacher = $this->teacher();
        $teacher->givePermissionTo(['academic-structure-manage', 'profile-sensitive-export']);
        $this->denied(404, fn () => app(ExitArtifacts::class)->read($teacher, (int) $row->id));
        $this->denied(404, fn () => app(ExitPackages::class)->retry($teacher, (int) $row->id));
        SchoolMembership::where('user_id', $this->superAdmin()->id)->update(['status' => 'inactive']);
        $this->denied(403, fn () => app(ExitPackages::class)->retry($this->superAdmin(), (int) $row->id));
    }

    public function test_retention_change_requires_export_review_without_invalidation_of_existing_copy_expiry(): void
    {
        Queue::fake();
        Storage::fake(ExitArtifacts::DISK);
        $selection = $this->exitSelection();
        $preview = app(ExitPackages::class)->preview($this->superAdmin(), $selection);
        app(TransitionPolicies::class)->saveRetention($this->superAdmin(), ['days' => 2, 'revision' => 0]);
        $this->denied(409, fn () => app(ExitPackages::class)->prepare($this->superAdmin(), $selection + ['fingerprint' => $preview['fingerprint'], 'operation_id' => (string) Str::uuid()]));
        $row = $this->exitPackage($selection);
        app(TransitionPolicies::class)->saveRetention($this->superAdmin(), ['days' => 3, 'revision' => 1]);
        app(ExitArtifacts::class)->generate((int) $row->id, $row->generation_id);
        $this->assertSame('ready', $row->fresh()->state);
        $this->assertSame(now()->addDays(2)->getTimestamp(), $row->fresh()->expires_at->getTimestamp());
    }

    public function test_unapproved_learner_office_page_and_history_remain_available_without_exposing_profile_files(): void
    {
        User::whereKey($this->schoolA->ids['ben_user'])->update(['status' => 0]);
        $data = app(TransitionQueries::class)->learner($this->superAdmin(), $this->schoolA->ids['ben']);
        $this->assertSame([], $data['document_choices']);
        $this->actingAs($this->superAdmin(), 'web')->get('/academic-transitions?learner_id='.$this->schoolA->ids['ben'])->assertOk()->assertSee('Preview learner change');
        $this->assertSame(0, (int) User::findOrFail($this->schoolA->ids['ben_user'])->status);
    }

    public function test_year_end_changes_invalidate_the_reviewed_join_interval(): void
    {
        $flow = app(TransitionFlow::class);
        $input = $this->input('join', ['learner_id' => $this->schoolA->ids['ben'], 'class_section_id' => $this->schoolA->ids['section_a'], 'group_ids' => [$this->group], 'fee_prorata_requested' => false]);
        $preview = $flow->preview($this->superAdmin(), $input);
        $this->assertSame('2026-12-31', $preview['membership_ends_on']);
        SessionYear::whereKey($this->year)->update(['end_date' => '2026-12-10']);
        $this->denied(409, fn () => $flow->commit($this->superAdmin(), $input + ['operation_id' => (string) Str::uuid(), 'fingerprint' => $preview['fingerprint']]));
        $this->assertSame(0, AcademicTransition::count());
        $this->assertSame('2026-12-10', $flow->preview($this->superAdmin(), $input)['membership_ends_on']);
    }

    private function guardianWithOnlyAsha(): User
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        foreach ($parent->parent->children()->get() as $child) {
            GuardianLink::updateOrCreate(['learner_id' => $child->id, 'guardian_user_id' => $parent->id], ['relationship' => 'father', 'status' => (int) $child->id === $this->schoolA->ids['asha'] ? 'active' : 'ended', 'can_approve' => true, 'can_see_fees' => true]);
        }
        $policies = app(ParentPolicyService::class);
        $policies->accept($parent, 'navigateed', $policies->status($parent)['navigateed']['version']);

        return $parent;
    }

    public function test_only_child_departure_retains_parent_v1_records_but_ended_guardian_rights_still_refuse(): void
    {
        $parent = $this->guardianWithOnlyAsha();
        $token = AppTokens::issue($parent, AppTokens::PARENT);
        $this->execute($this->input('leave', ['effective_on' => '2026-02-09']));
        $this->assertSame(0, (int) StudentSessions::where('student_id', $this->schoolA->ids['asha'])->where('session_year_id', $this->year)->value('status'));
        $this->withToken($token)->getJson('/api/parent/get-profile-data')->assertOk()->assertJsonPath('error', false);
        $this->getJson('/api/parent/attendance?child_id='.$this->schoolA->ids['asha'])->assertOk()->assertJsonPath('error', false);
        $this->getJson('/api/parent/fees-transactions-list')->assertOk()->assertJsonPath('error', false);
        $this->getJson('/api/v2/academics/reports/learners/'.$this->schoolA->ids['asha'])->assertOk();
        // Retained departure rights do not require a session in a later school year.
        $next = SessionYear::forceCreate(['name' => '2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'default' => 0]);
        Settings::where('type', 'session_year')->update(['message' => (string) $next->id]);
        $this->getJson('/api/parent/get-profile-data')->assertOk()->assertJsonPath('error', false);
        GuardianLink::where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $parent->id)->update(['status' => 'ended']);
        $this->getJson('/api/parent/get-profile-data')->assertUnauthorized()->assertJsonPath('code', 'SESSION_NOT_ACTIVE');
        $this->getJson('/api/v2/academics/reports/learners/'.$this->schoolA->ids['asha'])->assertNotFound();
    }

    public function test_manual_session_deactivation_without_a_departure_is_not_bypassed_for_parent(): void
    {
        $parent = $this->guardianWithOnlyAsha();
        StudentSessions::where('student_id', $this->schoolA->ids['asha'])->where('session_year_id', $this->year)->update(['status' => 0]);
        $this->withToken(AppTokens::issue($parent, AppTokens::PARENT))->getJson('/api/parent/get-profile-data')->assertUnauthorized()->assertJsonPath('code', 'ACCOUNT_DEACTIVATED');
    }
}
