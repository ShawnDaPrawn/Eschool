<?php

declare(strict_types=1);

namespace Tests\Feature\Admissions;

use App\Models\Academics\GradeLevel;
use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationDocument;
use App\Models\Admissions\ApplicationEvent;
use App\Models\Admissions\ApplicationLink;
use App\Models\Admissions\Intake;
use App\Models\Admissions\IntakeGrade;
use App\Models\Admissions\Offer;
use App\Models\ClassSection;
use App\Models\FeesPaid;
use App\Models\Parents;
use App\Models\PaymentTransaction;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianInvitation;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Transfers\TransferReceipt;
use App\Models\User;
use App\Services\Admissions\AdmissionOffers;
use App\Services\Admissions\AdmissionReview;
use App\Services\Admissions\ApplicationDocuments;
use App\Services\Admissions\ApplicationForms;
use App\Services\Admissions\ApplicationLinks;
use App\Services\Admissions\Applications;
use App\Services\Admissions\EnrolApplication;
use App\Services\Admissions\EnrolApplications;
use App\Services\Admissions\IntakeAuthority;
use App\Services\Admissions\IntakeCatalogue;
use App\Services\Admissions\IntakeRetention;
use App\Services\Leadership\LeadershipFeeCollections;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Transfers\TransferAccept;
use App\Services\Transfers\TransferPackages;
use App\Services\Transfers\TransferVault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class AdmissionsTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private Intake $intake;

    private int $gradeId;

    private array $fields;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T10:00:00+02:00'));
        config(['modules.intake' => ['always_on' => true]]);
        Queue::fake();
        $this->admin = $this->superAdmin();
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        $this->gradeId = (int) GradeLevel::where('code', '5')->value('id');
        ClassSection::with('class')->findOrFail($this->schoolA->ids['section_a'])->class->forceFill(['grade_level_id' => $this->gradeId])->save();
        SessionYear::whereKey($this->schoolA->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->fields = ProfileFieldDefinition::where('profile_type', 'learner')->whereIn('source', ['user.first_name', 'user.last_name', 'user.dob'])->pluck('id', 'source')->all();
        $this->intake = app(IntakeCatalogue::class)->save($this->admin, $this->op(['revision' => 0, 'session_year_id' => $this->schoolA->ids['session_year'], 'name' => 'Synthetic admissions',
            'state' => 'open', 'opens_at' => '2026-02-01T00:00', 'closes_at' => '2026-03-01T00:00', 'application_fee_cents' => 0, 'keep_for_days' => 30,
            'field_ids' => array_values($this->fields), 'required_documents' => [], 'grades' => [['grade_level_id' => $this->gradeId, 'places' => 1]]]));
    }

    private function op(array $data): array
    {
        return ['operation_id' => (string) Str::uuid()] + $data;
    }

    private function contact(string $suffix = 'one'): array
    {
        return ['first_name' => 'Prospective', 'last_name' => 'Guardian', 'email' => 'admissions.'.$suffix.'@example.test', 'mobile' => '+27820000000', 'relationship' => 'guardian'];
    }

    private function answers(string $suffix = 'One'): array
    {
        return [$this->fields['user.first_name'] => 'Applicant'.$suffix, $this->fields['user.last_name'] => 'Synthetic', $this->fields['user.dob'] => '2015-01-10'];
    }

    private function draft(string $suffix = 'one'): array
    {
        $key = bin2hex(random_bytes(32));
        $row = app(Applications::class)->begin($this->intake, (int) IntakeGrade::value('id'), (string) Str::uuid(), $key, $this->contact($suffix), $this->answers($suffix));

        return [$row, $key];
    }

    private function submitted(string $suffix = 'one'): array
    {
        [$row, $key] = $this->draft($suffix);
        $token = bin2hex(random_bytes(32));
        ApplicationLink::create(['application_id' => $row->id, 'token_hash' => hash('sha256', $token), 'email_hash' => $row->email_hash, 'expires_at' => now()->addMinutes(30)]);
        $session = app(ApplicationLinks::class)->consume($this->intake, $token);
        $row = app(Applications::class)->submit((int) $row->id, $session['key'], 1);

        return [$row, $session['key']];
    }

    private function accepted(string $suffix = 'one'): array
    {
        [$row, $key] = $this->submitted($suffix);
        app(AdmissionOffers::class)->issue($this->admin, $this->op(['application_ids' => [$row->id]]));
        $row = $row->fresh();
        $row = app(AdmissionOffers::class)->decide((int) $row->id, $key, (int) $row->revision, 'accept');

        return [$row, $key];
    }

    private function refuse(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected a scoped admission refusal.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (ValidationException $error) {
            $this->assertSame($status, $error->status);
        }
    }

    private function enrolApplicant(string $suffix, string $startsOn = '2026-02-01'): array
    {
        [$row] = $this->accepted($suffix);
        $input = $this->op(['revision' => $row->revision, 'starts_on' => $startsOn, 'category_id' => $this->schoolA->ids['category']]);
        $row = app(EnrolApplication::class)->enrol($this->admin, (int) $row->id, $input);
        $learner = Students::with('user')->findOrFail($row->learner_id);
        $guardian = User::findOrFail(GuardianLink::where('learner_id', $learner->id)->sole()->guardian_user_id);

        return [$row, $learner, $guardian, $input];
    }

    private function acceptAdmittedGuardian(User $guardian): string
    {
        $invitation = GuardianInvitation::where('guardian_user_id', $guardian->id)->sole();
        $this->app['auth']->forgetGuards();
        $reply = $this->postJson('/api/v2/guardian/invitations/'.$invitation->encrypted_token.'/accept', ['password' => 'SyntheticInvitation2026'])->assertOk();
        $this->assertNotNull($guardian->fresh()->email_verified_at);
        $this->assertFalse($guardian->fresh()->password_change_required);

        return $reply->json('token');
    }

    public function test_admission_invitation_approval_and_dated_membership_remain_separate_on_retries_and_withdrawal(): void
    {
        config(['identity.approval_enforced' => true]);
        [$row, $learner, $guardian, $input] = $this->enrolApplicant('joined-onboarding', '2026-02-12');
        $token = $this->acceptAdmittedGuardian($guardian);
        $this->assertFalse(app(ApprovalService::class)->approved($learner));
        $this->assertFalse(app(MembershipService::class)->isActiveMember($learner->user, 1));

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v2/guardian/approvals/'.$learner->id, ['decision' => 'approve', 'consent_version' => config('identity.consent_version')])
            ->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertTrue(app(ApprovalService::class)->approved($learner));
        // Consent does not move a future school-membership start date forward.
        app(MembershipService::class)->forget($learner->user);
        $this->assertFalse(app(MembershipService::class)->isActiveMember($learner->user, 1));
        $this->travelTo(CarbonImmutable::parse('2026-02-12T10:00:00+02:00'));
        app(MembershipService::class)->forget($learner->user);
        $this->assertTrue(app(MembershipService::class)->isActiveMember($learner->user, 1));
        $learnerToken = AppTokens::issue($learner->user, AppTokens::STUDENT);
        $this->app['auth']->forgetGuards();
        $this->withToken($learnerToken)->getJson('/api/v2/me/context', ['X-School-Id' => '2'])->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v2/guardian/approvals/'.$learner->id, ['decision' => 'withdraw', 'consent_version' => config('identity.consent_version')])
            ->assertOk()->assertJsonPath('data.status', 'withdrawn');
        $this->assertSame(0, $learner->user->tokens()->count());
        $this->assertFalse(app(ApprovalService::class)->approved($learner));
        $this->assertSame($learner->id, app(EnrolApplication::class)->enrol($this->admin, (int) $row->id, $input)->learner_id);
        $this->assertFalse(app(ApprovalService::class)->approved($learner));
        $this->assertSame(1, GuardianInvitation::where('guardian_user_id', $guardian->id)->count());
        $this->assertSame(1, IntakeGrade::findOrFail($row->intake_grade_id)->occupied_places);
    }

    public function test_an_admitted_approved_learner_transfers_identity_but_not_school_approval_or_early_membership(): void
    {
        config(['identity.approval_enforced' => true, 'modules.school_transfers.always_on' => true]);
        Storage::fake(TransferVault::DISK);
        [$row, $learner, $guardian] = $this->enrolApplicant('joined-transfer');
        $this->acceptAdmittedGuardian($guardian);
        $guardian = $guardian->fresh();
        app(ApprovalService::class)->decide($guardian, $learner, 'approve', (string) config('identity.consent_version'));
        $service = app(TransferPackages::class);
        $package = $service->prepare($this->admin, $this->op(['learner_id' => $learner->id, 'guardian_id' => $guardian->id,
            'to_school_id' => 2, 'external_school' => null, 'last_attended_on' => '2026-02-10', 'starts_on' => '2026-02-11', 'report_ids' => [], 'document_ids' => []]));
        $consent = $this->op(['revision' => $package->revision, 'fingerprint' => $package->fingerprint, 'version' => config('school_transfers.consent_version'), 'confirmation' => true]);
        SchoolMembership::where('user_id', $guardian->id)->where('role', 'Parent')->update(['status' => SchoolMembership::SUSPENDED]);
        // The same loaded guardian cannot use stale authority on the saved package.
        $this->refuse(403, fn () => $service->consent($guardian, (int) $package->id, $consent));
        SchoolMembership::where('user_id', $guardian->id)->where('role', 'Parent')->update(['status' => SchoolMembership::ACTIVE]);
        $package = $service->consent($guardian, (int) $package->id, $consent);
        $package = $service->release($this->admin, (int) $package->id, $this->op(['revision' => $package->revision]));
        $receipt = app(CurrentSchool::class)->run(2, function () use ($package, $guardian, $learner): TransferReceipt {
            $receiver = User::create(['first_name' => 'Receiving', 'last_name' => 'Office', 'email' => 'joined-receiver@example.test', 'password' => bcrypt('SyntheticReceiver2026'), 'status' => 1]);
            $receiver->assignRole('Super Admin');
            SessionYear::whereKey($this->schoolB->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
            ClassSection::with('class')->findOrFail($this->schoolB->ids['section_a'])->class->forceFill(['grade_level_id' => $this->gradeId])->save();
            $input = $this->op(['revision' => $package->revision, 'fingerprint' => $package->fingerprint, 'session_year_id' => $this->schoolB->ids['session_year'],
                'class_section_id' => $this->schoolB->ids['section_a'], 'category_id' => $this->schoolB->ids['category'], 'confirmation' => true]);
            $receipt = app(TransferAccept::class)->accept($receiver, (int) $package->id, $input);
            $this->assertSame($receipt->id, app(TransferAccept::class)->accept($receiver, (int) $package->id, $input)->id);
            $received = Students::findOrFail($receipt->learner_id);
            $this->assertSame($learner->user_id, $received->user_id);
            $this->assertFalse(app(ApprovalService::class)->approved($received));
            $this->assertSame(0, LearnerApproval::where('learner_id', $received->id)->count());
            app(MembershipService::class)->forget($guardian);
            $this->assertFalse(app(MembershipService::class)->isActiveMember($guardian, 2));
            $this->assertFalse(GuardianLink::where('learner_id', $received->id)->sole()->can_see_fees);

            return $receipt;
        });
        $this->assertSame('2026-02-10', Enrolment::where('learner_id', $learner->id)->sole()->ends_on->toDateString());
        $this->travelTo(CarbonImmutable::parse('2026-02-11T10:00:00+02:00'));
        app(MembershipService::class)->forget($learner->user);
        $this->assertFalse(app(MembershipService::class)->isActiveMember($learner->user, 1));
        app(CurrentSchool::class)->run(2, function () use ($receipt, $guardian, $learner): void {
            $received = Students::findOrFail($receipt->learner_id);
            app(MembershipService::class)->forget($guardian);
            $this->assertTrue(app(MembershipService::class)->isActiveMember($guardian, 2));
            $this->assertFalse(app(ApprovalService::class)->approved($received));
            $this->refuse(403, fn () => AppTokens::issue($learner->user, AppTokens::STUDENT));
        });
        $this->assertSame('enrolled', $row->fresh()->state);
        $this->assertSame(1, IntakeGrade::findOrFail($row->intake_grade_id)->occupied_places);
    }

    public function test_office_acknowledgement_is_not_a_second_leadership_receipt_and_replay_rechecks_school_membership(): void
    {
        config(['modules.fees.always_on' => true, 'modules.leadership_dashboard.always_on' => true]);
        $this->intake->update(['application_fee_cents' => 12500]);
        [$row] = $this->accepted('joined-fee');
        $input = $this->op(['revision' => $row->revision, 'amount_cents' => 12500, 'reference' => 'Synthetic office acknowledgement']);
        $paid = app(AdmissionReview::class)->officeFee($this->admin, (int) $row->id, $input);
        SchoolMembership::where('user_id', $this->admin->id)->update(['status' => SchoolMembership::SUSPENDED]);
        $this->refuse(403, fn () => app(AdmissionReview::class)->officeFee($this->admin, (int) $row->id, $input));
        SchoolMembership::where('user_id', $this->admin->id)->update(['status' => SchoolMembership::ACTIVE]);
        app(CurrentSchool::class)->run(2, fn () => $this->refuse(404, fn () => app(AdmissionReview::class)->officeFee($this->admin, (int) $row->id, $input)));
        $this->assertSame($paid->revision, app(AdmissionReview::class)->officeFee($this->admin, (int) $row->id, $input)->revision);
        $row = app(EnrolApplication::class)->enrol($this->admin, (int) $row->id, $this->op(['revision' => $paid->revision, 'starts_on' => '2026-02-01', 'category_id' => $this->schoolA->ids['category']]));
        $learner = Students::findOrFail($row->learner_id);
        $projection = app(LeadershipFeeCollections::class);
        $year = $this->schoolA->ids['session_year'];
        $this->assertSame(0, $projection->amount($projection->read($this->admin, $year, '2026-02-01', '2026-02-10'), $this->gradeId, '2026-02-01', '2026-02-10'));
        PaymentTransaction::forceCreate(['student_id' => $learner->id, 'class_id' => $learner->class_id, 'parent_id' => $learner->guardian_id,
            'payment_status' => 1, 'mode' => 0, 'type_of_fee' => 0, 'total_amount_cents' => 5000, 'session_year_id' => $year, 'date' => '2026-02-10']);
        FeesPaid::forceCreate(['student_id' => $learner->id, 'class_id' => $learner->class_id, 'total_amount_cents' => 5000, 'session_year_id' => $year, 'date' => '2026-02-10', 'is_fully_paid' => 0]);
        foreach ([1, 2] as $_) {
            $this->assertSame(5000, $projection->amount($projection->read($this->admin, $year, '2026-02-01', '2026-02-10'), $this->gradeId, '2026-02-01', '2026-02-10'));
        }
        $this->assertSame(1, ApplicationEvent::where('application_id', $row->id)->where('kind', 'office_fee_acknowledged')->count());
        $this->assertTrue($row->office_fee_paid);
        $this->assertSame(12500, $row->office_fee_cents);
    }

    public function test_draft_is_encrypted_exact_initial_retry_is_stable_and_foreign_key_is_refused(): void
    {
        $key = bin2hex(random_bytes(32));
        $nonce = (string) Str::uuid();
        $grade = (int) IntakeGrade::value('id');
        $service = app(Applications::class);
        $first = $service->begin($this->intake, $grade, $nonce, $key, $this->contact(), $this->answers());
        $again = $service->begin($this->intake, $grade, $nonce, $key, $this->contact(), $this->answers());
        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, Application::count());
        $raw = DB::table('applications')->where('id', $first->id)->first();
        $this->assertStringNotContainsString('Applicantone', $raw->answers);
        $this->assertStringNotContainsString('admissions.one', $raw->parent_contact);
        $this->refuse(404, fn () => app(IntakeAuthority::class)->owner((int) $first->id, str_repeat('0', 64)));
        app(CurrentSchool::class)->run(2, fn () => $this->refuse(404, fn () => app(IntakeAuthority::class)->owner((int) $first->id, $key)));
    }

    public function test_drafts_can_save_incomplete_fields_but_submit_requires_email_proof_and_required_answers(): void
    {
        [$row, $key] = $this->draft();
        $row = app(Applications::class)->save((int) $row->id, $key, 1, $this->contact(), []);
        $this->assertSame([], $row->answers);
        $this->refuse(422, fn () => app(Applications::class)->submit((int) $row->id, $key, 2));
        $this->assertSame('draft', $row->fresh()->state);
        $this->refuse(404, fn () => app(IntakeAuthority::class)->application($this->admin, (int) $row->id));
    }

    public function test_magic_resume_is_single_use_rotates_old_owner_key_and_creates_no_learner_approval(): void
    {
        [$row, $old] = $this->draft();
        $token = bin2hex(random_bytes(32));
        ApplicationLink::create(['application_id' => $row->id, 'token_hash' => hash('sha256', $token), 'email_hash' => $row->email_hash, 'expires_at' => now()->addMinutes(30)]);
        $result = app(ApplicationLinks::class)->consume($this->intake, $token);
        $this->assertNotSame($old, $result['key']);
        $this->refuse(404, fn () => app(IntakeAuthority::class)->owner((int) $row->id, $old));
        $this->refuse(404, fn () => app(ApplicationLinks::class)->consume($this->intake, $token));
        $this->assertSame(0, LearnerApproval::count());
    }

    public function test_offer_capacity_waiting_list_and_decline_promote_next_family_once(): void
    {
        [$first, $firstKey] = $this->submitted('first');
        [$second] = $this->submitted('second');
        $request = $this->op(['application_ids' => [$first->id, $second->id]]);
        $offers = app(AdmissionOffers::class);
        $a = $offers->issue($this->admin, $request);
        $this->assertSame($a, $offers->issue($this->admin, $request));
        $this->assertSame('offered', $first->fresh()->state);
        $this->assertSame('waitlisted', $second->fresh()->state);
        $revision = (int) $first->fresh()->revision;
        $offers->decide((int) $first->id, $firstKey, $revision, 'decline');
        $offers->decide((int) $first->id, $firstKey, $revision, 'decline');
        $this->assertSame('offered', $second->fresh()->state);
        $this->assertSame(1, Offer::where('state', 'offered')->count());
        $this->assertSame(2, Offer::count());
    }

    public function test_accepted_offer_keeps_place_after_acceptance_deadline_and_enrolment_is_one_transaction(): void
    {
        [$row] = $this->accepted();
        $this->travelTo(now()->addDays(8));
        $grade = (int) $row->intake_grade_id;
        $this->assertSame(1, app(AdmissionOffers::class)->reserved($grade));
        app(AdmissionOffers::class)->recover($grade);
        $this->assertSame('accepted', $row->fresh()->state);
        $before = Students::count();
        $request = $this->op(['revision' => $row->revision, 'starts_on' => '2026-02-20', 'category_id' => $this->schoolA->ids['category']]);
        $enrol = app(EnrolApplication::class);
        $enrolled = $enrol->enrol($this->admin, (int) $row->id, $request);
        $again = $enrol->enrol($this->admin, (int) $row->id, $request);
        $this->assertSame($enrolled->learner_id, $again->learner_id);
        $this->assertSame($before + 1, Students::count());
        $this->assertSame('enrolled', $enrolled->state);
        $this->assertSame(1, GuardianLink::where('learner_id', $enrolled->learner_id)->count());
        $this->assertSame(1, GuardianInvitation::count());
        $this->assertSame(1, IntakeGrade::findOrFail($grade)->occupied_places);
        $this->assertSame(1, app(AdmissionOffers::class)->reserved($grade));
        $this->assertSame(0, LearnerApproval::where('learner_id', $enrolled->learner_id)->count());
        $this->assertTrue(Students::with('user')->findOrFail($enrolled->learner_id)->user->password_change_required);
    }

    public function test_outer_enrolment_rollback_leaves_no_partial_identity_guardian_or_invitation(): void
    {
        [$row] = $this->accepted();
        $before = [User::count(), Students::count(), GuardianInvitation::count()];
        try {
            DB::transaction(function () use ($row): void {
                app(EnrolApplication::class)->enrol($this->admin, (int) $row->id, $this->op(['revision' => $row->revision, 'starts_on' => '2026-02-20', 'category_id' => $this->schoolA->ids['category']]));
                throw new \RuntimeException('Synthetic enclosing rollback');
            });
        } catch (\RuntimeException $error) {
            $this->assertSame('Synthetic enclosing rollback', $error->getMessage());
        }
        $this->assertSame($before, [User::count(), Students::count(), GuardianInvitation::count()]);
        $this->assertSame('accepted', $row->fresh()->state);
        $this->assertSame(0, IntakeGrade::findOrFail($row->intake_grade_id)->occupied_places);
    }

    public function test_wrong_role_wrong_school_and_wrong_owner_are_refused_before_answer_queries(): void
    {
        [$row, $key] = $this->submitted();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        DB::enableQueryLog();
        $this->refuse(403, fn () => app(IntakeAuthority::class)->application($teacher, (int) $row->id));
        $this->refuse(404, fn () => app(IntakeAuthority::class)->owner((int) $row->id, str_repeat('0', 64)));
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();
        $bodyQueries = array_filter($queries, fn ($sql) => str_contains($sql, 'from `applications`') && (str_contains($sql, 'select *') || str_contains($sql, '`answers`') || str_contains($sql, '`parent_contact`')));
        $this->assertSame([], array_values($bodyQueries));
        app(CurrentSchool::class)->run(2, fn () => $this->refuse(404, fn () => app(IntakeAuthority::class)->owner((int) $row->id, $key)));
        $this->refuse(403, fn () => app(AdmissionOffers::class)->issue($teacher, $this->op(['application_ids' => [$row->id]])));
    }

    public function test_offer_expiry_promotes_waiting_family_without_changing_another_grade(): void
    {
        [$first] = $this->submitted('first');
        [$second] = $this->submitted('second');
        app(AdmissionOffers::class)->issue($this->admin, $this->op(['application_ids' => [$first->id, $second->id]]));
        $this->travelTo(now()->addDays(8));
        app(AdmissionOffers::class)->recover((int) $first->intake_grade_id);
        $this->assertSame('offer_expired', $first->fresh()->state);
        $this->assertSame('offered', $second->fresh()->state);
        $this->assertSame(1, app(AdmissionOffers::class)->reserved((int) $first->intake_grade_id));
        app(AdmissionOffers::class)->recover((int) $first->intake_grade_id);
        $this->assertSame(2, Offer::count());
    }

    public function test_office_fee_must_match_integer_cents_before_enrolment_and_exact_retry_is_stable(): void
    {
        $this->intake->update(['application_fee_cents' => 12500]);
        [$row] = $this->accepted();
        $enrol = $this->op(['revision' => $row->revision, 'starts_on' => '2026-02-20', 'category_id' => $this->schoolA->ids['category']]);
        $this->refuse(422, fn () => app(EnrolApplication::class)->enrol($this->admin, (int) $row->id, $enrol));
        $fee = $this->op(['revision' => $row->revision, 'amount_cents' => 12501, 'reference' => 'Synthetic receipt']);
        $this->refuse(409, fn () => app(AdmissionReview::class)->officeFee($this->admin, (int) $row->id, $fee));
        $fee['operation_id'] = (string) Str::uuid();
        $fee['amount_cents'] = 12500;
        $service = app(AdmissionReview::class);
        $paid = $service->officeFee($this->admin, (int) $row->id, $fee);
        $this->assertTrue($paid->office_fee_paid);
        $this->assertSame($paid->revision, $service->officeFee($this->admin, (int) $row->id, $fee)->revision);
        $this->assertSame(12500, $paid->office_fee_cents);
    }

    public function test_scanned_private_document_retry_download_and_owner_scope(): void
    {
        Storage::fake(ApplicationDocuments::DISK);
        $this->intake->update(['required_documents' => ['birth_certificate']]);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-clean', 'signature' => null];
            }
        });
        [$row, $key] = $this->draft();
        $pdf = "%PDF-1.4\nSynthetic private admission document\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('private-name.pdf', $pdf);
        $op = (string) Str::uuid();
        $service = app(ApplicationDocuments::class);
        $doc = $service->upload((int) $row->id, $key, 'birth_certificate', $op, $file);
        $this->assertSame($doc->id, $service->upload((int) $row->id, $key, 'birth_certificate', $op, $file)->id);
        $this->assertStringNotContainsString($pdf, Storage::disk($service::DISK)->get($doc->path));
        $this->assertStringNotContainsString('private-name', $doc->path);
        $this->assertSame($pdf, $service->ownerBytes((int) $row->id, $key, $doc->handle)['bytes']);
        $this->refuse(404, fn () => $service->ownerBytes((int) $row->id, str_repeat('0', 64), $doc->handle));
        [$other, $otherKey] = $this->draft('other');
        $this->refuse(404, fn () => $service->ownerBytes((int) $other->id, $otherKey, $doc->handle));
        app(CurrentSchool::class)->run(2, fn () => $this->refuse(404, fn () => $service->ownerBytes((int) $row->id, $key, $doc->handle)));
        $this->refuse(404, fn () => $service->staffBytes($this->admin, (int) $row->id, $doc->handle));
        $this->assertSame(1, ApplicationDocument::count());
    }

    public function test_document_scanner_failure_is_closed_and_does_not_erase_draft(): void
    {
        Storage::fake(ApplicationDocuments::DISK);
        $this->intake->update(['required_documents' => ['birth_certificate']]);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                throw new \RuntimeException('Synthetic scanner outage');
            }
        });
        [$row, $key] = $this->draft();
        $this->refuse(503, fn () => app(ApplicationDocuments::class)->upload((int) $row->id, $key, 'birth_certificate', (string) Str::uuid(), UploadedFile::fake()->createWithContent('proof.pdf', '%PDF-1.4 synthetic')));
        $this->assertSame('draft', $row->fresh()->state);
        $this->assertSame(0, ApplicationDocument::count());
    }

    public function test_retention_blocks_expired_draft_resume_before_touching_body_and_erases_even_when_module_off(): void
    {
        [$row, $key] = $this->draft();
        $token = bin2hex(random_bytes(32));
        ApplicationLink::create(['application_id' => $row->id, 'token_hash' => hash('sha256', $token), 'email_hash' => $row->email_hash, 'expires_at' => now()->addDays(60)]);
        $this->travelTo(now()->addDays(31));
        $this->refuse(404, fn () => app(ApplicationLinks::class)->consume($this->intake, $token));
        $this->refuse(404, fn () => app(IntakeAuthority::class)->owner((int) $row->id, $key));
        config(['modules.intake.always_on' => false]);
        $this->assertTrue(app(IntakeRetention::class)->purge((int) $row->id));
        $this->assertNull(Application::find($row->id));
        $this->assertSame(0, ApplicationLink::where('application_id', $row->id)->count());
        $this->assertSame(0, ApplicationEvent::where('application_id', $row->id)->count());
    }

    public function test_retention_never_erases_an_accepted_place_or_another_school_application(): void
    {
        [$row] = $this->accepted();
        $this->travelTo(now()->addDays(366));
        $service = app(IntakeRetention::class);
        $this->assertFalse($service->due($row->fresh()));
        $this->assertFalse($service->purge((int) $row->id));
        app(CurrentSchool::class)->run(2, fn () => $this->assertFalse($service->purge((int) $row->id)));
        $this->assertNotNull(Application::find($row->id));
    }

    private function publicUrl(string $page = 'form'): string
    {
        return route('admissions.public.'.$page, ['schoolSlug' => School::findOrFail(1)->slug, 'intakeId' => $this->intake->public_id]);
    }

    private function applicantBrowser(Application $row, string $key): array
    {
        return ['admissions' => [1 => [$this->intake->public_id => Crypt::encryptString(json_encode(['id' => (int) $row->id, 'key' => $key, 'nonce' => $row->public_id, 'issued' => now()->subSeconds(5)->getTimestamp()], JSON_THROW_ON_ERROR))]]];
    }

    private function panelPin(array $data): array
    {
        return $this->op(['expected_account_id' => $this->admin->id, 'expected_school_id' => 1] + $data);
    }

    public function test_public_form_creates_draft_without_account_and_never_flashes_private_input(): void
    {
        $url = $this->publicUrl();
        $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('Synthetic admissions');
        $this->travelTo(now()->addSeconds(3));
        $this->post($url, ['contact' => $this->contact(), 'answers' => $this->answers(), 'website' => '', 'intake_grade_id' => IntakeGrade::value('id')])->assertRedirect($this->publicUrl('application'));
        $row = Application::firstOrFail();
        $this->assertSame('draft', $row->state);
        $this->get($this->publicUrl('application'))->assertOk()->assertSee('ApplicantOne')->assertSee('Application status');
        $invalid = $this->contact();
        $invalid['email'] = 'private-invalid-email';
        $this->post($this->publicUrl('save'), ['revision' => $row->revision, 'contact' => $invalid, 'answers' => $this->answers('Unsaved')])->assertStatus(422)->assertSee('ApplicantUnsaved')->assertSee('private-invalid-email')->assertSessionMissing('_old_input');
        $this->assertSame($this->contact()['email'], $row->fresh()->parent_contact['email']);
        $this->assertStringNotContainsString('ApplicantOne', json_encode(session()->all(), JSON_THROW_ON_ERROR));
    }

    public function test_public_resume_get_does_not_consume_link_and_exchange_owns_only_matching_intake(): void
    {
        [$row, $old] = $this->draft();
        $token = bin2hex(random_bytes(32));
        $link = ApplicationLink::create(['application_id' => $row->id, 'token_hash' => hash('sha256', $token), 'email_hash' => $row->email_hash, 'expires_at' => now()->addMinutes(30)]);
        $this->get($this->publicUrl('resume'))->assertOk()->assertDontSee($token);
        $this->assertNull($link->fresh()->used_at);
        $this->post($this->publicUrl('exchange'), ['token' => $token])->assertRedirect($this->publicUrl('application'));
        $this->get($this->publicUrl('application'))->assertOk()->assertSee('Applicantone');
        $this->post($this->publicUrl('exchange'), ['token' => $token])->assertNotFound();
        $this->refuse(404, fn () => app(IntakeAuthority::class)->owner((int) $row->id, $old));
    }

    public function test_public_other_intake_school_and_missing_owner_never_return_body(): void
    {
        [$row, $key] = $this->draft();
        $this->get($this->publicUrl('application'))->assertNotFound()->assertDontSee('Applicantone');
        $foreign = route('admissions.public.application', ['schoolSlug' => 'baobab-high', 'intakeId' => $this->intake->public_id]);
        $this->withSession($this->applicantBrowser($row, $key))->get($foreign)->assertNotFound()->assertDontSee('Applicantone');
        $this->withSession($this->applicantBrowser($row, str_repeat('0', 64)))->post($this->publicUrl('save'), ['revision' => 1, 'contact' => $this->contact(), 'answers' => $this->answers('UnauthorisedReflection')])->assertNotFound()->assertDontSee('UnauthorisedReflection')->assertSessionMissing('_old_input');
    }

    public function test_private_panel_pipeline_detail_and_stale_account_pin(): void
    {
        [$row] = $this->submitted();
        $this->actingAs($this->admin)->get(route('admissions-panel.index'))->assertOk()->assertSee($row->public_id)->assertDontSee('Applicantone');
        $this->get(route('admissions-panel.detail', ['application' => $row->id]))->assertOk()->assertSee('Applicantone')->assertHeader('Cache-Control', 'no-store, private');
        $input = $this->panelPin(['revision' => $row->revision, 'decision' => 'reject', 'reason' => 'Private office draft']);
        $input['expected_account_id'] = $this->schoolA->ids['tara_user'];
        $this->post(route('admissions-panel.write', ['application' => $row->id, 'operation' => 'review']), $input)->assertForbidden()->assertDontSee('Private office draft');
        $this->assertSame('submitted', $row->fresh()->state);
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get(route('admissions-panel.detail', ['application' => $row->id]))->assertForbidden()->assertDontSee('Applicantone');
    }

    public function test_panel_validation_does_not_flash_private_review_text(): void
    {
        [$row] = $this->submitted();
        $input = $this->panelPin(['revision' => $row->revision, 'decision' => 'invalid', 'reason' => 'Private preserved office draft']);
        $this->actingAs($this->admin)->post(route('admissions-panel.write', ['application' => $row->id, 'operation' => 'review']), $input)->assertStatus(422)->assertSee('Private preserved office draft')->assertSessionMissing('_old_input');
        $this->get(route('admissions-panel.new'))->assertOk()->assertSee('Configure intake');
    }

    public function test_bulk_enrol_is_atomic_and_balances_fresh_counts_with_stable_retry(): void
    {
        IntakeGrade::query()->update(['places' => 2]);
        [$first] = $this->accepted('first');
        [$second] = $this->accepted('second');
        ClassSection::with('class')->findOrFail($this->schoolA->ids['section_c'])->class->forceFill(['grade_level_id' => $this->gradeId])->save();
        $request = $this->op(['starts_on' => '2026-02-20', 'category_id' => $this->schoolA->ids['category'], 'applications' => [['id' => $first->id, 'revision' => $first->revision], ['id' => $second->id, 'revision' => $second->revision - 1]]]);
        $before = Students::count();
        $service = app(EnrolApplications::class);
        $this->refuse(409, fn () => $service->enrol($this->admin, $request));
        $this->assertSame($before, Students::count());
        $this->assertSame('accepted', $first->fresh()->state);
        $request['applications'][1]['revision']++;
        $receipt = $service->enrol($this->admin, $request);
        $this->assertSame($receipt, $service->enrol($this->admin, $request));
        $this->assertSame($before + 2, Students::count());
        $this->assertSame(2, GuardianInvitation::count());
        $this->assertSame(0, LearnerApproval::whereIn('learner_id', [$first->fresh()->learner_id, $second->fresh()->learner_id])->count());
    }

    public function test_profile_field_type_mutation_cannot_reinterpret_a_saved_application(): void
    {
        [$row, $key] = $this->draft();
        ProfileFieldDefinition::whereKey($this->fields['user.first_name'])->update(['type' => 'boolean']);
        $this->refuse(409, fn () => app(Applications::class)->save((int) $row->id, $key, 1, $this->contact(), $this->answers()));
        $this->assertSame('Applicantone', $row->fresh()->answers[(string) $this->fields['user.first_name']]);
    }

    public function test_sensitive_snapshot_and_fresh_role_revocation_apply_before_body_query(): void
    {
        [$row] = $this->submitted();
        $this->intake->update(['restricted_field_ids' => [$this->fields['user.dob']]]);
        $role = Role::where('school_id', 1)->where('name', 'Super Admin')->firstOrFail();
        $role->revokePermissionTo('profile-sensitive-view');
        $this->admin->revokePermissionTo('profile-sensitive-view');
        $this->assertSame($this->admin->id, app(IntakeAuthority::class)->staff($this->admin)->id);
        DB::enableQueryLog();
        $this->refuse(403, fn () => app(IntakeAuthority::class)->application($this->admin, (int) $row->id));
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();
        $this->assertSame([], array_values(array_filter($queries, fn ($sql) => str_contains($sql, 'from `applications`') && str_contains($sql, 'select *'))));
        SchoolMembership::where('user_id', $this->admin->id)->update(['status' => 'suspended']);
        $this->refuse(403, fn () => app(IntakeAuthority::class)->staff($this->admin));
    }

    public function test_expired_offer_is_erased_after_retention_even_if_recovery_was_disabled(): void
    {
        [$row] = $this->submitted();
        app(AdmissionOffers::class)->issue($this->admin, $this->op(['application_ids' => [$row->id]]));
        $this->travelTo(now()->addDays(38));
        $this->assertTrue(app(IntakeRetention::class)->due($row->fresh()));
        $this->assertTrue(app(IntakeRetention::class)->purge((int) $row->id));
        $this->assertSame(0, Offer::where('application_id', $row->id)->count());
    }

    public function test_orphan_sweep_erases_only_old_unknown_files_inside_current_school(): void
    {
        $disk = Storage::fake(ApplicationDocuments::DISK);
        $prefix = app(CurrentSchool::class)->storagePath('applications');
        $old = $prefix.'/synthetic-orphan.enc';
        $fresh = $prefix.'/synthetic-fresh.enc';
        $foreign = app(CurrentSchool::class)->run(2, fn () => app(CurrentSchool::class)->storagePath('applications')).'/foreign.enc';
        foreach ([$old, $fresh, $foreign] as $path) {
            $disk->put($path, 'synthetic encrypted orphan bytes');
        }
        touch($disk->path($old), now()->subDays(3)->getTimestamp());
        touch($disk->path($foreign), now()->subDays(3)->getTimestamp());
        $this->assertSame(1, app(IntakeRetention::class)->orphanFiles());
        $disk->assertMissing($old);
        $disk->assertExists($fresh);
        $disk->assertExists($foreign);
    }

    public function test_family_can_start_a_sibling_application_without_overwriting_first_draft(): void
    {
        [$row, $key] = $this->draft();
        $this->withSession($this->applicantBrowser($row, $key))->get($this->publicUrl())->assertRedirect($this->publicUrl('application'));
        $this->post($this->publicUrl('another'))->assertRedirect($this->publicUrl());
        $this->get($this->publicUrl())->assertOk();
        $this->travelTo(now()->addSeconds(3));
        $this->post($this->publicUrl(), ['contact' => $this->contact(), 'answers' => $this->answers('Sibling'), 'intake_grade_id' => IntakeGrade::value('id')])->assertRedirect($this->publicUrl('application'));
        $this->assertSame(2, Application::count());
        $this->assertSame('Applicantone', $row->fresh()->answers[(string) $this->fields['user.first_name']]);
    }

    public function test_old_panel_year_refusal_preserves_private_reason_without_session_flash(): void
    {
        [$row] = $this->submitted();
        $old = SessionYear::create(['name' => 'Synthetic retained year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $input = $this->panelPin(['revision' => $row->revision, 'decision' => 'reject', 'reason' => 'Private retained year draft']);
        $this->actingAs($this->admin)->withSession(['session_year' => $old->id])->post(route('admissions-panel.write', ['application' => $row->id, 'operation' => 'review']), $input)->assertStatus(423)->assertSee('Private retained year draft')->assertSessionMissing('_old_input');
        $this->assertSame('submitted', $row->fresh()->state);
    }

    public function test_enrolled_application_copy_expires_without_erasing_learner_guardian_or_invitation(): void
    {
        [$row] = $this->accepted();
        $row = app(EnrolApplication::class)->enrol($this->admin, (int) $row->id, $this->op(['revision' => $row->revision, 'starts_on' => '2026-02-20', 'category_id' => $this->schoolA->ids['category']]));
        $learner = (int) $row->learner_id;
        $this->travelTo(now()->addDays(31));
        $this->assertTrue(app(IntakeRetention::class)->purge((int) $row->id));
        $this->assertNotNull(Students::find($learner));
        $this->assertSame(1, GuardianLink::where('learner_id', $learner)->count());
        $this->assertSame(1, GuardianInvitation::count());
        $this->assertSame(0, LearnerApproval::where('learner_id', $learner)->count());
    }

    public function test_erasing_enrolled_application_never_reopens_its_occupied_place(): void
    {
        $this->intake->update(['keep_for_days' => 1]);
        [$row] = $this->accepted('occupied');
        [$waiting] = $this->submitted('waiting');
        $offers = app(AdmissionOffers::class);
        $offers->issue($this->admin, $this->op(['application_ids' => [$waiting->id]]));
        $row = app(EnrolApplication::class)->enrol($this->admin, (int) $row->id, $this->op(['revision' => $row->revision, 'starts_on' => '2026-02-10', 'category_id' => $this->schoolA->ids['category']]));
        $this->travelTo(now()->addDays(2));
        $this->assertTrue($this->intake->closes_at->isFuture());
        $this->assertTrue(app(IntakeRetention::class)->purge((int) $row->id));
        $this->assertSame(1, IntakeGrade::findOrFail($row->intake_grade_id)->occupied_places);
        $this->assertSame(1, $offers->reserved((int) $row->intake_grade_id));
        $this->assertSame([], $offers->recover((int) $row->intake_grade_id));
        $this->assertSame('waitlisted', $waiting->fresh()->state);
        $this->assertSame(0, Offer::where('state', 'offered')->count());
        $migration = require database_path('migrations/2026_10_25_221000_preserve_enrolled_intake_occupancy.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }

    public function test_selected_parent_identity_fields_are_imported_but_existing_global_identity_is_preserved(): void
    {
        $field = ProfileFieldDefinition::where('profile_type', 'parent')->where('source', 'user.mobile')->firstOrFail();
        $ids = [...$this->intake->field_ids, (int) $field->id];
        $selected = app(ApplicationForms::class)->selectable()->whereIn('id', $ids);
        $this->intake->update(['field_ids' => $ids, 'form_fingerprint' => app(ApplicationForms::class)->fingerprint($selected)]);
        foreach (['new', 'existing'] as $kind) {
            IntakeGrade::query()->update(['places' => 2]);
            $contact = $this->contact($kind);
            $contact['mobile'] = null;
            if ($kind === 'existing') {
                app(CurrentSchool::class)->run(2, fn () => User::create(['first_name' => 'Existing', 'last_name' => 'Identity', 'email' => $contact['email'], 'mobile' => '+27829999999', 'gender' => 'Not specified', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]));
            }
            $row = app(Applications::class)->begin($this->intake, (int) IntakeGrade::value('id'), (string) Str::uuid(), bin2hex(random_bytes(32)), $contact, $this->answers($kind) + [$field->id => '+27821111111']);
            $token = bin2hex(random_bytes(32));
            ApplicationLink::create(['application_id' => $row->id, 'token_hash' => hash('sha256', $token), 'email_hash' => $row->email_hash, 'expires_at' => now()->addMinutes(30)]);
            $session = app(ApplicationLinks::class)->consume($this->intake, $token);
            app(Applications::class)->submit((int) $row->id, $session['key'], 1);
            app(AdmissionOffers::class)->issue($this->admin, $this->op(['application_ids' => [$row->id]]));
            $row = app(AdmissionOffers::class)->decide((int) $row->id, $session['key'], (int) $row->fresh()->revision, 'accept');
            $row = app(EnrolApplication::class)->enrol($this->admin, (int) $row->id, $this->op(['revision' => $row->revision, 'starts_on' => '2026-02-10', 'category_id' => $this->schoolA->ids['category']]));
            $guardian = User::acrossSchools()->where('email', $contact['email'])->firstOrFail();
            $this->assertSame($kind === 'new' ? '+27821111111' : '+27829999999', $guardian->mobile);
            $this->assertSame($guardian->mobile, Parents::where('user_id', $guardian->id)->value('mobile'));
            $this->assertSame(0, LearnerApproval::where('learner_id', $row->learner_id)->count());
        }
    }

    public function test_normal_public_navigation_does_not_exhaust_separate_creation_allowance(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->get($this->publicUrl())->assertOk();
            $this->travelTo(now()->addSeconds(3));
            $this->post($this->publicUrl(), ['contact' => $this->contact('rate'.$i), 'answers' => $this->answers('Rate'.$i), 'intake_grade_id' => IntakeGrade::value('id')])->assertRedirect($this->publicUrl('application'));
            $this->get($this->publicUrl('application'))->assertOk();
            $this->post($this->publicUrl('another'))->assertRedirect($this->publicUrl());
        }
        $this->assertSame(6, Application::count());
    }

    public function test_due_waiting_applications_cannot_be_revived_by_recovery_or_manual_offers(): void
    {
        IntakeGrade::query()->update(['places' => 0]);
        [$row] = $this->submitted();
        $offers = app(AdmissionOffers::class);
        $offers->issue($this->admin, $this->op(['application_ids' => [$row->id]]));
        $this->travelTo($this->intake->closes_at->addDays(31));
        IntakeGrade::query()->update(['places' => 1]);
        $this->assertSame([], $offers->recover((int) $row->intake_grade_id));
        $this->refuse(404, fn () => $offers->issue($this->admin, $this->op(['application_ids' => [$row->id]])));
        $this->assertSame('waitlisted', $row->fresh()->state);
        $this->assertSame(0, Offer::count());
        $this->assertTrue(app(IntakeRetention::class)->due($row->fresh()));
    }

    public function test_delayed_expiry_recovery_keeps_original_deadline_for_retention(): void
    {
        [$row] = $this->submitted();
        $offers = app(AdmissionOffers::class);
        $offers->issue($this->admin, $this->op(['application_ids' => [$row->id]]));
        $deadline = Offer::firstOrFail()->expires_at;
        $this->travelTo($deadline->addDays(31));
        $this->assertTrue(app(IntakeRetention::class)->due($row->fresh()));
        $offers->recover((int) $row->intake_grade_id);
        $this->assertSame('offer_expired', $row->fresh()->state);
        $this->assertTrue($row->fresh()->terminal_at->equalTo($deadline));
        $this->assertTrue(app(IntakeRetention::class)->due($row->fresh()));
    }

    public function test_expired_browser_draft_can_start_again_without_disclosing_old_answers(): void
    {
        $this->intake->update(['keep_for_days' => 1]);
        [$row, $key] = $this->draft();
        $this->travelTo(now()->addDays(2));
        $this->withSession($this->applicantBrowser($row, $key))->get($this->publicUrl('application'))->assertNotFound()->assertSee('Start a new application')->assertDontSee('Applicantone');
        $this->post($this->publicUrl('another'))->assertRedirect($this->publicUrl());
        $this->get($this->publicUrl())->assertOk()->assertDontSee('Applicantone');
    }
}
