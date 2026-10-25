<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use App\Models\Academics\AcademicTransition;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\MarkRelease;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Academics\MarkSnapshot;
use App\Models\Academics\ReportBatch;
use App\Models\Academics\ReportCard;
use App\Models\Academics\ReportTemplate;
use App\Models\Attendance;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFile;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferReceipt;
use App\Models\Transfers\TransferStage;
use App\Models\User;
use App\Services\People\Profiles\ProfileDefinitions;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Notifications\DeliveryGuard;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use App\Services\Transfers\TransferAccept;
use App\Services\Transfers\TransferAuthority;
use App\Services\Transfers\TransferEnrollment;
use App\Services\Transfers\TransferNotices;
use App\Services\Transfers\TransferPackages;
use App\Services\Transfers\TransferRetention;
use App\Services\Transfers\TransferVault;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class SchoolTransferTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $sender;

    private User $receiver;

    private User $guardian;

    private int $learner;

    private int $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00:00', 'Africa/Johannesburg'));
        config(['modules.school_transfers' => ['always_on' => true]]);
        Storage::fake(TransferVault::DISK);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->sender = $this->superAdmin();
        $this->learner = $this->schoolA->ids['asha'];
        $this->guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $this->guardian->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        SessionYear::whereKey($this->schoolA->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'reason' => 'Synthetic transfer source', 'legacy_dates' => false]);
        app(StudentNumbers::class)->assign(Students::findOrFail($this->learner));
        Attendance::forceCreate(['student_id' => $this->learner, 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->schoolA->ids['session_year'], 'date' => '2026-02-05', 'type' => Attendance::TYPE_PRESENT]);
        app(CurrentSchool::class)->run(2, function (): void {
            $this->receiver = User::create(['first_name' => 'Receiving', 'last_name' => 'Admin', 'email' => 'receiving-transfer@example.test', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $this->receiver->assignRole('Super Admin');
            SessionYear::whereKey($this->schoolB->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
            $room = ClassSection::with('class')->findOrFail($this->schoolB->ids['section_a']);
            $room->class->forceFill(['grade_level_id' => GradeLevel::where('code', '5')->value('id')])->save();
            $this->category = (int) Category::query()->value('id');
        });
    }

    private function input(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->learner, 'guardian_id' => $this->guardian->id, 'to_school_id' => 2, 'external_school' => null, 'last_attended_on' => '2026-02-28', 'starts_on' => '2026-03-01', 'report_ids' => [], 'document_ids' => []];
    }

    private function consent(TransferPackage $p): TransferPackage
    {
        return app(TransferPackages::class)->consent($this->guardian, (int) $p->id, ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision, 'fingerprint' => $p->fingerprint, 'version' => config('school_transfers.consent_version'), 'confirmation' => true]);
    }

    private function released(): TransferPackage
    {
        $p = $this->consent(app(TransferPackages::class)->prepare($this->sender, $this->input()));

        return app(TransferPackages::class)->release($this->sender, (int) $p->id, ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision]);
    }

    private function acceptance(TransferPackage $p): array
    {
        return ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision, 'fingerprint' => $p->fingerprint, 'session_year_id' => $this->schoolB->ids['session_year'], 'class_section_id' => $this->schoolB->ids['section_a'], 'category_id' => $this->category, 'confirmation' => true];
    }

    private function failWith(int $status, callable $work): void
    {
        try {
            $work();
            $this->fail('Expected refusal '.$status);
        } catch (HttpException|ModelNotFoundException $e) {
            $this->assertSame($status, $e instanceof HttpException ? $e->getStatusCode() : 404);
        }
    }

    public function test_consented_round_trip_closes_source_opens_destination_and_never_copies_online_approval(): void
    {
        $p = $this->released();
        $this->assertNull(Enrolment::where('learner_id', $this->learner)->sole()->ends_on);
        $this->assertSame(1, count($p->contents['attendance']));
        $bytes = app(TransferVault::class)->readSource($p);
        $this->assertStringStartsWith('PK', $bytes);
        $number = Students::findOrFail($this->learner)->student_number;
        $userId = (int) Students::findOrFail($this->learner)->user_id;
        $op = $this->acceptance($p);
        $receipt = app(CurrentSchool::class)->run(2, function () use ($p, $number, $userId, $op) {
            $r = app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op);
            $student = Students::findOrFail($r->learner_id);
            $this->assertSame($userId, (int) $student->user_id);
            $this->assertSame($number, $student->student_number);
            $this->assertSame('2026-03-01', Enrolment::where('learner_id', $student->id)->sole()->starts_on->toDateString());
            $this->assertSame(0, LearnerApproval::where('learner_id', $student->id)->count());
            $this->assertTrue(GuardianLink::where('learner_id', $student->id)->where('guardian_user_id', $this->guardian->id)->where('can_approve', true)->exists());
            $this->assertSame('2026-03-01', SchoolMembership::where('school_id', 2)->where('user_id', $userId)->where('role', 'Student')->sole()->starts_on->toDateString());
            $this->assertSame('join', AcademicTransition::where('learner_id', $student->id)->sole()->kind);
            $this->assertSame($r->id, app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op)->id);
            $this->assertSame(1, TransferReceipt::count());

            return $r;
        });
        $this->assertSame('2026-02-28', Enrolment::where('learner_id', $this->learner)->sole()->ends_on->toDateString());
        $this->assertSame('accepted', $p->fresh()->state);
        $this->assertSame(1, AcademicTransition::where('learner_id', $this->learner)->where('kind', 'leave')->count());
        $this->assertSame(0, TransferReceipt::count());
        $this->assertNotSame($this->learner, (int) $receipt->learner_id);
    }

    public function test_parent_consent_is_named_verified_and_bound_to_exact_frozen_contents(): void
    {
        $input = $this->input();
        $p = app(TransferPackages::class)->prepare($this->sender, $input);
        $this->assertSame($p->id, app(TransferPackages::class)->prepare($this->sender, $input)->id);
        $this->assertSame(1, TransferPackage::count());
        $other = User::findOrFail($this->schoolA->ids['mona_user']);
        $other->forceFill(['email_verified_at' => now()])->save();
        $this->failWith(404, fn () => app(TransferAuthority::class)->ownConsent($other, (int) $p->id));
        $this->failWith(409, fn () => app(TransferPackages::class)->consent($this->guardian, (int) $p->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'fingerprint' => str_repeat('0', 64), 'version' => config('school_transfers.consent_version'), 'confirmation' => true]));
        $this->guardian->forceFill(['email_verified_at' => null])->save();
        $this->failWith(403, fn () => $this->consent($p));
        $this->assertSame('awaiting_consent', $p->fresh()->state);
    }

    public function test_withdrawn_consent_removes_pending_destination_access_without_closing_enrolment(): void
    {
        $p = $this->released();
        $op = $this->acceptance($p);
        app(TransferPackages::class)->cancel($this->guardian, (int) $p->id, ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision], true);
        app(CurrentSchool::class)->run(2, fn () => $this->failWith(404, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op)));
        $this->assertNull(Enrolment::where('learner_id', $this->learner)->sole()->ends_on);
        $this->assertSame('cancelled', $p->fresh()->state);
    }

    public function test_expiry_source_changes_and_wrong_destination_never_mutate_enrolments(): void
    {
        $p = $this->released();
        $op = $this->acceptance($p);
        $this->failWith(404, fn () => app(TransferAccept::class)->accept($this->sender, (int) $p->id, $op));
        Enrolment::where('learner_id', $this->learner)->update(['ends_on' => '2026-12-31']);
        app(CurrentSchool::class)->run(2, fn () => $this->failWith(409, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op)));
        $p->update(['expires_at' => now()->subSecond()]);
        app(CurrentSchool::class)->run(2, fn () => $this->failWith(409, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op)));
        $this->assertSame('2026-12-31', Enrolment::where('learner_id', $this->learner)->sole()->ends_on->toDateString());
    }

    public function test_receiving_failure_rolls_back_source_closure_and_discards_the_unadopted_copy(): void
    {
        $p = $this->released();
        $op = $this->acceptance($p);
        app()->instance(TransferEnrollment::class, new class extends TransferEnrollment
        {
            public function receive(User $actor, User $identity, User $guardian, TransferPackage $package, array $d): Students
            {
                abort(409, 'Synthetic receiving failure');
            }
        });
        app(CurrentSchool::class)->run(2, function () use ($p, $op): void {
            $this->failWith(409, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op));
            $this->assertSame(0, TransferReceipt::count());
            foreach (TransferStage::where('adopted', false)->get() as $stage) {
                Storage::disk(TransferVault::DISK)->assertMissing($stage->path);
            }
        });
        $this->assertNull(Enrolment::where('learner_id', $this->learner)->sole()->ends_on);
        $this->assertSame(0, AcademicTransition::where('learner_id', $this->learner)->count());
        $this->assertSame('released', $p->fresh()->state);
    }

    public function test_tampered_archive_and_platform_operator_are_refused(): void
    {
        $p = $this->released();
        $op = $this->acceptance($p);
        $disk = Storage::disk(TransferVault::DISK);
        $disk->put($p->artifact_path, $disk->get($p->artifact_path).'tampered');
        app(CurrentSchool::class)->run(2, fn () => $this->failWith(409, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op)));
        PlatformStaffIdentity::create(['user_id' => $this->sender->id]);
        $this->failWith(403, fn () => app(TransferPackages::class)->prepare($this->sender, $this->input()));
        $this->assertNull(Enrolment::where('learner_id', $this->learner)->sole()->ends_on);
    }

    public function test_external_release_produces_signed_archive_and_closes_only_the_source_school(): void
    {
        $d = $this->input();
        $d['to_school_id'] = null;
        $d['external_school'] = 'Synthetic external school';
        $p = $this->consent(app(TransferPackages::class)->prepare($this->sender, $d));
        $p = app(TransferPackages::class)->release($this->sender, (int) $p->id, ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision]);
        $this->assertSame('released', $p->state);
        $this->assertSame('Synthetic external school', $p->contents['to_school_name']);
        $this->assertStringStartsWith('PK', app(TransferVault::class)->readSource($p));
        $this->assertSame('2026-02-28', Enrolment::where('learner_id', $this->learner)->sole()->ends_on->toDateString());
        app(CurrentSchool::class)->run(2, fn () => $this->assertSame(0, TransferReceipt::count()));
    }

    public function test_interleaved_exact_retry_cannot_overwrite_an_adopted_private_archive(): void
    {
        $actor = $this->sender;
        $input = $this->input();
        app()->instance(TransferVault::class, new class($actor, $input) extends TransferVault
        {
            private bool $nested = false;

            public function __construct(private User $actor, private array $input) {}

            public function put(TransferStage $stage, string $bytes): string
            {
                if (! $this->nested) {
                    $this->nested = true;
                    app(TransferPackages::class)->prepare($this->actor, $this->input);
                }

                return parent::put($stage, $bytes);
            }
        });
        $p = app(TransferPackages::class)->prepare($actor, $input);
        $this->assertSame(1, TransferPackage::count());
        $this->assertStringStartsWith('PK', app(TransferVault::class)->readSource($p));
        $this->assertSame(1, count(Storage::disk(TransferVault::DISK)->allFiles()));
    }

    public function test_expiring_source_and_uncommitted_copies_preserves_the_receiving_archive(): void
    {
        $p = $this->released();
        $op = $this->acceptance($p);
        $r = app(CurrentSchool::class)->run(2, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op));
        $orphan = app(TransferVault::class)->stage($this->sender, (string) Str::uuid(), str_repeat('a', 64));
        app(TransferVault::class)->put($orphan, 'synthetic orphan');
        $orphan->update(['expires_at' => now()->subSecond()]);
        $p->update(['expires_at' => now()->subSecond()]);
        $this->artisan('transfers:prune-copies')->assertSuccessful();
        Storage::disk(TransferVault::DISK)->assertMissing($orphan->path);
        Storage::disk(TransferVault::DISK)->assertMissing($p->artifact_path);
        Storage::disk(TransferVault::DISK)->assertExists($r->artifact_path);
        $this->assertSame('accepted', $p->fresh()->state);
    }

    public function test_role_withdrawal_during_file_staging_prevents_the_atomic_handoff(): void
    {
        $p = $this->released();
        $op = $this->acceptance($p);
        $actorId = (int) $this->receiver->id;
        app()->instance(TransferVault::class, new class($actorId) extends TransferVault
        {
            public function __construct(private int $actorId) {}

            public function put(TransferStage $stage, string $bytes): string
            {
                $hash = parent::put($stage, $bytes);
                SchoolMembership::where('school_id', 2)->where('user_id', $this->actorId)->update(['ends_on' => '2026-02-09']);

                return $hash;
            }
        });
        app(CurrentSchool::class)->run(2, fn () => $this->failWith(403, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op)));
        $this->assertNull(Enrolment::where('learner_id', $this->learner)->sole()->ends_on);
        $this->assertSame('released', $p->fresh()->state);
    }

    public function test_reports_released_marks_and_current_permitted_documents_round_trip_as_signed_history(): void
    {
        $grade = (int) GradeLevel::where('code', '5')->value('id');
        $year = $this->schoolA->ids['session_year'];
        $subject = $this->schoolA->ids['science'];
        $scope = ['subject_id' => $subject, 'grade_level_id' => $grade, 'session_year_id' => $year, 'learner_id' => $this->learner, 'term_id' => null, 'period_key' => 'year'];
        $snapshot = MarkSnapshot::create($scope + ['version' => 1, 'fingerprint' => str_repeat('b', 64), 'percentage_bp' => 7200, 'running_bp' => 7200, 'coverage_bp' => 10000, 'rating_level' => 6, 'complete' => true, 'inputs' => ['tasks' => []], 'result' => [], 'created_at' => now()]);
        $release = MarkRelease::create(array_diff_key($scope, ['learner_id' => true]) + ['version' => 1, 'mode' => 'official', 'fingerprint' => str_repeat('c', 64), 'released_by' => $this->sender->id, 'created_at' => now()]);
        MarkReleaseEntry::create(['mark_release_id' => $release->id, 'learner_id' => $this->learner, 'mark_snapshot_id' => $snapshot->id, 'created_at' => now()]);
        MarkSnapshot::create($scope + ['version' => 2, 'fingerprint' => str_repeat('d', 64), 'percentage_bp' => 9900, 'running_bp' => 9900, 'coverage_bp' => 10000, 'rating_level' => 7, 'complete' => true, 'inputs' => ['private_marker' => 'Never released'], 'result' => [], 'created_at' => now()]);
        $template = ReportTemplate::create(['name' => 'Synthetic transfer design', 'design' => [], 'revision' => 1]);
        $batch = ReportBatch::create(['report_template_id' => $template->id, 'session_year_id' => $year, 'grade_level_id' => $grade, 'class_section_id' => $this->schoolA->ids['section_a'], 'mode' => 'official', 'state' => 'draft', 'selection' => [], 'design' => [], 'source_fingerprint' => str_repeat('a', 64), 'fingerprint' => str_repeat('a', 64), 'created_by' => $this->sender->id]);
        $card = ReportCard::create(['report_batch_id' => $batch->id, 'learner_id' => $this->learner, 'payload' => ['year_name' => '2026', 'term_name' => null]]);
        $pdf = Pdf::loadHTML('<p>Synthetic released transfer report</p>')->output();
        $reportPath = app(CurrentSchool::class)->storagePath('reports').'/'.$card->id.'/'.Str::uuid().'.enc';
        Storage::disk(TransferVault::DISK)->put($reportPath, Crypt::encryptString($pdf));
        $card->update(['pdf_path' => $reportPath, 'pdf_sha256' => hash('sha256', $pdf), 'generated_fingerprint' => $batch->fingerprint, 'generated_at' => now()]);
        $batch->update(['state' => 'released', 'released_at' => now(), 'released_by' => $this->sender->id]);
        Storage::fake(ProfileFiles::DISK);
        $category = ProfileFieldCategory::where('profile_type', 'learner')->where('key', 'health')->firstOrFail();
        $field = app(ProfileDefinitions::class)->field($this->sender, ['category_id' => $category->id, 'key' => 'transfer_document', 'label' => 'Synthetic transfer document', 'type' => 'file', 'sensitive' => true, 'visible_to' => ['guardian', 'school_admin'], 'editable_by' => ['school_admin']]);
        $document = "%PDF-1.4\nSYNTHETIC TRANSFER DOCUMENT\n%%EOF";
        app(ProfileFiles::class)->upload($this->sender, User::findOrFail($this->schoolA->ids['asha_user']), 'learner', 'transfer_document', UploadedFile::fake()->createWithContent('transfer-letter.pdf', $document));
        $file = ProfileFile::where('definition_id', $field->id)->sole();
        $input = $this->input();
        $input['report_ids'] = [(int) $card->id];
        $input['document_ids'] = [$file->id];
        $p = $this->consent(app(TransferPackages::class)->prepare($this->sender, $input));
        $p = app(TransferPackages::class)->release($this->sender, (int) $p->id, ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision]);
        $this->assertCount(1, $p->contents['released_marks']);
        $this->assertSame(7200, $p->contents['released_marks'][0]['percentage_bp']);
        $this->assertCount(1, $p->contents['reports']);
        $this->assertCount(1, $p->contents['documents']);
        $bytes = app(TransferVault::class)->readSource($p);
        $temporary = tempnam(sys_get_temp_dir(), 'transfer-test-');
        file_put_contents($temporary, $bytes);
        chmod($temporary, 0600);
        $zip = new \ZipArchive;
        try {
            $this->assertTrue($zip->open($temporary));
            $manifest = $zip->getFromName('manifest.json');
            $signature = json_decode($zip->getFromName('signature.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertTrue(sodium_crypto_sign_verify_detached(base64_decode($signature['signature']), $manifest, base64_decode($signature['public_key'])));
            $this->assertSame($pdf, $zip->getFromName('reports/'.$card->id.'.pdf'));
            $this->assertSame($document, $zip->getFromName('documents/'.$file->id.'.pdf'));
            $this->assertStringNotContainsString('Never released', $manifest);
        } finally {
            $zip->close();
            unlink($temporary);
        }
        $op = $this->acceptance($p);
        $receipt = app(CurrentSchool::class)->run(2, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op));
        $this->assertSame(7200, $receipt->contents['released_marks'][0]['percentage_bp']);
        app(CurrentSchool::class)->run(2, function (): void {
            $this->assertSame(0, MarkReleaseEntry::where('learner_id', Students::where('user_id', $this->schoolA->ids['asha_user'])->value('id'))->count());
        });
    }

    public function test_routing_columns_cannot_disagree_with_the_guardian_signed_manifest(): void
    {
        $p = $this->released();
        DB::table('transfer_packages')->where('id', $p->id)->update(['to_school_id' => 1]);
        $this->failWith(409, fn () => app(TransferVault::class)->verify($p->fresh()));
        $this->assertNull(Enrolment::where('learner_id', $this->learner)->sole()->ends_on);
    }

    public function test_third_school_overlap_requires_review_without_revealing_that_school(): void
    {
        $identity = User::findOrFail($this->schoolA->ids['asha_user']);
        $third = School::forceCreate(['name' => 'Confidential Third School', 'slug' => 'third-school-transfer-fixture', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed((int) $third->id);
        app(CurrentSchool::class)->run((int) $third->id, function () use ($identity): void {
            $demo = DemoSchool::build('Third transfer');
            $identity->unsetRelation('roles')->unsetRelation('permissions')->assignRole('Student');
            $room = ClassSection::findOrFail($demo->ids['section_a']);
            $student = Students::create(['user_id' => $identity->id, 'class_id' => $room->class_id, 'class_section_id' => $room->id, 'category_id' => Category::query()->value('id'), 'admission_no' => 'THIRD-TRANSFER', 'admission_date' => '2026-02-01']);
            Enrolment::create(['learner_id' => $student->id, 'session_year_id' => $demo->ids['session_year'], 'class_section_id' => $room->id, 'starts_on' => '2026-02-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic existing third-school enrolment', 'legacy_dates' => false]);
        });
        $p = $this->released();
        $op = $this->acceptance($p);
        app(CurrentSchool::class)->run(2, function () use ($p, $op): void {
            try {
                app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op);
                $this->fail('Overlap must require review');
            } catch (HttpException $e) {
                $this->assertSame(409, $e->getStatusCode());
                $this->assertStringNotContainsString('Confidential Third School', $e->getMessage());
            }
            $this->assertSame(0, TransferReceipt::count());
        });
        $this->assertNull(Enrolment::where('learner_id', $this->learner)->sole()->ends_on);
    }

    public function test_unapproved_identity_is_not_activated_and_retained_external_exports_do_not_repeat_departure(): void
    {
        $user = User::findOrFail($this->schoolA->ids['asha_user']);
        $user->forceFill(['status' => 0])->save();
        $p = $this->released();
        $op = $this->acceptance($p);
        app(CurrentSchool::class)->run(2, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op));
        $this->assertSame(0, (int) $user->fresh()->status);
        $this->failWith(409, fn () => app(TransferPackages::class)->prepare($this->sender, $this->input()));
        $input = $this->input();
        $input['to_school_id'] = null;
        $input['external_school'] = 'Retained external export';
        $copy = $this->consent(app(TransferPackages::class)->prepare($this->sender, $input));
        $this->assertTrue($copy->contents['export_only']);
        app(TransferPackages::class)->release($this->sender, (int) $copy->id, ['operation_id' => (string) Str::uuid(), 'revision' => $copy->revision]);
        $this->assertSame(1, AcademicTransition::where('learner_id', $this->learner)->where('kind', 'leave')->count());
    }

    public function test_authenticated_http_guardian_and_receiver_round_trip_keeps_archives_private(): void
    {
        $u = '/api/v2/transfers/';
        $this->actingAs($this->sender)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->sender->id]);
        $this->getJson($u.'access')->assertOk()->assertJsonPath('data.staff', true);
        foreach (['learners', 'destinations', 'years', 'rooms', 'categories', 'guardians?learner_id='.$this->learner, 'reports?learner_id='.$this->learner, 'documents?learner_id='.$this->learner.'&guardian_id='.$this->guardian->id] as $kind) {
            $this->getJson($u.'choices/'.$kind)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        }
        $input = $this->input();
        $id = $this->postJson($u.'packages', $input)->assertOk()->assertJsonPath('data.revision', 1)->json('data.id');
        $this->postJson($u.'packages', $input)->assertOk()->assertJsonPath('data.id', $id);
        $this->getJson($u.'outgoing')->assertOk()->assertJsonPath('data.items.0.id', $id);
        $this->getJson($u.'outgoing/'.$id)->assertOk()->assertJsonPath('data.can_release', false)->assertDontSee('learner_user_id')->assertDontSee('artifact_path');
        $this->actingAs($this->guardian)->withHeader('X-Account-Id', (string) $this->guardian->id);
        $this->getJson($u.'consents')->assertOk()->assertJsonPath('data.items.0.id', $id);
        $p = TransferPackage::findOrFail($id);
        $this->getJson($u.'consents/'.$id)->assertOk()->assertJsonPath('data.can_consent', true);
        $this->get($u.'consents/'.$id.'/archive', ['Accept' => 'application/json'])->assertOk()->assertHeader('Content-Type', 'application/zip')->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson($u.'packages/'.$id.'/consent', ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'fingerprint' => $p->fingerprint, 'version' => config('school_transfers.consent_version'), 'confirmation' => true])->assertOk()->assertJsonPath('data.revision', 2);
        $this->actingAs($this->sender)->withHeader('X-Account-Id', (string) $this->sender->id)->postJson($u.'packages/'.$id.'/release', ['operation_id' => (string) Str::uuid(), 'revision' => 2])->assertOk()->assertJsonPath('data.revision', 3);
        $this->actingAs($this->receiver)->withHeaders(['X-School-Id' => '2', 'X-Account-Id' => (string) $this->receiver->id]);
        $this->getJson($u.'outgoing/'.$id)->assertNotFound();
        $this->getJson($u.'incoming')->assertOk()->assertJsonPath('data.items.0.id', $id);
        $this->getJson($u.'incoming/'.$id)->assertOk()->assertJsonPath('data.can_accept', true);
        $r = $this->postJson($u.'packages/'.$id.'/accept', $this->acceptance($p->fresh()))->assertOk()->assertJsonPath('data.kind', 'receipt')->json('data.id');
        $this->getJson($u.'receipts')->assertOk()->assertJsonPath('data.items.0.id', $r);
        $this->getJson($u.'receipts/'.$r)->assertOk()->assertJsonPath('data.state', 'accepted');
        $this->travelTo(now()->addDays(15));
        app(CurrentSchool::class)->run(1, fn () => app(TransferRetention::class)->expire());
        $this->get($u.'receipts/'.$r.'/archive', ['Accept' => 'application/json'])->assertOk()->assertHeader('Content-Type', 'application/zip');
        $this->getJson($u.'incoming/'.$id)->assertNotFound();
    }

    public function test_http_transfer_authority_precedes_validation_replay_and_foreign_reads(): void
    {
        $u = '/api/v2/transfers/';
        $p = app(TransferPackages::class)->prepare($this->sender, $this->input());
        $teacher = User::findOrFail($this->schoolA->ids['theo_user']);
        $this->actingAs($teacher)->withHeader('X-School-Id', '1');
        foreach (['access', 'outgoing', 'outgoing/'.$p->id, 'choices/learners'] as $path) {
            $this->getJson($u.$path)->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
        }
        $this->postJson($u.'packages', [])->assertForbidden();
        $this->actingAs($this->guardian)->getJson($u.'outgoing/'.$p->id)->assertForbidden();
        $this->getJson($u.'consents/'.$p->id)->assertOk();
        GuardianLink::where('learner_id', $this->learner)->where('guardian_user_id', $this->guardian->id)->update(['can_approve' => false]);
        $this->getJson($u.'consents/'.$p->id)->assertNotFound();
        $this->getJson($u.'consents')->assertOk()->assertJsonCount(0, 'data.items');
        $this->postJson($u.'packages/'.$p->id.'/consent', [])->assertNotFound();
        $this->actingAs($this->sender)->postJson($u.'packages?external_school=private-query', $this->input())->assertUnprocessable();
        $this->withHeader('X-Account-Id', (string) $this->guardian->id)->getJson($u.'outgoing/'.$p->id)->assertForbidden();
        $this->withHeader('X-Account-Id', (string) $this->sender->id);
        $this->sender->forceFill(['password_change_required' => true])->save();
        $this->postJson($u.'packages', [])->assertForbidden();
        $this->actingAs($this->receiver)->withHeaders(['X-School-Id' => '2', 'X-Account-Id' => (string) $this->receiver->id])->getJson($u.'incoming/'.$p->id)->assertNotFound();
    }

    public function test_incoming_list_and_download_recheck_current_consent_and_source_authority(): void
    {
        $p = $this->released();
        $u = '/api/v2/transfers/';
        $this->actingAs($this->receiver)->withHeader('X-School-Id', '2');
        $this->getJson($u.'incoming')->assertOk()->assertJsonCount(1, 'data.items');
        app(CurrentSchool::class)->run(1, fn () => GuardianLink::where('learner_id', $this->learner)->where('guardian_user_id', $this->guardian->id)->update(['can_approve' => false]));
        $this->getJson($u.'incoming')->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson($u.'incoming/'.$p->id)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $this->get($u.'incoming/'.$p->id.'/archive', ['Accept' => 'application/json'])->assertNotFound();
    }

    public function test_panel_named_choices_prepare_release_accept_and_private_refusals(): void
    {
        $this->actingAs($this->sender);
        $this->get('/school-transfers')->assertOk()->assertSee('Sending school')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/school-transfers?learner_id='.$this->learner.'&guardian_id='.$this->guardian->id)->assertOk()->assertSee('Prepare a transfer for guardian review');
        $input = $this->input() + ['expected_account_id' => $this->sender->id, 'expected_school_id' => 1];
        $this->post('/school-transfers/packages', $input)->assertRedirect()->assertHeader('Cache-Control', 'no-store, private');
        $p = TransferPackage::sole();
        $this->get('/school-transfers?record='.$p->id)->assertOk()->assertSee($p->fingerprint)->assertSee('Dated attendance')->assertDontSee('Release consented transfer');
        $p = $this->consent($p);
        $this->get('/school-transfers?record='.$p->id)->assertOk()->assertSee('Release consented transfer');
        $op = ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision, 'expected_account_id' => $this->sender->id, 'expected_school_id' => 1];
        $this->post('/school-transfers/packages/'.$p->id.'/release', $op)->assertRedirect();
        app(CurrentSchool::class)->run(2, function () use ($p): void {
            $this->withSession(['school_id' => 2])->actingAs($this->receiver);
            $this->get('/school-transfers?lane=incoming&record='.$p->id)->assertOk()->assertSee('Accept this transfer')->assertSee('Receiving homeroom');
            $this->post('/school-transfers/packages/'.$p->id.'/accept', $this->acceptance($p->fresh()) + ['expected_account_id' => $this->receiver->id, 'expected_school_id' => 2])->assertRedirect();
            $r = TransferReceipt::sole();
            $this->get('/school-transfers?lane=receipts&record='.$r->id)->assertOk()->assertSee('Historical released marks');
        });
    }

    public function test_private_panel_validation_and_historical_refusal_never_flash_names_or_bypass_identity(): void
    {
        $this->actingAs($this->sender);
        $input = $this->input() + ['expected_account_id' => $this->sender->id, 'expected_school_id' => 1];
        $input['to_school_id'] = null;
        $input['external_school'] = '<script>Private receiving school</script>';
        $input['starts_on'] = 'bad-date';
        $this->post('/school-transfers/packages', $input)->assertUnprocessable()->assertSee('&lt;script&gt;Private receiving school&lt;/script&gt;', false)->assertSessionMissing('_old_input.external_school')->assertHeader('Cache-Control', 'no-store, private');
        $input['expected_account_id'] = $this->guardian->id;
        $this->post('/school-transfers/packages', $input)->assertForbidden()->assertDontSee('Private receiving school');
        $input['expected_account_id'] = $this->sender->id;
        $past = SessionYear::create(['name' => 'Transfer old year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->withSession(['session_year' => $past->id])->post('/school-transfers/packages', $input)->assertStatus(423)->assertSessionMissing('_old_input.external_school');
        $this->sender->forceFill(['password_change_required' => true])->save();
        $this->post('/school-transfers/packages', $input)->assertForbidden()->assertDontSee('Private receiving school');
    }

    public function test_transfer_consent_notices_are_generic_exact_retries_and_revoke_before_inbox_or_transport(): void
    {
        Queue::fake();
        $input = $this->input();
        $p = app(TransferPackages::class)->prepare($this->sender, $input);
        app(TransferPackages::class)->prepare($this->sender, $input);
        $notice = Notification::where('type', TransferNotices::TYPE)->sole();
        $this->assertStringNotContainsString($p->contents['learner_name'], $notice->message);
        $this->assertStringNotContainsString($p->contents['to_school_name'], $notice->message);
        $this->assertSame(['link', 'transfer_package_id', 'transfer_revision'], array_keys($notice->delivery_payload));
        $guard = app(TransferNotices::class);
        $payload = $notice->delivery_payload;
        $this->assertTrue($guard->allows($this->guardian, $notice->type, $payload));
        $payload['transfer_package_id'] = (string) $payload['transfer_package_id'];
        $this->assertTrue($guard->allows($this->guardian, $notice->type, $payload));
        $payload['transfer_revision'] = true;
        $this->assertFalse($guard->allows($this->guardian, $notice->type, $payload));
        $rules = app(NotificationRules::class);
        $rules->choose($this->guardian, $notice->type, 'email', true);
        $deliveryKey = DeliveryLog::queued('mail', $notice->type, (int) $this->guardian->id, $this->guardian->email);
        $delivery = NotificationDelivery::where('delivery_key', $deliveryKey)->sole();
        $transport = app(DeliveryGuard::class);
        $this->assertTrue($transport->allows($delivery, $notice->type, 'email', $this->guardian->email, $notice->delivery_payload));
        GuardianLink::where('learner_id', $this->learner)->where('guardian_user_id', $this->guardian->id)->update(['can_approve' => false]);
        $this->assertFalse($transport->allows($delivery, $notice->type, 'email', $this->guardian->email, $notice->delivery_payload));
        $this->assertFalse($guard->allows($this->guardian, $notice->type, $notice->delivery_payload));
        $this->assertSame([], app(Notifier::class)->send($this->guardian, $notice->type, $notice->title, $notice->message, $notice->delivery_payload['link'], $notice, ['in_app']));
    }

    public function test_private_archive_expiry_during_read_cannot_return_frozen_bytes(): void
    {
        $p = app(TransferPackages::class)->prepare($this->sender, $this->input());
        app()->instance(TransferVault::class, new class extends TransferVault
        {
            public function readSource(TransferPackage $row): string
            {
                $bytes = parent::readSource($row);
                $row->update(['expires_at' => now()->subSecond()]);

                return $bytes;
            }
        });
        $this->actingAs($this->guardian)->getJson('/api/v2/transfers/consents/'.$p->id.'/archive')->assertConflict()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_retained_transfer_archive_prevents_destructive_schema_rollback(): void
    {
        $p = app(TransferPackages::class)->prepare($this->sender, $this->input());
        $migration = require database_path('migrations/2026_10_25_130000_create_consented_school_transfers.php');
        try {
            $migration->down();
            $this->fail('Retained transfer history was removed.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Retained school transfer records', $error->getMessage());
        }
        $this->assertSame($p->id, TransferPackage::sole()->id);
        $this->assertStringStartsWith('PK', app(TransferVault::class)->readSource($p));
    }
}
