<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolOffboarding;
use App\Models\User;
use App\Services\Academics\Transitions\ExitSigning;
use App\Services\Platform\Offboarding\OffboardingExport;
use App\Services\Platform\Saas\PlatformOffboarding;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;
use ZipArchive;

/**
 * School offboarding (Action Plan SA.11): HQ starts it, the leaving school's
 * Super Admin downloads its full export and confirms receipt (or the deadline
 * passes), HQ archives the school, and deletion needs two approvals by
 * different people and runs as a dry run only.
 *
 * School B (school 2) is the leaving school: school 1 is the install's
 * default school, which can never be archived. School 1 is "the other
 * school" whose rows must never change.
 * engineering/specs/platform/offboarding.md.
 */
class SchoolOffboardingTest extends TestCase
{
    use UsesTwoSchools;

    private const LEAVING = self::SCHOOL_B_ID;

    private const OTHER = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        Storage::fake(OffboardingExport::DISK);
        $this->travelTo(CarbonImmutable::parse('2026-11-04 09:00', PlatformOffboarding::TIMEZONE));
        DB::table('school_memberships')->where('starts_on', '>', '2026-01-01')->update(['starts_on' => '2026-01-01']);
    }

    private function staff(string $role, string $name = ''): User
    {
        $email = ($name ?: $role).'@platform.example';
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($name ?: $role), 'last_name' => 'Operator', 'email' => $email, 'mobile' => '', 'password' => 'Synthetic-offboarding-password-582!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addDays(60)]);

        return $user;
    }

    /** The leaving school's Super Admin (the fixture has none). */
    private function headOfB(string $email = 'head@baobab.example'): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => 'Head', 'last_name' => 'Baobab Admin', 'email' => $email, 'mobile' => '', 'password' => 'Synthetic-offboarding-password-582!', 'status' => 1]));
        app(CurrentSchool::class)->run(self::LEAVING, fn () => $user->assignRole('Super Admin'));
        DB::table('school_memberships')->where('user_id', $user->id)->update(['starts_on' => '2026-01-01']);

        return $user->fresh();
    }

    private function start(array $changes = []): SchoolOffboarding
    {
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding', $changes + [
            'reason' => 'The school has moved to another provider.', 'receipt_deadline' => '2026-11-20',
            'delete_on' => '2026-12-31', 'retention_terms' => 'Clause 12: data deleted within 60 days of termination.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        return $this->offboarding();
    }

    private function offboarding(): SchoolOffboarding
    {
        return SchoolOffboarding::acrossSchools()->where('school_id', self::LEAVING)->latest('id')->firstOrFail();
    }

    /** The queued build, run directly (the test transaction never commits, so afterCommit jobs never fire). */
    private function build(SchoolOffboarding $row): SchoolOffboarding
    {
        app(CurrentSchool::class)->run(self::LEAVING, fn () => app(OffboardingExport::class)->generate((int) $row->id, (string) $row->generation_id));

        return $row->fresh();
    }

    private function link(User $admin): string
    {
        $url = $this->actingAs($admin)->get('/school-exit')->assertOk()->viewData('offboarding')['download_url'];
        $this->assertIsString($url);

        return $url;
    }

    /** Started, built, downloaded and confirmed, then archived by Operations. */
    private function archived(User $operations, User $admin): SchoolOffboarding
    {
        $this->actingAs($operations);
        $row = $this->build($this->start());
        $this->get($this->link($admin))->assertOk();
        $this->post('/school-exit/'.$row->id.'/receipt', ['confirm' => '1'])->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($operations)->post('/saas/schools/'.self::LEAVING.'/offboarding/archive')->assertRedirect()->assertSessionHasNoErrors();

        return $row->fresh();
    }

    /** @return array<string, string> entry name => contents */
    private function unzip(string $bytes): array
    {
        $path = storage_path('framework/testing/offboarding-'.bin2hex(random_bytes(6)).'.zip');
        @mkdir(dirname($path), 0700, true);
        file_put_contents($path, $bytes);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[(string) $zip->getNameIndex($i)] = (string) $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($path);

        return $entries;
    }

    /** @return array<string, int|string> every school-owned table's rows of one school, its memberships and status */
    private function rowsOf(int $schoolId): array
    {
        $counts = [];
        foreach (SchoolOwnedTables::withSchoolId() as $table) {
            if (Schema::hasTable($table)) {
                $counts[$table] = DB::table($table)->where('school_id', $schoolId)->count();
            }
        }
        $counts['school_memberships'] = DB::table('school_memberships')->where('school_id', $schoolId)->count();
        $counts['status'] = (string) DB::table('schools')->where('id', $schoolId)->value('status');

        return $counts;
    }

    public function test_operations_offboards_a_school_whose_super_admin_collects_and_confirms_its_export(): void
    {
        $operations = $this->staff('operations');
        $admin = $this->headOfB();
        $learner = User::query()->findOrFail($this->schoolB->ids['asha_user']);
        $this->actingAs($operations)->post('/saas/schools/'.self::LEAVING.'/contracts', [
            'starts_on' => '2025-01-01', 'ends_on' => '2026-12-31', 'contracted_learners' => 300, 'price_per_learner' => '24.50', 'signatory' => 'Principal Baobab',
            'operator_agreement_version' => 'v2', 'operator_agreement_signed_on' => '2025-01-01', 'deletion_days_after_end' => 30,
        ])->assertRedirect()->assertSessionHasNoErrors();

        // 1. Started with the contract's retention terms; anything entered is ignored.
        $this->get('/saas/schools/'.self::LEAVING.'/offboarding')->assertOk()->assertSee('data deleted 30 days after it ends on 2026-12-31, so on 2027-01-30.');
        $row = $this->start();
        $this->assertSame([SchoolOffboarding::PREPARING, 'contract', '2027-01-30', '2026-11-20'], [$row->state, $row->retention_source, $row->delete_on->toDateString(), $row->receipt_deadline->toDateString()]);
        $this->get('/saas/schools/'.self::LEAVING.'/offboarding')->assertOk()->assertSee('The export is being prepared.');

        // 2. The export is built for the school; HQ sees counts and states, never a row.
        $row = $this->build($row);
        $this->assertSame(SchoolOffboarding::READY, $row->state);
        $this->assertGreaterThan(50, $row->table_count);
        $this->assertTrue(Storage::disk(OffboardingExport::DISK)->exists($row->artifact_path));
        $this->assertStringStartsWith('schools/2/offboarding/'.$row->id.'/', $row->artifact_path);
        $this->assertStringNotContainsString('PK', substr(Storage::disk(OffboardingExport::DISK)->get($row->artifact_path), 0, 64));
        $this->get('/saas/schools/'.self::LEAVING.'/offboarding')->assertOk()->assertSee('Not downloaded yet.')->assertSee($row->artifact_sha256)
            ->assertDontSee($learner->email)->assertDontSee('school-exit/'.$row->id.'/download');
        $this->assertDatabaseHas('notifications', ['school_id' => self::LEAVING, 'type' => 'school.offboarding_export']);

        // The Super Admin's page offers a signed link bound to them, and the archive is the school's own data, signed.
        $bytes = $this->get($this->link($admin))->assertOk()->assertHeader('Content-Type', 'application/zip')->assertHeader('Cache-Control', 'no-store, private')->getContent();
        $this->assertSame($row->artifact_sha256, hash('sha256', $bytes));
        $files = $this->unzip($bytes);
        $manifest = json_decode($files['manifest.json'], true);
        $verification = json_decode($files['verification.json'], true);
        $this->assertSame(OffboardingExport::FORMAT, $manifest['format']);
        $this->assertSame(app(CurrentSchool::class)->run(self::LEAVING, fn () => app(ExitSigning::class)->sign('')['public_key']), $verification['public_key']);
        $this->assertTrue(sodium_crypto_sign_verify_detached(base64_decode(trim($files['manifest.signature'])), $files['manifest.json'], base64_decode($verification['public_key'])));
        $byTable = array_column($manifest['tables'], null, 'table');
        $this->assertSame(hash('sha256', $files['tables/students.jsonl']), $byTable['students']['sha256']);
        $this->assertSame(DB::table('students')->where('school_id', self::LEAVING)->count(), $byTable['students']['rows']);
        foreach (['students', 'users', 'school_memberships', 'audit_log'] as $table) {
            foreach (array_filter(explode("\n", $files['tables/'.$table.'.jsonl'])) as $line) {
                $record = json_decode($line, true);
                $this->assertArrayNotHasKey('password', $record);
                if (array_key_exists('school_id', $record)) {
                    $this->assertSame(self::LEAVING, (int) $record['school_id'], $table);
                }
            }
        }
        $this->assertStringContainsString($learner->email, $files['tables/users.jsonl']);
        $this->assertContains('secret_key', $byTable['card_keys']['omitted_columns']);
        $this->assertArrayHasKey('school_contracts', $manifest['not_exported']);
        $this->assertArrayNotHasKey('tables/school_contracts.jsonl', $files);

        // 3. Receipt confirmed in the panel; 4. archived by Operations, deletion scheduled.
        $this->post('/school-exit/'.$row->id.'/receipt', ['confirm' => '1'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([SchoolOffboarding::RECEIVED, 'confirmed', $admin->id, 1], [$row->fresh()->state, $row->fresh()->receipt_basis, (int) $row->fresh()->received_by, $row->fresh()->download_count]);
        $this->actingAs($operations)->post('/saas/schools/'.self::LEAVING.'/offboarding/archive')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('archived', DB::table('schools')->where('id', self::LEAVING)->value('status'));
        $this->assertSame(SchoolOffboarding::ARCHIVED, $row->fresh()->state);
        $this->get('/saas/schools/'.self::LEAVING.'/offboarding')->assertOk()->assertSee('Deletion is scheduled for 2027-01-30.');

        // Every step is audited with ids and states, never the reason text.
        $actions = DB::table('audit_log')->where('school_id', self::LEAVING)->where('action', 'like', 'platform_school_offboarding_%')->orderBy('id')->pluck('action')->all();
        $this->assertSame(['platform_school_offboarding_started', 'platform_school_offboarding_export_ready', 'platform_school_offboarding_export_downloaded',
            'platform_school_offboarding_receipt_confirmed', 'platform_school_offboarding_archived'], $actions);
        $this->assertTrue(DB::table('audit_log')->where('school_id', self::LEAVING)->where('action', 'platform_school_status_changed')->exists());
        $this->assertStringNotContainsString('another provider', (string) DB::table('audit_log')->where('action', 'like', 'platform_school_offboarding_%')->pluck('after')->implode(' '));
    }

    public function test_every_step_refuses_the_wrong_role_and_writes_nothing(): void
    {
        $admin = $this->headOfB();
        $teacherB = User::query()->findOrFail($this->schoolB->ids['tara_user']);
        $paths = ['offboarding', 'offboarding/retry', 'offboarding/archive', 'offboarding/cancel', 'offboarding/dry-run', 'offboarding/deletion', 'offboarding/deletion/approval', 'offboarding/legal-hold'];
        $body = ['reason' => 'Should never be recorded here.', 'receipt_deadline' => '2026-11-20', 'delete_on' => '2026-12-31', 'retention_terms' => 'Should never be recorded here.'];
        $refused = function () use ($paths, $body): void {
            $this->get('/saas/schools/'.self::LEAVING.'/offboarding')->assertForbidden();
            foreach ($paths as $path) {
                $this->post('/saas/schools/'.self::LEAVING.'/'.$path, $body)->assertForbidden();
            }
            $this->delete('/saas/schools/'.self::LEAVING.'/offboarding/legal-hold')->assertForbidden();
        };
        foreach (['support', 'finance', 'content', 'sales'] as $role) {
            $this->actingAs($this->staff($role));
            $refused();
        }
        // A school's own Super Admin never reaches HQ.
        $this->actingAs($admin);
        $refused();
        $this->assertSame(0, SchoolOffboarding::acrossSchools()->count());
        $this->assertSame(0, DB::table('audit_log')->where('action', 'like', 'platform_school_offboarding_%')->count());

        // Operations runs steps 1 to 4 and asks for deletion, but cannot give the second approval or lift a hold.
        $operations = $this->staff('operations');
        $row = $this->archived($operations, $admin);
        $this->actingAs($operations)->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion')->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion/approval')->assertForbidden();
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/legal-hold', ['reason' => 'A records request from the department.'])->assertRedirect();
        $this->delete('/saas/schools/'.self::LEAVING.'/offboarding/legal-hold')->assertForbidden();
        $this->assertTrue($row->fresh()->legal_hold);
        $this->assertNull($row->fresh()->deletion_approved_at);

        // In the school: only its Super Admin sees the page, downloads or confirms; a teacher is refused.
        $this->actingAs($teacherB)->withHeader('X-School-Id', (string) self::LEAVING);
        $this->get('/school-exit')->assertForbidden();
        $this->post('/school-exit/'.$row->id.'/receipt', ['confirm' => '1'])->assertForbidden();
        $this->get(URL::temporarySignedRoute('school-exit.download', now()->addMinutes(5), ['offboarding' => $row->id, 'reader' => $teacherB->id]))->assertForbidden();
        $this->assertSame(1, $row->fresh()->download_count);
    }

    public function test_the_leaving_schools_offboarding_never_touches_the_other_schools_rows(): void
    {
        $before = $this->rowsOf(self::OTHER);
        $owner = $this->staff('owner');
        $row = $this->archived($this->staff('operations'), $this->headOfB());
        $this->actingAs($this->staff('operations', 'operations2'))->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion')->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($owner)->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion/approval')->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($row->fresh()->deletion_approved_at);

        $this->assertSame($before, $this->rowsOf(self::OTHER));
        // The export held no row of the other school, and the dry run counted none of its rows.
        $report = $row->fresh()->dry_run_report;
        $this->assertSame(DB::table('students')->where('school_id', self::LEAVING)->count(), $report['delete']['students']);
        $this->assertSame(DB::table('session_years')->where('school_id', self::LEAVING)->count(), $report['delete']['session_years']);
        // The other school has no offboarding, and its Super Admin sees none.
        $this->get('/saas/schools/'.self::OTHER.'/offboarding')->assertOk()->assertSee('Start an offboarding');
        $this->actingAs($this->superAdmin())->get('/school-exit')->assertOk()->assertSee('Your school is not leaving NavigateEd');
        // The leaving school's offboarding id is not found from the other school.
        $this->post('/school-exit/'.$row->id.'/receipt', ['confirm' => '1'])->assertNotFound();
    }

    public function test_the_download_link_is_refused_to_hq_staff_other_schools_and_after_expiry(): void
    {
        $operations = $this->staff('operations');
        $admin = $this->headOfB();
        $this->actingAs($operations);
        $row = $this->build($this->start());
        $url = $this->link($admin);

        // HQ staff, signed in to /saas: no school membership, so the panel signs them out; never a file.
        $this->actingAs($operations)->get($url)->assertRedirect(route('login'));
        // Platform staff who also hold a Super Admin role at the school: refused all the same.
        $insider = $this->headOfB('insider@baobab.example');
        PlatformStaffIdentity::query()->create(['user_id' => $insider->id]);
        $this->actingAs($insider)->get('/school-exit')->assertForbidden();
        $this->get(URL::temporarySignedRoute('school-exit.download', now()->addMinutes(5), ['offboarding' => $row->id, 'reader' => $insider->id]))->assertForbidden();
        // Another school's Super Admin with the forwarded link, or with the reader changed to themselves.
        $this->actingAs($this->superAdmin())->get($url)->assertForbidden();
        $this->get(str_replace('reader='.$admin->id, 'reader='.$this->superAdmin()->id, $url))->assertForbidden();
        $this->get(URL::temporarySignedRoute('school-exit.download', now()->addMinutes(5), ['offboarding' => $row->id, 'reader' => $this->superAdmin()->id]))->assertNotFound();
        // A second Super Admin of the school cannot use the first one's link.
        $this->actingAs($this->headOfB('deputy@baobab.example'))->get($url)->assertForbidden();
        $this->assertSame(0, $row->fresh()->download_count);

        // The link lasts minutes; the export itself lasts until the receipt deadline.
        $this->travel(16)->minutes();
        $this->actingAs($admin)->get($url)->assertForbidden();
        $this->get($this->link($admin))->assertOk();
        $this->travelTo(CarbonImmutable::parse('2026-11-21 08:00', PlatformOffboarding::TIMEZONE));
        $this->get(URL::temporarySignedRoute('school-exit.download', now()->addMinutes(5), ['offboarding' => $row->id, 'reader' => $admin->id]))->assertStatus(410);
        $this->assertNull($this->get('/school-exit')->assertOk()->viewData('offboarding')['download_url']);
        $this->assertSame(1, $row->fresh()->download_count);
        $this->assertSame(1, DB::table('audit_log')->where('action', 'platform_school_offboarding_export_downloaded')->count());

        // The deadline passed without confirmation: it counts as receipt.
        $this->artisan('offboarding:receipt-deadlines')->assertSuccessful();
        $this->assertSame([SchoolOffboarding::RECEIVED, 'deadline'], [$row->fresh()->state, $row->fresh()->receipt_basis]);
        $this->assertTrue(DB::table('audit_log')->where('action', 'platform_school_offboarding_receipt_deadline_passed')->exists());
    }

    public function test_the_deletion_dry_run_counts_rows_and_deletes_nothing(): void
    {
        $operations = $this->staff('operations');
        $row = $this->archived($operations, $this->headOfB());
        $tables = collect(Schema::getTableListing(DB::connection()->getDatabaseName(), false))
            ->reject(fn (string $table): bool => in_array($table, ['audit_log', 'school_offboardings'], true));
        $before = $tables->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
        $offboardings = DB::table('school_offboardings')->count();

        $this->actingAs($operations)->post('/saas/schools/'.self::LEAVING.'/offboarding/dry-run')->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($before, $tables->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all());
        $this->assertSame($offboardings, DB::table('school_offboardings')->count());
        $this->assertSame('archived', DB::table('schools')->where('id', self::LEAVING)->value('status'));
        $report = $row->fresh()->dry_run_report;
        $this->assertSame(0, $report['deleted']);
        $this->assertSame(DB::table('students')->where('school_id', self::LEAVING)->count(), $report['delete']['students']);
        $this->assertGreaterThan(0, $report['delete_total']);
        $this->assertArrayNotHasKey('school_contracts', $report['delete']);
        $this->assertArrayNotHasKey('audit_log', $report['delete']);
        $this->assertArrayHasKey('audit_log', $report['kept']);
        $this->get('/saas/schools/'.self::LEAVING.'/offboarding')->assertOk()->assertSee('Rows deleted: 0.')->assertSee('Rows that would be deleted, per table');
        $this->assertTrue(DB::table('audit_log')->where('action', 'platform_school_offboarding_dry_run')->exists());
    }

    public function test_deletion_needs_a_second_approval_from_a_different_owner_after_the_export_is_rechecked(): void
    {
        $first = $this->staff('owner');
        $second = $this->staff('owner', 'owner2');
        $row = $this->archived($this->staff('operations'), $this->headOfB());

        // Approval before anyone asked is refused.
        $this->actingAs($second)->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion/approval')->assertStatus(409);
        $this->actingAs($first)->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion')->assertRedirect()->assertSessionHasNoErrors();
        // The same person cannot give the second approval.
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion/approval')->assertSessionHasErrors('approval');
        $this->assertNull($row->fresh()->deletion_approved_at);

        // A legal hold voids the request and stops deletion until an Owner lifts it.
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/legal-hold', ['reason' => 'Litigation hold from the school governing body.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($row->fresh()->deletion_requested_at);
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion')->assertStatus(409);
        $this->actingAs($second)->delete('/saas/schools/'.self::LEAVING.'/offboarding/legal-hold')->assertRedirect()->assertSessionHasNoErrors();

        // The export copy must still be there and unchanged.
        $path = $row->fresh()->artifact_path;
        $stored = Storage::disk(OffboardingExport::DISK)->get($path);
        Storage::disk(OffboardingExport::DISK)->put($path, 'tampered');
        $this->actingAs($first)->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion')->assertStatus(409);
        Storage::disk(OffboardingExport::DISK)->put($path, $stored);

        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion')->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($second)->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion/approval')->assertRedirect()->assertSessionHasNoErrors();
        $fresh = $row->fresh();
        $this->assertSame([$first->id, $second->id], [(int) $fresh->deletion_requested_by, (int) $fresh->deletion_approved_by]);
        $this->assertSame(0, $fresh->dry_run_report['deleted']);
        $this->assertGreaterThan(0, DB::table('students')->where('school_id', self::LEAVING)->count());
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion/approval')->assertStatus(409);
        $approved = DB::table('audit_log')->where('action', 'platform_school_offboarding_deletion_approved')->sole();
        $this->assertSame(['requested_by' => $first->id, 'approved_by' => $second->id], array_intersect_key(json_decode($approved->after, true), ['requested_by' => 1, 'approved_by' => 1]));
    }

    public function test_starting_is_validated_and_each_step_waits_for_the_one_before(): void
    {
        $operations = $this->staff('operations');
        $this->actingAs($operations);
        // The default school can never be offboarded.
        $this->post('/saas/schools/'.self::OTHER.'/offboarding', ['reason' => 'Should never start for the default school.', 'receipt_deadline' => '2026-11-20', 'delete_on' => '2026-12-31', 'retention_terms' => 'Clause 12 of the agreement.'])
            ->assertSessionHasErrors('reason');
        // No Super Admin to receive the export.
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding', ['reason' => 'The school has moved to another provider.', 'receipt_deadline' => '2026-11-20', 'delete_on' => '2026-12-31', 'retention_terms' => 'Clause 12 of the agreement.'])
            ->assertSessionHasErrors('reason');
        $admin = $this->headOfB();
        // No contract term recorded: the date and terms must be entered; the deadline gives at least a week; deletion comes after it.
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding', ['reason' => 'The school has moved to another provider.', 'receipt_deadline' => '2026-11-06'])
            ->assertSessionHasErrors(['receipt_deadline']);
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding', ['reason' => 'The school has moved to another provider.', 'receipt_deadline' => '2026-11-20'])
            ->assertSessionHasErrors(['delete_on', 'retention_terms']);
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding', ['reason' => 'The school has moved to another provider.', 'receipt_deadline' => '2026-11-20', 'delete_on' => '2026-11-15', 'retention_terms' => 'Clause 12 of the agreement.'])
            ->assertSessionHasErrors(['delete_on']);
        // A deletion term needs the contract's end date.
        $this->post('/saas/schools/'.self::LEAVING.'/contracts', ['starts_on' => '2025-01-01', 'contracted_learners' => 300, 'price_per_learner' => '24.50', 'signatory' => 'Principal Baobab',
            'operator_agreement_version' => 'v2', 'operator_agreement_signed_on' => '2025-01-01', 'deletion_days_after_end' => 30])->assertSessionHasErrors('deletion_days_after_end');
        $this->assertSame(0, SchoolOffboarding::acrossSchools()->count());

        $row = $this->start();
        $this->assertSame(['entered', '2026-12-31'], [$row->retention_source, $row->delete_on->toDateString()]);
        // One offboarding at a time; nothing can be archived before the export is received.
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding', ['reason' => 'A second offboarding at once.', 'receipt_deadline' => '2026-11-20', 'delete_on' => '2026-12-31', 'retention_terms' => 'Clause 12 of the agreement.'])->assertStatus(409);
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/archive')->assertStatus(409);
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/dry-run')->assertStatus(409);
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/deletion')->assertStatus(409);
        $this->assertSame('active', DB::table('schools')->where('id', self::LEAVING)->value('status'));

        // A failed build can be prepared again.
        app(CurrentSchool::class)->run(self::LEAVING, fn () => app(OffboardingExport::class)->failed((int) $row->id, (string) $row->generation_id, 'generation_failed'));
        $this->get('/saas/schools/'.self::LEAVING.'/offboarding')->assertOk()->assertSee('generation_failed');
        $this->post('/saas/schools/'.self::LEAVING.'/offboarding/retry')->assertRedirect()->assertSessionHasNoErrors();
        $row = $this->build($row->fresh());
        $this->assertSame(SchoolOffboarding::READY, $row->state);

        // Receipt needs a download first.
        $this->actingAs($admin)->post('/school-exit/'.$row->id.'/receipt', ['confirm' => '1'])->assertSessionHasErrors('confirm');
        $this->assertSame(SchoolOffboarding::READY, $row->fresh()->state);

        // Cancelling discards the export copy; the school stays active and may start again.
        $this->actingAs($operations)->post('/saas/schools/'.self::LEAVING.'/offboarding/cancel', ['reason' => 'The school renewed its contract.'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(SchoolOffboarding::CANCELLED, $row->fresh()->state);
        $this->assertSame([], Storage::disk(OffboardingExport::DISK)->allFiles('schools/2/offboarding'));
        $this->actingAs($admin)->get('/school-exit')->assertOk()->assertSee('Your school is not leaving NavigateEd');
    }
}
