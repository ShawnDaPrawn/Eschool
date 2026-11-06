<?php

declare(strict_types=1);

namespace Tests\Feature\OpenStandards;

use App\Jobs\OpenStandards\BuildOneRosterExport;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSection;
use App\Models\OpenStandards\OneRosterExport;
use App\Models\OpenStandards\OpenBadgeCredential;
use App\Models\People\Enrolment;
use App\Models\Platform\AuditLog;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Recognition\AwardPresentation;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\OpenStandards\Badges\BadgeDocuments;
use App\Services\OpenStandards\Badges\BadgeSigningKeys;
use App\Services\OpenStandards\Badges\DataIntegrityProof;
use App\Services\OpenStandards\Badges\Jcs;
use App\Services\OpenStandards\OneRoster\OneRosterExports;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\AwardPresentations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;
use ZipArchive;

/**
 * Action Plan 5.9 (part 1): the OneRoster 1.2 CSV export and Open Badges 3.0
 * credentials for recognition awards. engineering/specs/platform/open-standards.md.
 */
class OpenStandardsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $admin;

    private int $year;

    private int $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 10:00:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        Storage::fake('academic_private');
        // An ephemeral signing seed, made for this test only.
        config(['open_standards.open_badges.signing_key' => base64_encode(random_bytes(32)), 'modules.awards' => ['always_on' => true]]);
        $this->admin = $this->superAdmin();
        $this->year = $this->schoolA->ids['session_year'];
        $this->learner = $this->schoolA->ids['asha'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->placeIn('5');
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
    }

    // ---- OneRoster ----------------------------------------------------------

    public function test_a_school_leader_exports_its_roster_as_oneroster_csv(): void
    {
        $this->teachingGroup();
        app(LuritsNumbers::class)->record(Students::findOrFail($this->learner), '8800000001', 'school_entry');
        $files = $this->export(['reason' => 'Sharing classes with our reading platform']);

        $this->assertSame($this->sorted(['manifest.csv', 'academicSessions.csv', 'classes.csv', 'courses.csv', 'enrollments.csv', 'orgs.csv', 'roles.csv', 'users.csv']), $this->sorted(array_keys($files)));
        $manifest = array_column($this->csv($files['manifest.csv']), 'value', 'propertyName');
        $this->assertSame('1.2', $manifest['oneroster.version']);
        $this->assertSame('bulk', $manifest['file.users']);
        $this->assertSame('absent', $manifest['file.demographics']);
        $this->assertSame('absent', $manifest['file.results']);

        $users = $this->csv($files['users.csv']);
        $asha = collect($users)->firstWhere('identifier', '8800000001');
        $this->assertNotNull($asha, 'The learner is identified by LURITS first');
        $this->assertSame('8800000001', $asha['username']);
        $this->assertStringContainsString('{LURITS:8800000001}', $asha['userIds']);
        $this->assertSame('05', $asha['grades']);
        $this->assertNotNull(collect($users)->firstWhere('givenName', User::findOrFail($this->schoolA->ids['tara_user'])->first_name), 'The teacher is exported');
        $roles = collect($this->csv($files['roles.csv']));
        $this->assertSame(['student', 'teacher'], $roles->pluck('role')->unique()->sort()->values()->all());
        $classes = collect($this->csv($files['classes.csv']));
        $this->assertSame(['homeroom', 'scheduled'], $classes->pluck('classType')->unique()->sort()->values()->all());
        $enrolments = collect($this->csv($files['enrollments.csv']));
        $this->assertTrue($enrolments->contains(fn ($e) => $e['userSourcedId'] === $asha['sourcedId'] && $e['role'] === 'student'));
        $this->assertSame(1, collect($this->csv($files['orgs.csv']))->count());

        $row = OneRosterExport::firstOrFail();
        $this->assertSame(1, $row->download_count);
        $this->assertSame('Sharing classes with our reading platform', $row->reason);
        $this->assertNotSame('Sharing classes with our reading platform', DB::table('oneroster_exports')->value('reason'), 'The reason is encrypted at rest');
        $this->assertStringStartsNotWith("PK\x03\x04", (string) Storage::disk('academic_private')->get((string) $row->artifact_path), 'The stored copy is encrypted');
        foreach (['open_standards.oneroster_export_requested', 'open_standards.oneroster_export_ready', 'open_standards.oneroster_export_downloaded'] as $action) {
            $this->assertTrue(AuditLog::where('action', $action)->exists(), $action);
        }
    }

    public function test_the_csv_files_carry_the_oneroster_1_2_column_headers(): void
    {
        $files = $this->export(['reason' => 'Checking the export format', 'include_demographics' => '1']);
        $expected = [
            'manifest.csv' => 'propertyName,value',
            'orgs.csv' => 'sourcedId,status,dateLastModified,name,type,identifier,parentSourcedId',
            'academicSessions.csv' => 'sourcedId,status,dateLastModified,title,type,startDate,endDate,parentSourcedId,schoolYear',
            'courses.csv' => 'sourcedId,status,dateLastModified,schoolYearSourcedId,title,courseCode,grades,orgSourcedId,subjects,subjectCodes',
            'classes.csv' => 'sourcedId,status,dateLastModified,title,grades,courseSourcedId,classCode,classType,location,schoolSourcedId,termSourcedIds,subjects,subjectCodes,periods',
            'users.csv' => 'sourcedId,status,dateLastModified,enabledUser,username,userIds,givenName,familyName,middleName,identifier,email,sms,phone,agentSourcedIds,grades,password,userMasterIdentifier,preferredGivenName,preferredMiddleName,preferredFamilyName,primaryOrgSourcedId,pronouns',
            'roles.csv' => 'sourcedId,status,dateLastModified,userSourcedId,roleType,role,beginDate,endDate,orgSourcedId,userProfileSourcedId',
            'enrollments.csv' => 'sourcedId,status,dateLastModified,classSourcedId,schoolSourcedId,userSourcedId,role,primary,beginDate,endDate',
            'demographics.csv' => 'sourcedId,status,dateLastModified,birthDate,sex,americanIndianOrAlaskaNative,asian,blackOrAfricanAmerican,nativeHawaiianOrOtherPacificIslander,white,demographicRaceTwoOrMoreRaces,hispanicOrLatinoEthnicity,countryOfBirthCode,stateOfBirthAbbreviation,cityOfBirth,publicSchoolResidenceStatus',
        ];
        $this->assertSame($this->sorted(array_keys($expected)), $this->sorted(array_keys($files)));
        foreach ($expected as $file => $header) {
            $this->assertSame($header, strtok($files[$file], "\r\n"), $file);
        }
        $manifest = array_column($this->csv($files['manifest.csv']), 'value', 'propertyName');
        $this->assertSame('bulk', $manifest['file.demographics']);
        foreach (['manifest.version', 'oneroster.version', 'file.academicSessions', 'file.categories', 'file.classes', 'file.classResources', 'file.courses', 'file.courseResources', 'file.demographics', 'file.enrollments', 'file.lineItemLearningObjectiveIds', 'file.lineItems', 'file.lineItemScoreScales', 'file.orgs', 'file.resources', 'file.resultLearningObjectiveIds', 'file.results', 'file.resultScoreScales', 'file.roles', 'file.scoreScales', 'file.userProfiles', 'file.userResources', 'file.users'] as $property) {
            $this->assertArrayHasKey($property, $manifest);
        }
        foreach ($this->csv($files['demographics.csv']) as $row) {
            $this->assertContains($row['sex'], ['male', 'female', 'other', 'unspecified']);
            // Race and ethnicity are never exported.
            $this->assertSame('', $row['white'].$row['asian'].$row['hispanicOrLatinoEthnicity']);
        }
        // Bulk files leave status and dateLastModified blank.
        $this->assertSame('', $this->csv($files['users.csv'])[0]['status']);
    }

    public function test_demographics_are_off_by_default_and_need_the_sensitive_profile_export_ability(): void
    {
        $files = $this->export(['reason' => 'Sharing classes with our reading platform']);
        $this->assertArrayNotHasKey('demographics.csv', $files);
        $this->assertStringNotContainsString('birthDate', implode('', $files));

        $staff = $this->exporter(['oneroster-export']);
        $this->actingAs($staff)->get('/open-standards')->assertOk()->assertDontSee('name="include_demographics"', false);
        Queue::fake();
        $this->actingAs($staff)->post('/open-standards/oneroster', ['reason' => 'Sharing classes with birth dates', 'include_demographics' => '1'])->assertForbidden();
        Queue::assertNothingPushed();
    }

    public function test_only_staff_with_the_sensitive_ability_may_export_and_a_reason_is_required(): void
    {
        Queue::fake();
        foreach (['tara_user', 'farid_user', 'asha_user'] as $person) {
            $user = User::findOrFail($this->schoolA->ids[$person]);
            $this->actingAs($user)->get('/open-standards')->assertForbidden();
            $this->actingAs($user)->post('/open-standards/oneroster', ['reason' => 'Trying to export the roster'])->assertForbidden();
        }
        $this->actingAs($this->admin)->post('/open-standards/oneroster', ['reason' => 'short'])->assertSessionHasErrors('reason');
        Queue::assertNothingPushed();
        $this->assertSame(0, OneRosterExport::count());
    }

    public function test_the_export_never_includes_another_schools_rows_and_school_b_cannot_read_it(): void
    {
        $this->teachingGroup();
        $files = $this->export(['reason' => 'Sharing classes with our reading platform', 'include_demographics' => '1']);
        foreach ($files as $name => $content) {
            $this->assertStringNotContainsString(self::SCHOOL_B_MARKER, $content, $name);
        }
        $namesB = DB::table('users')->whereIn('id', Students::acrossSchools()->where('school_id', self::SCHOOL_B_ID)->pluck('user_id'))->pluck('last_name');
        $this->assertNotEmpty($namesB);
        foreach ($namesB as $name) {
            $this->assertStringNotContainsString((string) $name, $files['users.csv']);
        }
        $id = (int) OneRosterExport::firstOrFail()->id;
        app(CurrentSchool::class)->run(self::SCHOOL_B_ID, function () use ($id): void {
            $this->assertNull(OneRosterExport::find($id));
            // School A's leader is nobody at school B: not found before any export is looked up.
            $this->refuse(404, fn () => app(OneRosterExports::class)->download($this->admin, $id, (int) $this->admin->id));
        });
    }

    public function test_the_signed_download_link_expires_and_works_only_for_its_requester(): void
    {
        $this->prepare(['reason' => 'Sharing classes with our reading platform']);
        $url = $this->downloadUrl($this->admin);
        $other = $this->exporter(['oneroster-export']);
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->travel(11)->minutes();
        $this->actingAs($this->admin)->get($url)->assertForbidden();
        // A fresh link still works until the export itself expires.
        $this->actingAs($this->admin)->get($this->downloadUrl($this->admin))->assertOk()->assertHeader('Content-Type', 'application/zip');
        $this->travel(3)->days();
        $this->actingAs($this->admin)->get('/open-standards')->assertOk()->assertSee(__('No longer available'));
        $this->refuse(410, fn () => app(OneRosterExports::class)->download($this->admin, (int) OneRosterExport::firstOrFail()->id, (int) $this->admin->id));
    }

    public function test_expired_export_files_are_deleted_in_their_own_school_keeping_row_and_audit(): void
    {
        $expired = $this->prepare(['reason' => 'Sharing classes with our reading platform']);
        $expiredPath = (string) $expired->fresh()->artifact_path;
        $fresh = $this->storedExport(1, now()->addDays(5));
        $other = $this->storedExport(self::SCHOOL_B_ID, now()->subDay());
        $disk = Storage::disk('academic_private');
        $this->travel(3)->days();

        $this->assertSame(1, app(CurrentSchool::class)->run(1, fn () => app(OneRosterExports::class)->prune()));
        $this->assertFalse($disk->exists($expiredPath), 'The expired file is deleted');
        $this->assertTrue($disk->exists($fresh['path']), 'An export still in its window is kept');
        $this->assertTrue($disk->exists($other['path']), "Pruning school A never touches school B's files");
        $row = $expired->fresh();
        $this->assertSame(OneRosterExport::PURGED, $row->state);
        $this->assertNotNull($row->purged_at);
        $this->assertNull($row->artifact_path);
        $this->assertNotNull($row->artifact_sha256, 'The row and its fingerprint stay');
        foreach (['open_standards.oneroster_export_requested', 'open_standards.oneroster_export_ready', 'open_standards.oneroster_export_purged'] as $action) {
            $this->assertTrue(AuditLog::where('action', $action)->where('subject_id', $row->id)->exists(), $action);
        }
        $this->refuse(410, fn () => app(OneRosterExports::class)->download($this->admin, (int) $row->id, (int) $this->admin->id));
        $this->actingAs($this->admin)->get('/open-standards')->assertOk()->assertDontSee('oneroster/'.$row->id.'/download', false)->assertSee('oneroster/'.$fresh['id'].'/download', false);

        // The daily command visits every school in its own context.
        $this->artisan('open-standards:prune-exports')->assertSuccessful();
        $this->assertFalse($disk->exists($other['path']));
        $this->assertTrue($disk->exists($fresh['path']));
        $this->assertSame(OneRosterExport::PURGED, app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => OneRosterExport::findOrFail($other['id'])->state));
        $this->assertSame(OneRosterExport::READY, OneRosterExport::findOrFail($fresh['id'])->state);
    }

    // ---- Open Badges --------------------------------------------------------

    public function test_a_guardian_downloads_a_signed_badge_credential_that_verifies(): void
    {
        $award = $this->award();
        $farid = $this->guardian('farid_user');
        $list = $this->api($farid)->getJson("/api/v2/open-badges/learners/{$this->learner}/credentials")->assertOk();
        $list->assertJsonPath('data.items.0.presentation_id', $award->id)->assertJsonPath('data.items.0.issued', false)->assertJsonPath('data.available', true);

        $credential = $this->credential($farid, $award);
        $this->assertSame(['VerifiableCredential', 'OpenBadgeCredential'], $credential['type']);
        $this->assertSame(BadgeDocuments::CONTEXT, $credential['@context']);
        $this->assertSame('eddsa-jcs-2022', $credential['proof']['cryptosuite']);
        $this->assertStringStartsWith('did:key:z6Mk', $credential['proof']['verificationMethod']);
        $this->assertSame('Community service', $credential['credentialSubject']['achievement']['name']);
        $this->assertSame('1EdTechRevocationList', $credential['credentialStatus']['type']);
        $name = Students::with('user')->findOrFail($this->learner)->user->full_name;
        $json = json_encode($credential);
        $this->assertStringNotContainsString($name, $json, 'The recipient name is hashed, never plain');
        $this->assertStringNotContainsString('8800000001', $json);

        // The same document on every download.
        $this->assertSame($credential, $this->credential($farid, $award));
        $this->assertSame(1, OpenBadgeCredential::count());
        $this->api($farid)->getJson("/api/v2/open-badges/learners/{$this->learner}/credentials")->assertJsonPath('data.items.0.issued', true);

        $result = $this->verify($credential, $name)->assertOk()->json();
        $this->assertTrue($result['verified']);
        $this->assertSame('valid', $result['status']);
        $this->assertTrue($result['checks']['recipient']);
        $this->assertSame(['id', 'name', 'issuer', 'achievement', 'awarded', 'valid_from'], array_keys($result['credential']));
        $this->assertFalse($this->verify($credential, 'Somebody Else')->json('verified'));
        $this->getJson('/open-badges/status/'.substr($credential['id'], 9))->assertOk()->assertJsonPath('revokedCredentials', []);
        $this->getJson(parse_url($credential['issuer']['id'], PHP_URL_PATH))->assertOk()->assertJsonPath('name', $credential['issuer']['name']);
        $this->assertTrue(AuditLog::where('action', 'open_badges.credential_issued')->exists());
        $this->assertTrue(AuditLog::where('action', 'open_badges.credential_downloaded')->exists());
    }

    public function test_signature_verification_round_trip_refuses_any_change_or_other_key(): void
    {
        $credential = $this->credential($this->guardian('farid_user'), $this->award());
        $proof = app(DataIntegrityProof::class);
        $keys = app(BadgeSigningKeys::class)->trusted(1);
        $decode = fn (array $doc) => json_decode((string) json_encode($doc), false);
        $this->assertTrue($proof->verify($decode($credential), $keys));

        $changed = $credential;
        $changed['credentialSubject']['achievement']['name'] = 'Head learner';
        $this->assertFalse($proof->verify($decode($changed), $keys));
        $this->assertFalse($this->verify($changed)->json('checks.proof'));
        $reordered = array_reverse($credential, true);
        $this->assertTrue($proof->verify($decode($reordered), $keys), 'JCS makes member order irrelevant');
        // School B's key never verifies school A's credential.
        $this->assertFalse($proof->verify($decode($credential), app(BadgeSigningKeys::class)->trusted(self::SCHOOL_B_ID)));
        // After a rotation the retired seed still verifies; without it nothing does.
        $old = config('open_standards.open_badges.signing_key');
        config(['open_standards.open_badges.signing_key' => base64_encode(random_bytes(32)), 'open_standards.open_badges.retired_signing_keys' => [$old]]);
        $this->assertTrue($this->verify($credential)->json('verified'));
        config(['open_standards.open_badges.retired_signing_keys' => []]);
        $this->assertFalse($this->verify($credential)->json('verified'));
        $this->assertSame('{"a":{},"b":[1,"\u0001"]}', Jcs::encode(json_decode('{"b":[1,"\u0001"],"a":{}}')));
    }

    public function test_a_credential_stays_byte_identical_after_a_rename_and_key_rotation(): void
    {
        $award = $this->award();
        $farid = $this->guardian('farid_user');
        $path = "/api/v2/open-badges/learners/{$this->learner}/credentials/{$award->id}";
        $first = (string) $this->api($farid)->getJson($path)->assertOk()->getContent();

        User::findOrFail($this->schoolA->ids['asha_user'])->forceFill(['first_name' => 'Renamed', 'last_name' => 'Learner'])->save();
        School::findOrFail(1)->forceFill(['name' => 'Renamed Primary'])->save();
        $old = config('open_standards.open_badges.signing_key');
        config(['open_standards.open_badges.signing_key' => base64_encode(random_bytes(32)), 'open_standards.open_badges.retired_signing_keys' => [$old]]);

        $again = (string) $this->api($farid)->getJson($path)->assertOk()->getContent();
        $this->assertSame($first, $again, 'The same credential id always returns the bytes it was issued with');
        $this->assertSame(1, OpenBadgeCredential::count());
        $this->assertTrue($this->verify(json_decode($again, true))->json('verified'));
        $this->assertNotSame($first, DB::table('open_badge_credentials')->value('document'), 'The stored document is encrypted at rest');
    }

    public function test_verification_trusts_only_the_key_recorded_when_the_credential_was_issued(): void
    {
        $credential = $this->credential($this->guardian('farid_user'), $this->award());
        $old = config('open_standards.open_badges.signing_key');
        config(['open_standards.open_badges.signing_key' => base64_encode(random_bytes(32)), 'open_standards.open_badges.retired_signing_keys' => [$old]]);
        // The same claims re-signed with today's key, which the school trusts for new credentials.
        $unsigned = $credential;
        unset($unsigned['proof']);
        $key = app(BadgeSigningKeys::class)->signing(1);
        $forged = app(DataIntegrityProof::class)->sign($unsigned, $key['secret'], $key['verification_method'], $credential['proof']['created']);
        $this->assertFalse($this->verify($forged)->json('checks.proof'));
        $this->assertTrue($this->verify($credential)->json('verified'));
    }

    public function test_a_withdrawn_award_revokes_its_credential(): void
    {
        $award = $this->award();
        $farid = $this->guardian('farid_user');
        $credential = $this->credential($farid, $award);
        app(AwardPresentations::class)->void($this->admin, (int) $award->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'reason' => 'Awarded to the wrong learner']);

        $result = $this->verify($credential)->assertOk()->json();
        $this->assertFalse($result['verified']);
        $this->assertSame('revoked', $result['status']);
        $this->assertTrue($result['checks']['revoked']);
        $this->assertStringNotContainsString('wrong learner', json_encode($result));
        $status = $this->getJson('/open-badges/status/'.substr($credential['id'], 9))->assertOk();
        $status->assertJsonPath('revokedCredentials.0.id', $credential['id']);
        $this->assertStringNotContainsString('wrong learner', $status->getContent());
        $this->assertSame('award_withdrawn', OpenBadgeCredential::firstOrFail()->revocation_code);
        $this->api($farid)->getJson("/api/v2/open-badges/learners/{$this->learner}/credentials/{$award->id}")->assertNotFound();
    }

    public function test_another_guardian_teacher_or_school_cannot_download_a_childs_badge(): void
    {
        $award = $this->award();
        $path = "/api/v2/open-badges/learners/{$this->learner}/credentials/{$award->id}";
        $gina = $this->guardian('gina_user', (int) $this->schoolA->ids['ben']);
        $this->assertContains($this->api($gina)->getJson($path)->status(), [403, 404]);
        $this->assertContains($this->api($gina)->getJson("/api/v2/open-badges/learners/{$this->learner}/credentials")->status(), [403, 404]);
        $this->api(User::findOrFail($this->schoolA->ids['tara_user']))->getJson($path)->assertForbidden();
        // A school B learner's award is not found from school A, even by id.
        $farid = $this->guardian('farid_user');
        $other = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => AwardPresentation::create(['learner_id' => $this->schoolB->ids['asha'], 'session_year_id' => $this->schoolB->ids['session_year'],
            'grade_level_id' => (int) GradeLevel::where('code', '5')->value('id'), 'presented_by' => $this->schoolB->ids['tara_user'], 'presented_on' => '2026-02-10',
            'title' => 'Baobab honours', 'criteria' => 'Synthetic', 'source_kind' => 'outside', 'state' => 'active', 'revision' => 1]));
        $this->assertContains($this->api($farid)->getJson("/api/v2/open-badges/learners/{$this->schoolB->ids['asha']}/credentials/{$other->id}")->status(), [403, 404]);
        $this->api($farid)->getJson("/api/v2/open-badges/learners/{$this->learner}/credentials/{$other->id}")->assertNotFound();
        $this->assertSame(0, OpenBadgeCredential::acrossSchools()->count());
    }

    public function test_learners_download_their_own_badges_only_from_the_allowed_age_stage(): void
    {
        $award = $this->award();
        $asha = User::findOrFail($this->schoolA->ids['asha_user']);
        $path = "/api/v2/open-badges/learners/{$this->learner}/credentials/{$award->id}";
        $this->api($asha)->getJson($path)->assertForbidden();
        $this->placeIn('9');
        $this->app['auth']->forgetGuards();
        $this->api($asha)->getJson($path)->assertOk()->assertJsonPath('credentialSubject.achievement.name', 'Community service');
    }

    public function test_no_credential_is_issued_without_configured_key_material(): void
    {
        $award = $this->award();
        config(['open_standards.open_badges.signing_key' => null]);
        $farid = $this->guardian('farid_user');
        $this->api($farid)->getJson("/api/v2/open-badges/learners/{$this->learner}/credentials")->assertOk()->assertJsonPath('data.available', false);
        $this->api($farid)->getJson("/api/v2/open-badges/learners/{$this->learner}/credentials/{$award->id}")->assertStatus(503);
        $this->assertSame(0, OpenBadgeCredential::count());
    }

    public function test_verification_of_an_unknown_or_malformed_credential_reveals_nothing(): void
    {
        $this->postJson('/open-badges/verify', ['id' => 'urn:uuid:'.Str::uuid(), 'type' => ['VerifiableCredential']])->assertOk()->assertJsonPath('verified', false)->assertJsonPath('credential', null);
        $this->postJson('/open-badges/verify', ['nothing' => true])->assertOk()->assertJsonPath('status', 'not_verified');
        $this->getJson('/open-badges/status/'.Str::uuid())->assertNotFound();
        $this->getJson('/open-badges/issuers/no-such-school')->assertNotFound();
    }

    // ---- helpers -------------------------------------------------------------

    private function placeIn(string $grade): void
    {
        ClassSection::with('class')->findOrFail($this->schoolA->ids['section_a'])->class->forceFill(['grade_level_id' => (int) GradeLevel::where('code', $grade)->value('id')])->save();
    }

    private function teachingGroup(): void
    {
        $teacher = (int) $this->schoolA->ids['tara_teacher'];
        HomeroomAllocation::create(['class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year, 'teacher_id' => $teacher, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic homeroom']);
        $group = TeachingGroup::create(['subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => (int) GradeLevel::where('code', '5')->value('id'), 'session_year_id' => $this->year,
            'class_section_id' => $this->schoolA->ids['section_a'], 'name' => 'Grade 5 maths', 'active' => true]);
        TeachingGroupMembership::create(['teaching_group_id' => $group->id, 'learner_id' => $this->learner, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic membership']);
        TeachingAllocation::create(['teaching_group_id' => $group->id, 'teacher_id' => $teacher, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic allocation']);
    }

    /** @return array{id: int, path: string} a ready export with a stored file, made directly in a school */
    private function storedExport(int $school, CarbonImmutable|\DateTimeInterface $expires): array
    {
        return app(CurrentSchool::class)->run($school, function () use ($expires): array {
            $requester = $this->admin->id;
            $year = (int) SessionYear::query()->value('id');
            $row = OneRosterExport::create(['requested_by' => $requester, 'session_year_id' => $year, 'reason' => 'Synthetic stored export', 'include_demographics' => false,
                'state' => OneRosterExport::READY, 'generation_id' => (string) Str::uuid(), 'ready_at' => now(), 'expires_at' => $expires]);
            $path = app(CurrentSchool::class)->storagePath('oneroster/'.$row->id).'/'.Str::uuid().'.enc';
            Storage::disk('academic_private')->put($path, 'synthetic cipher');
            $row->forceFill(['artifact_path' => $path, 'artifact_sha256' => hash('sha256', 'x'), 'cipher_sha256' => hash('sha256', 'synthetic cipher')])->save();

            return ['id' => (int) $row->id, 'path' => $path];
        });
    }

    private function prepare(array $input): OneRosterExport
    {
        Queue::fake();
        $this->actingAs($this->admin)->post('/open-standards/oneroster', $input)->assertRedirect(route('open-standards.index'));
        $row = OneRosterExport::latest('id')->firstOrFail();
        Queue::assertPushed(BuildOneRosterExport::class, fn ($job) => $job->exportId === (int) $row->id && $job->schoolId === 1);
        (new BuildOneRosterExport(1, (int) $row->id, (string) $row->generation_id))->handle(app(CurrentSchool::class));
        $this->assertSame(OneRosterExport::READY, $row->fresh()->state);

        return $row;
    }

    /** @return array<string, string> file name => content */
    private function export(array $input): array
    {
        $this->prepare($input);
        $response = $this->actingAs($this->admin)->get($this->downloadUrl($this->admin))->assertOk()->assertHeader('Content-Type', 'application/zip');
        $path = tempnam(sys_get_temp_dir(), 'oneroster-test-');
        file_put_contents($path, $response->getContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $files[$zip->getNameIndex($i)] = (string) $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($path);

        return $files;
    }

    private function downloadUrl(User $user): string
    {
        $page = $this->actingAs($user)->get('/open-standards')->assertOk()->getContent();
        $this->assertSame(1, preg_match('#href="([^"]*open-standards/oneroster/\d+/download[^"]*)"#', $page, $m));

        return html_entity_decode($m[1]);
    }

    /** @return list<array<string, string>> */
    private function csv(string $content): array
    {
        $lines = array_map(fn ($line) => str_getcsv($line, ',', '"', ''), array_values(array_filter(explode("\r\n", $content), fn ($l) => $l !== '')));
        $headers = array_shift($lines);

        return array_map(fn ($line) => array_combine($headers, $line), $lines);
    }

    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }

    /** A staff member whose custom role holds only these permissions. */
    private function exporter(array $permissions): User
    {
        $user = User::findOrFail($this->schoolA->ids['theo_user']);
        $role = Role::query()->create(['name' => 'Roster officer', 'custom_role' => 1, 'guard_name' => 'web']);
        $role->givePermissionTo($permissions);
        $user->assignRole($role);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    private function award(): AwardPresentation
    {
        return AwardPresentation::create(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'grade_level_id' => (int) GradeLevel::where('code', '5')->value('id'),
            'presented_by' => $this->admin->id, 'presented_on' => '2026-02-10', 'title' => 'Community service', 'criteria' => 'Sustained service throughout the year',
            'source_kind' => 'outside', 'outside_organisation' => 'Synthetic Youth Trust', 'state' => 'active', 'revision' => 1]);
    }

    private function guardian(string $person, ?int $learner = null): User
    {
        $user = User::findOrFail($this->schoolA->ids[$person]);
        GuardianLink::updateOrCreate(['learner_id' => $learner ?? $this->learner, 'guardian_user_id' => $user->id], ['status' => 'active', 'can_approve' => true, 'relationship' => 'guardian']);

        return $user;
    }

    /** @return array<string, mixed> */
    private function credential(User $user, AwardPresentation $award): array
    {
        $response = $this->api($user)->getJson("/api/v2/open-badges/learners/{$this->learner}/credentials/{$award->id}")->assertOk()->assertHeader('Content-Type', 'application/ld+json');
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));

        return json_decode((string) $response->getContent(), true);
    }

    private function verify(array $credential, ?string $name = null): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->postJson('/open-badges/verify', $name === null ? $credential : ['credential' => $credential, 'recipient_name' => $name]);
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('open-standards-synthetic')->plainTextToken)->withHeader('X-School-Id', '1')->withHeader('X-Account-Id', (string) $actor->id);
    }

    private function refuse(int $status, callable $work): void
    {
        try {
            $work();
            $this->fail('Expected refusal '.$status);
        } catch (HttpException|ModelNotFoundException $e) {
            $this->assertSame($status, $e instanceof HttpException ? $e->getStatusCode() : 404);
        }
    }
}
