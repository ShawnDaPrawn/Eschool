<?php

declare(strict_types=1);

namespace Tests\Feature\Passport;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\MarkRelease;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Academics\MarkSnapshot;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\Discipline\DisciplineRecord;
use App\Models\Pastoral\SupportPlan;
use App\Models\PeerTutoring\ServiceHour;
use App\Models\People\Enrolment;
use App\Models\Platform\AuditLog;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFile;
use App\Models\Platform\SchoolMembership;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\House;
use App\Models\Recognition\HousePoint;
use App\Models\Recognition\PointCategory;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Transfers\TransferReceipt;
use App\Models\User;
use App\Models\Wellbeing\CheckIn;
use App\Services\Passport\LearnerPassport;
use App\Services\People\Profiles\ProfileDefinitions;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Transfers\TransferAccept;
use App\Services\Transfers\TransferPackages;
use App\Services\Transfers\TransferVault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\SyntheticCleanScanner;
use Tests\TestCase;

/**
 * The learner passport v1 (Action Plan 4.10):
 * engineering/specs/people/learner-passport.md.
 *
 * Asha starts at School A (Jacaranda), earns an award, captains the chess
 * club and tutors for 45 minutes there, then moves to School B (Baobab) in a
 * consented, signed transfer that carries her released Science result. At
 * School B she wins a debating award. Records that must never appear in a
 * passport (discipline, wellbeing, support plans, house points, a withdrawn
 * award, a health document in the package, private reasons) are seeded with
 * markers, and every passport answer is checked for them.
 */
class LearnerPassportTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private const LURITS = '8800000001';

    /** Text that may never appear in any passport, view or download. */
    private const NEVER = ['HIDDEN-WITHDRAWN-AWARD', 'VOID-REASON-SECRET', 'HOUSEPOINT-HOUSE', 'HOUSEPOINT-CATEGORY', 'HOUSEPOINT-REASON',
        'DISCIPLINE-CODE', 'DISCIPLINE-SECRET', 'WELLBEING-SECRET', 'SUPPORTPLAN-SECRET', 'MEMBERSHIP-REASON-SECRET', 'Synthetic counselling referral', 'transfer-letter'];

    private User $sender;

    private User $receiver;

    private User $guardian;

    private User $asha;

    /** Asha's learner row at School A, then at School B. */
    private int $ashaA;

    private int $ashaB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00:00', 'Africa/Johannesburg'));
        foreach (['school_transfers', 'passport', 'awards', 'groups', 'peer_tutoring'] as $module) {
            config(['modules.'.$module => ['always_on' => true]]);
        }
        Storage::fake(TransferVault::DISK);
        Storage::fake(ProfileFiles::DISK);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->sender = $this->superAdmin();
        $this->ashaA = $this->schoolA->ids['asha'];
        $this->asha = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->guardian = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->guardian->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->ashaA, 'guardian_user_id' => $this->guardian->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        SessionYear::whereKey($this->schoolA->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Enrolment::create(['learner_id' => $this->ashaA, 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'reason' => 'Synthetic passport source', 'legacy_dates' => false]);
        app(StudentNumbers::class)->assign(Students::findOrFail($this->ashaA));
        app(LuritsNumbers::class)->record(Students::findOrFail($this->ashaA), self::LURITS, 'school_entry');
        $category = 0;
        app(CurrentSchool::class)->run(2, function () use (&$category): void {
            $this->receiver = User::create(['first_name' => 'Receiving', 'last_name' => 'Admin', 'email' => 'receiving-passport@example.test', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $this->receiver->assignRole('Super Admin');
            SessionYear::whereKey($this->schoolB->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
            $room = ClassSection::with('class')->findOrFail($this->schoolB->ids['section_a']);
            $room->class->forceFill(['grade_level_id' => GradeLevel::where('code', '5')->value('id')])->save();
            $category = (int) Category::query()->value('id');
        });
        $this->schoolARecords();
        $this->transfer($category);
        $this->travelTo(CarbonImmutable::parse('2026-03-10 10:00:00', 'Africa/Johannesburg'));
        app(CurrentSchool::class)->run(2, function (): void {
            AwardPresentation::create(['learner_id' => $this->ashaB, 'session_year_id' => $this->schoolB->ids['session_year'], 'grade_level_id' => GradeLevel::where('code', '5')->value('id'),
                'presented_by' => $this->receiver->id, 'presented_on' => '2026-03-09', 'title' => 'Debating finalist B-LIVE', 'criteria' => 'Reached the regional final', 'source_kind' => 'outside',
                'outside_organisation' => 'Regional Debating League', 'state' => 'active', 'revision' => 1]);
        });
    }

    /** School A's own records of Asha, the ones a passport may show and the ones it never may. */
    private function schoolARecords(): void
    {
        $grade = (int) GradeLevel::where('code', '5')->value('id');
        $year = $this->schoolA->ids['session_year'];
        $award = ['learner_id' => $this->ashaA, 'session_year_id' => $year, 'grade_level_id' => $grade, 'presented_by' => $this->sender->id, 'source_kind' => 'outside', 'revision' => 1];
        AwardPresentation::create($award + ['presented_on' => '2026-02-02', 'title' => 'Spelling bee winner A-LIVE', 'criteria' => 'Won the school spelling bee', 'state' => 'active']);
        AwardPresentation::create($award + ['presented_on' => '2026-02-03', 'title' => 'HIDDEN-WITHDRAWN-AWARD', 'criteria' => 'Given in error', 'state' => 'withdrawn', 'void_reason' => 'VOID-REASON-SECRET']);
        $group = ActivityGroup::create(['session_year_id' => $year, 'name' => 'Chess club A-LIVE', 'discipline' => 'Chess', 'season' => 'Summer', 'starts_on' => '2026-01-10', 'ends_on' => '2026-06-30', 'gender' => 'any', 'active' => true, 'revision' => 1]);
        ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $this->ashaA, 'role' => 'captain', 'active' => true, 'starts_on' => '2026-01-15', 'ends_on' => '2026-02-28', 'reason' => 'MEMBERSHIP-REASON-SECRET', 'revision' => 1]);
        $house = House::create(['name' => 'HOUSEPOINT-HOUSE', 'emblem' => 'star', 'primary_colour' => '#112233', 'secondary_colour' => '#445566', 'active' => true, 'revision' => 1]);
        $points = PointCategory::create(['name' => 'HOUSEPOINT-CATEGORY', 'default_points' => 1, 'minimum_points' => 1, 'maximum_points' => 5, 'active' => true, 'revision' => 1]);
        $this->withoutForeignKeys(function () use ($year, $grade, $house, $points): void {
            // Verified tutoring time; the session row itself is not part of a passport.
            ServiceHour::create(['peer_tutoring_session_id' => (string) Str::uuid(), 'learner_id' => $this->ashaA, 'verified_by' => $this->sender->id, 'kind' => 'earned', 'minutes' => 45]);
            HousePoint::create(['learner_id' => $this->ashaA, 'house_id' => $house->id, 'point_category_id' => $points->id, 'session_year_id' => $year, 'grade_level_id' => $grade, 'staff_id' => $this->sender->id,
                'house_name' => 'HOUSEPOINT-HOUSE', 'category_name' => 'HOUSEPOINT-CATEGORY', 'points' => 3, 'on_date' => '2026-02-04', 'reason' => 'HOUSEPOINT-REASON', 'public' => false, 'scope_kind' => 'school']);
            DisciplineRecord::create(['learner_id' => $this->ashaA, 'session_year_id' => $year, 'grade_level_id' => $grade, 'framework_id' => 999999, 'framework_revision' => 1, 'recorded_by' => $this->sender->id,
                'occurred_on' => '2026-02-05', 'infraction_code' => 'DISCIPLINE-CODE', 'level_code' => 'L1', 'status' => 'published', 'description' => 'DISCIPLINE-SECRET', 'basis' => ['DISCIPLINE-SECRET'], 'revision' => 1]);
            CheckIn::create(['learner_id' => $this->ashaA, 'session_year_id' => $year, 'scheduled_by' => $this->sender->id, 'prompt' => 'mood', 'due_on' => '2026-02-06', 'status' => 'answered', 'answer' => ['text' => 'WELLBEING-SECRET'], 'revision' => 1]);
            SupportPlan::create(['learner_id' => $this->ashaA, 'created_by' => $this->sender->id, 'started_on' => '2026-02-01', 'contents' => ['goal' => 'SUPPORTPLAN-SECRET']]);
        });
        // A released Science result travels in the package.
        $scope = ['subject_id' => $this->schoolA->ids['science'], 'grade_level_id' => $grade, 'session_year_id' => $year, 'learner_id' => $this->ashaA, 'term_id' => null, 'period_key' => 'year'];
        $snapshot = MarkSnapshot::create($scope + ['version' => 1, 'fingerprint' => str_repeat('b', 64), 'percentage_bp' => 7200, 'running_bp' => 7200, 'coverage_bp' => 10000, 'rating_level' => 6, 'complete' => true, 'inputs' => ['tasks' => []], 'result' => [], 'created_at' => now()]);
        $release = MarkRelease::create(array_diff_key($scope, ['learner_id' => true]) + ['version' => 1, 'mode' => 'official', 'fingerprint' => str_repeat('c', 64), 'released_by' => $this->sender->id, 'created_at' => now()]);
        MarkReleaseEntry::create(['mark_release_id' => $release->id, 'learner_id' => $this->ashaA, 'mark_snapshot_id' => $snapshot->id, 'created_at' => now()]);
    }

    /** A consented, signed transfer from School A to School B, carrying a health document too. */
    private function transfer(int $category): void
    {
        $health = ProfileFieldCategory::where('profile_type', 'learner')->where('key', 'health')->firstOrFail();
        $field = app(ProfileDefinitions::class)->field($this->sender, ['category_id' => $health->id, 'key' => 'transfer_document', 'label' => 'Synthetic counselling referral', 'type' => 'file', 'sensitive' => true, 'visible_to' => ['guardian', 'school_admin'], 'editable_by' => ['school_admin']]);
        $this->app->instance(UploadScanner::class, new SyntheticCleanScanner);
        app(ProfileFiles::class)->upload($this->sender, $this->asha, 'learner', 'transfer_document', UploadedFile::fake()->createWithContent('transfer-letter.pdf', "%PDF-1.4\nSYNTHETIC\n%%EOF"));
        $file = ProfileFile::where('definition_id', $field->id)->sole();
        $packages = app(TransferPackages::class);
        $p = $packages->prepare($this->sender, ['operation_id' => (string) Str::uuid(), 'learner_id' => $this->ashaA, 'guardian_id' => $this->guardian->id, 'to_school_id' => 2, 'external_school' => null,
            'last_attended_on' => '2026-02-28', 'starts_on' => '2026-03-01', 'report_ids' => [], 'document_ids' => [$file->id]]);
        $p = $packages->consent($this->guardian, (int) $p->id, ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision, 'fingerprint' => $p->fingerprint, 'version' => config('school_transfers.consent_version'), 'confirmation' => true]);
        $p = $packages->release($this->sender, (int) $p->id, ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision]);
        $this->assertCount(1, $p->contents['documents']);
        $this->assertCount(1, $p->contents['released_marks']);
        $op = ['operation_id' => (string) Str::uuid(), 'revision' => $p->revision, 'fingerprint' => $p->fingerprint, 'session_year_id' => $this->schoolB->ids['session_year'], 'class_section_id' => $this->schoolB->ids['section_a'], 'category_id' => $category, 'confirmation' => true];
        $receipt = app(CurrentSchool::class)->run(2, fn () => app(TransferAccept::class)->accept($this->receiver, (int) $p->id, $op));
        $this->ashaB = (int) $receipt->learner_id;
    }

    private function schoolBUser(string $key): User
    {
        return app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids[$key]));
    }

    private function withoutForeignKeys(callable $work): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $work();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function as(User $user, int $school): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->actingAs($user)->withHeaders(['X-School-Id' => (string) $school, 'X-Account-Id' => (string) $user->id])->withSession(['school_id' => $school]);
    }

    /** The panel signs in with the web guard, after any API call in the same test. */
    private function panel(User $user, int $school): self
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        return $this->flushHeaders()->actingAs($user, 'web')->withSession(['school_id' => $school]);
    }

    private function assertNeverShown(string $body): void
    {
        foreach ([...self::NEVER, $this->guardian->full_name, '2015-06-01', '2008-03-11'] as $marker) {
            $this->assertStringNotContainsString($marker, $body, 'A passport showed '.$marker);
        }
    }

    public function test_the_learner_sees_their_own_passport_across_the_schools_they_attended(): void
    {
        $data = $this->as($this->asha, 2)->getJson('/api/v2/passport/me')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('data');

        $this->assertSame('learner', $data['viewer']);
        $this->assertSame('all_schools', $data['scope']);
        $this->assertSame(['type' => 'lurits', 'value' => self::LURITS], $data['learner']['primary_identifier']);
        $this->assertSame([false, true], array_column($data['schools'], 'current'));
        [$former, $current] = $data['schools'];
        $this->assertSame(['2026-01-01', '2026-02-28'], [$former['attended_from'], $former['attended_to']]);
        $this->assertSame(['Spelling bee winner A-LIVE'], array_column($former['awards'], 'title'));
        $this->assertSame([['name' => 'Chess club A-LIVE', 'discipline' => 'Chess', 'season' => 'Summer', 'school_year' => SessionYear::findOrFail($this->schoolA->ids['session_year'])->name, 'role' => 'captain', 'starts_on' => '2026-01-15', 'ends_on' => '2026-02-28']], $former['activities']);
        $this->assertSame(['minutes' => 45, 'sessions' => 1], $former['service']);
        $this->assertSame(['2026-03-01', null], [$current['attended_from'], $current['attended_to']]);
        $this->assertSame(['Debating finalist B-LIVE'], array_column($current['awards'], 'title'));
        $this->assertNull($current['service']);
        $this->assertCount(1, $data['transfers']);
        $this->assertSame(['2026-02-28', '2026-03-01'], [$data['transfers'][0]['last_attended_on'], $data['transfers'][0]['starts_on']]);
        $this->assertSame(['official', 7200, 6], [$data['transfers'][0]['results'][0]['mode'], $data['transfers'][0]['results'][0]['percentage_bp'], $data['transfers'][0]['results'][0]['rating_level']]);
        $this->assertSame(['available' => false, 'items' => []], $data['reading']);
        $this->assertSame(['available' => false, 'formats' => ['json', 'pdf']], $data['export']);

        // Her own id gives the same passport; a LURITS number is not an id.
        $this->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertOk()->assertJsonPath('data.viewer', 'learner')->assertJsonCount(2, 'data.schools');
        $this->getJson('/api/v2/passport/learners/'.self::LURITS)->assertNotFound();
    }

    public function test_an_approving_guardian_sees_the_childs_passport_and_cannot_download_it(): void
    {
        $this->as($this->guardian, 2);
        $response = $this->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertOk()
            ->assertJsonPath('data.viewer', 'guardian')->assertJsonPath('data.scope', 'all_schools')->assertJsonPath('data.export', null)
            ->assertJsonPath('data.learner.primary_identifier.value', self::LURITS);
        $this->assertSame(['Spelling bee winner A-LIVE', 'Debating finalist B-LIVE'], array_merge(...array_map(fn ($s) => array_column($s['awards'], 'title'), $response->json('data.schools'))));
        // A guardian has no passport of their own and no download (open question: default no).
        $this->getJson('/api/v2/passport/me')->assertForbidden();
        $this->get('/api/v2/passport/me/export?format=json', ['Accept' => 'application/json'])->assertForbidden()->assertHeaderMissing('Content-Disposition');
        $this->assertSame(0, AuditLog::query()->where('action', 'passport.exported')->count());
    }

    public function test_staff_see_only_what_their_school_holds_and_the_signed_package_addressed_to_it(): void
    {
        $this->as($this->receiver, 2);
        $data = $this->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertOk()->json('data');
        $this->assertSame(['staff', 'this_school', null], [$data['viewer'], $data['scope'], $data['export']]);
        $this->assertSame(['type' => 'lurits', 'value' => self::LURITS], $data['learner']['primary_identifier']);
        $this->assertCount(1, $data['schools']);
        $this->assertTrue($data['schools'][0]['current']);
        $this->assertSame(['Debating finalist B-LIVE'], array_column($data['schools'][0]['awards'], 'title'));
        $this->assertSame([], $data['schools'][0]['activities']);
        $this->assertNull($data['schools'][0]['service']);
        // What School A sent, in the signed package: the result, not School A's live records.
        $this->assertSame(7200, $data['transfers'][0]['results'][0]['percentage_bp']);
        $body = json_encode($data, JSON_THROW_ON_ERROR);
        foreach (['Spelling bee winner A-LIVE', 'Chess club A-LIVE', '"minutes":45'] as $live) {
            $this->assertStringNotContainsString($live, $body);
        }

        // The panel: found by name, never by LURITS number.
        $this->panel($this->receiver, 2)->get('/learner-passports?q='.urlencode($this->asha->first_name))->assertOk()->assertSee(route('passport-panel.show', $this->ashaB), false);
        $this->get('/learner-passports?q='.self::LURITS)->assertOk()->assertDontSee(route('passport-panel.show', $this->ashaB), false)->assertSee('No learners match that name.');
        $page = $this->get('/learner-passports/learners/'.$this->ashaB)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSeeInOrder(['LURITS number', self::LURITS, 'Debating finalist B-LIVE', 'Results carried with a transfer', '72.00%'])
            ->assertDontSee('Spelling bee winner A-LIVE')->assertDontSee('Chess club A-LIVE');
        $this->assertNeverShown($page->getContent());
    }

    public function test_the_staff_view_never_reads_another_school_and_the_learner_view_does_only_for_her_own_rows(): void
    {
        $contexts = function (User $user, int $learner): array {
            $seen = [];
            $listening = false;
            Event::listen(QueryExecuted::class, function () use (&$seen, &$listening): void {
                if ($listening) {
                    $schools = app(CurrentSchool::class);
                    $seen[] = $schools->isAcrossSchools() ? 'across' : $schools->idOrNull();
                }
            });
            $this->actingAs($user);
            // Only the passport's own queries: not switching the test into School B and back.
            app(CurrentSchool::class)->run(2, function () use ($user, $learner, &$listening): void {
                $listening = true;
                app(LearnerPassport::class)->forLearner($user, $learner);
                $listening = false;
            });

            return array_values(array_unique($seen));
        };

        // Staff of School B: every query ran in School B, none in School A or across schools.
        $this->assertSame([2], $contexts($this->receiver, $this->ashaB));
        // Asha herself: School A's records of her are read inside School A.
        $this->assertSame([2, 1], $contexts($this->asha, $this->ashaB));
    }

    public function test_a_package_whose_signature_does_not_verify_is_left_out(): void
    {
        app(CurrentSchool::class)->run(2, function (): void {
            $receipt = TransferReceipt::sole();
            $signature = $receipt->signature;
            $signature['signature'] = base64_encode(str_repeat("\0", SODIUM_CRYPTO_SIGN_BYTES));
            DB::table('transfer_receipts')->where('id', $receipt->id)->update(['signature' => Crypt::encrypt($signature)]);
        });

        $this->as($this->receiver, 2)->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertOk()->assertJsonPath('data.transfers', []);
    }

    public function test_wrong_role_wrong_school_and_another_family_are_refused(): void
    {
        // A teacher without profile permission.
        $tara = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->as($tara, 1)->getJson('/api/v2/passport/learners/'.$this->ashaA)->assertForbidden();
        $this->panel($tara, 1)->get('/learner-passports')->assertForbidden();
        // Another learner and a parent known only from the legacy mother field, at School A.
        $this->as(User::findOrFail($this->schoolA->ids['ben_user']), 1)->getJson('/api/v2/passport/learners/'.$this->ashaA)->assertNotFound();
        $this->as(User::findOrFail($this->schoolA->ids['mona_user']), 1)->getJson('/api/v2/passport/learners/'.$this->ashaA)->assertNotFound();
        // School B's own learner and parent cannot open Asha's passport there.
        $this->as($this->schoolBUser('ben_user'), 2)->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertNotFound();
        $this->as($this->schoolBUser('gina_user'), 2)->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertNotFound();
        // Each school's staff: the other school's learner row does not exist for them.
        $this->as($this->receiver, 2)->getJson('/api/v2/passport/learners/'.$this->ashaA)->assertNotFound();
        $this->panel($this->receiver, 2)->get('/learner-passports/learners/'.$this->ashaA)->assertNotFound();
        $this->as($this->sender, 1)->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertNotFound();
        $this->as($this->receiver, 2)->getJson('/api/v2/passport/learners/'.$this->schoolA->ids['ben'])->assertNotFound();
        // A guardian whose link may not approve.
        app(CurrentSchool::class)->run(2, fn () => GuardianLink::where('learner_id', $this->ashaB)->where('guardian_user_id', $this->guardian->id)->update(['can_approve' => false]));
        $this->as($this->guardian, 2)->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertNotFound();
        // A guardian of the child at School A asking from School B about a School A row.
        $this->as($this->guardian, 2)->getJson('/api/v2/passport/learners/'.$this->ashaA)->assertNotFound();
        // Switched off: nothing answers.
        config(['modules.passport' => ['name' => 'Learner passport', 'default' => false]]);
        $this->as($this->asha, 2)->getJson('/api/v2/passport/me')->assertNotFound();
        $this->panel($this->receiver, 2)->get('/learner-passports/learners/'.$this->ashaB)->assertNotFound();
    }

    public function test_the_former_school_sees_only_its_own_records_never_the_new_schools(): void
    {
        $data = $this->as($this->sender, 1)->getJson('/api/v2/passport/learners/'.$this->ashaA)->assertOk()->json('data');

        $this->assertSame(['staff', 'this_school'], [$data['viewer'], $data['scope']]);
        $this->assertSame(['Spelling bee winner A-LIVE'], array_column($data['schools'][0]['awards'], 'title'));
        $this->assertSame([], $data['transfers']);
        $this->assertStringNotContainsString('Debating finalist B-LIVE', json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function test_excluded_records_never_appear_anywhere(): void
    {
        DB::table('users')->where('id', $this->asha->id)->update(['dob' => '2008-03-11']);
        $this->travelTo(CarbonImmutable::parse('2026-03-11 09:00:00', 'Africa/Johannesburg'));
        $bodies = [
            $this->as($this->asha, 2)->getJson('/api/v2/passport/me')->assertOk()->getContent(),
            $this->get('/api/v2/passport/me/export?format=json', ['Accept' => 'application/json'])->assertOk()->getContent(),
            $this->as($this->guardian, 2)->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertOk()->getContent(),
            $this->as($this->receiver, 2)->getJson('/api/v2/passport/learners/'.$this->ashaB)->assertOk()->getContent(),
            $this->panel($this->receiver, 2)->get('/learner-passports/learners/'.$this->ashaB)->assertOk()->getContent(),
            $this->as($this->sender, 1)->getJson('/api/v2/passport/learners/'.$this->ashaA)->assertOk()->getContent(),
        ];
        foreach ($bodies as $body) {
            $this->assertNeverShown($body);
        }
        $this->assertStringContainsString('Spelling bee winner A-LIVE', $bodies[1]);
    }

    public function test_the_download_is_refused_before_18_and_allowed_from_the_18th_birthday_in_school_time(): void
    {
        DB::table('users')->where('id', $this->asha->id)->update(['dob' => '2008-03-11']);
        $this->as($this->asha, 2);
        $this->getJson('/api/v2/passport/me')->assertOk()->assertJsonPath('data.export.available', false);
        $this->get('/api/v2/passport/me/export?format=json', ['Accept' => 'application/json'])->assertForbidden()->assertHeaderMissing('Content-Disposition');
        $this->assertSame(0, AuditLog::query()->where('action', 'passport.exported')->count());

        // 22:30 UTC on the 10th is already the 11th, her birthday, in Johannesburg.
        $this->travelTo(CarbonImmutable::parse('2026-03-10 22:30:00', 'UTC'));
        $this->getJson('/api/v2/passport/me')->assertOk()->assertJsonPath('data.export.available', true);
        $json = $this->get('/api/v2/passport/me/export?format=json', ['Accept' => 'application/json'])->assertOk()
            ->assertHeader('Content-Type', 'application/json')->assertHeader('Content-Disposition', 'attachment; filename="learner-passport.json"')->assertHeader('Cache-Control', 'no-store, private');
        $file = json_decode($json->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('navigateed-learner-passport-v1', $file['format']);
        $this->assertSame(self::LURITS, $file['passport']['learner']['primary_identifier']['value']);
        $this->assertCount(2, $file['passport']['schools']);
        $pdf = $this->get('/api/v2/passport/me/export?format=pdf', ['Accept' => 'application/json'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->get('/api/v2/passport/me/export?format=csv', ['Accept' => 'application/json'])->assertUnprocessable();

        $audits = AuditLog::query()->where('action', 'passport.exported')->orderBy('id')->get();
        $this->assertSame(['json', 'pdf'], $audits->map(fn ($row) => $row->after['format'])->all());
        $this->assertSame([2, 2], $audits->map(fn ($row) => (int) $row->school_id)->all());
        $this->assertSame([$this->asha->id, $this->asha->id], $audits->map(fn ($row) => (int) $row->actor_id)->all());
        foreach ($audits as $row) {
            $this->assertStringNotContainsString(self::LURITS, json_encode($row->toArray(), JSON_THROW_ON_ERROR));
            $this->assertStringNotContainsString('2008-03-11', json_encode($row->toArray(), JSON_THROW_ON_ERROR));
        }

        // Staff never download a learner's passport.
        $this->as($this->receiver, 2)->get('/api/v2/passport/me/export', ['Accept' => 'application/json'])->assertForbidden();
        $this->assertSame(2, AuditLog::query()->where('action', 'passport.exported')->count());
    }

    public function test_the_date_of_birth_never_leaves_and_an_unknown_one_means_no_download(): void
    {
        DB::table('users')->where('id', $this->asha->id)->update(['dob' => null]);
        $this->as($this->asha, 2)->getJson('/api/v2/passport/me')->assertOk()->assertJsonPath('data.export.available', false);
        $this->get('/api/v2/passport/me/export', ['Accept' => 'application/json'])->assertForbidden();
        DB::table('users')->where('id', $this->asha->id)->update(['dob' => '2015-06-01']);
        foreach ([[$this->asha, 2, $this->ashaB], [$this->guardian, 2, $this->ashaB], [$this->receiver, 2, $this->ashaB]] as [$user, $school, $learner]) {
            $body = $this->as($user, $school)->getJson('/api/v2/passport/learners/'.$learner)->assertOk()->getContent();
            $this->assertStringNotContainsString('2015-06-01', $body);
            $this->assertStringNotContainsString('dob', $body);
        }
    }

    public function test_an_unapproved_learner_is_refused_when_approval_is_enforced(): void
    {
        config(['identity.approval_enforced' => true]);

        $this->as($this->asha, 2)->getJson('/api/v2/passport/me')->assertForbidden();
    }
}
