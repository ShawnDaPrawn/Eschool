<?php

declare(strict_types=1);

namespace Tests\Feature\Activities;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMaterial;
use App\Models\Activities\ActivityMaterialFile;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\ActivityFiles;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class ActivityFilesTest extends TestCase
{
    use UsesTwoSchools;

    private ActivityGroup $group;

    private ActivityMaterial $material;

    private User $teacher;

    private User $parent;

    private ActivityFileScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo('2026-02-10 12:00:00 UTC');
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        config(['modules.groups' => ['always_on' => true]]);
        Storage::fake(ActivityFiles::DISK);
        $this->scanner = new ActivityFileScanner;
        $this->app->instance(UploadScanner::class, $this->scanner);
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->group = ActivityGroup::create(['session_year_id' => $year, 'name' => 'Synthetic files', 'discipline' => 'Culture', 'season' => '2026',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->teacher->id, 'role' => 'facilitator', 'cascade' => false,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic allocation']);
        $learner = $this->schoolA->ids['asha'];
        ActivityMembership::create(['activity_group_id' => $this->group->id, 'learner_id' => $learner, 'role' => 'member',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic membership']);
        Enrolment::create(['learner_id' => $learner, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'],
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        GuardianLink::updateOrCreate(['learner_id' => $learner, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->parent, Students::findOrFail($learner), 'approve', config('identity.consent_version'));
        $this->material = ActivityMaterial::create(['activity_group_id' => $this->group->id, 'author_id' => $this->teacher->id,
            'title' => 'Synthetic material', 'markdown' => 'Private instructions', 'published' => true, 'revision' => 1]);
    }

    private function upload(?array $input = null, ?UploadedFile $file = null): array
    {
        return app(ActivityFiles::class)->upload($this->teacher, (int) $this->group->id, (int) $this->material->id,
            $input ?? ['operation_id' => (string) Str::uuid(), 'material_revision' => $this->material->fresh()->revision],
            $file ?? UploadedFile::fake()->createWithContent('Synthetic private worksheet.pdf', "%PDF-1.4\nSynthetic private worksheet"));
    }

    private function read(User $actor, string $id): array
    {
        return app(ActivityFiles::class)->read($actor, (int) $this->group->id, (int) $this->material->id, $id);
    }

    private function refusal(callable $action, array $statuses = [403, 404]): void
    {
        try {
            $action();
            $this->fail('An unauthorized private document operation was allowed.');
        } catch (ModelNotFoundException) {
            $this->assertContains(404, $statuses);
        } catch (HttpExceptionInterface $error) {
            $this->assertContains($error->getStatusCode(), $statuses);
        }
    }

    public function test_scanned_ciphertext_and_metadata_are_private_and_exact_retry_is_single_effect(): void
    {
        $intent = ['operation_id' => (string) Str::uuid(), 'material_revision' => 1];
        $one = $this->upload($intent);
        $this->assertSame($one, $this->upload($intent));
        $this->assertSame(1, ActivityMaterialFile::where('material_id', $this->material->id)->count());
        $this->assertSame(2, $this->material->fresh()->revision);
        $file = ActivityMaterialFile::findOrFail($one['id']);
        $this->assertNotSame($file->name, DB::table('activity_material_files')->where('id', $file->id)->value('name'));
        $cipher = Storage::disk(ActivityFiles::DISK)->get($file->path);
        $this->assertStringNotContainsString('Synthetic private worksheet', $cipher);
        $this->assertStringContainsString('Synthetic private worksheet', $this->read($this->parent, $one['id'])['bytes']);
        $list = app(ActivityFiles::class)->listing($this->parent, (int) $this->group->id, (int) $this->material->id);
        $this->assertCount(1, $list['items']);
        $this->assertArrayNotHasKey('path', $list['items'][0]);
        $this->assertArrayNotHasKey('url', $list['items'][0]);
        $this->assertGreaterThanOrEqual(4, $this->scanner->scans);
        $this->assertStringNotContainsString('Synthetic private worksheet', json_encode(OutboxEvent::where('type', 'activity.group_changed')->latest('id')->value('payload'), JSON_THROW_ON_ERROR));
        $this->refusal(fn () => $this->upload($intent, UploadedFile::fake()->createWithContent('different.pdf', "%PDF-1.4\nDifferent")), [409]);
    }

    public function test_images_are_sanitized_to_pixels_and_scanned_after_conversion(): void
    {
        $one = $this->upload(null, UploadedFile::fake()->image('synthetic.png', 40, 30));
        $this->assertSame('image/jpeg', $one['mime']);
        $this->assertSame('synthetic.jpg', $one['name']);
        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($this->read($this->parent, $one['id'])['bytes'])[2]);
        $this->assertSame(2, $this->scanner->scans);
    }

    public function test_family_cannot_read_drafts_and_wrong_owner_role_group_or_school_cannot_upload_or_read(): void
    {
        $one = $this->upload();
        $otherTeacher = User::findOrFail($this->schoolA->ids['gina_user']);
        foreach ([$this->parent, User::findOrFail($this->schoolA->ids['asha_user']), $otherTeacher] as $actor) {
            $this->refusal(fn () => app(ActivityFiles::class)->upload($actor, (int) $this->group->id, (int) $this->material->id,
                ['operation_id' => (string) Str::uuid(), 'material_revision' => 2], UploadedFile::fake()->createWithContent('private.pdf', '%PDF-1.4')));
        }
        $this->material->update(['published' => false]);
        $this->refusal(fn () => $this->read($this->parent, $one['id']));
        $this->assertNotEmpty($this->read($this->teacher, $one['id'])['bytes']);
        app(CurrentSchool::class)->run(2, fn () => $this->refusal(fn () => $this->read(User::findOrFail($this->schoolB->ids['farid_user']), $one['id'])));
    }

    public function test_retained_enrolment_cannot_override_ended_student_membership_or_changed_parent_role(): void
    {
        $one = $this->upload();
        $learnerUser = $this->schoolA->ids['asha_user'];
        SchoolMembership::where('school_id', 1)->where('user_id', $learnerUser)->where('role', 'Student')->update(['ends_on' => '2026-02-09']);
        $this->refusal(fn () => $this->read($this->parent, $one['id']));
        SchoolMembership::where('school_id', 1)->where('user_id', $learnerUser)->where('role', 'Student')->update(['ends_on' => null]);
        $this->parent->syncRoles(['Facilitator']);
        $this->refusal(fn () => $this->read($this->parent, $one['id']));
    }

    public function test_refused_or_unavailable_scanner_and_stale_revision_leave_no_new_file(): void
    {
        $this->scanner->clean = false;
        $this->refusal(fn () => $this->upload(), [422]);
        $this->scanner->clean = true;
        $this->scanner->unavailable = true;
        $this->refusal(fn () => $this->upload(), [503]);
        $this->scanner->unavailable = false;
        $this->refusal(fn () => $this->upload(['operation_id' => (string) Str::uuid(), 'material_revision' => 9]), [409]);
        $this->assertSame(0, ActivityMaterialFile::count());
        $this->assertSame([], Storage::disk(ActivityFiles::DISK)->allFiles());
    }

    public function test_wrong_material_and_corrupt_ciphertext_never_expand_file_scope(): void
    {
        $one = $this->upload();
        $other = ActivityMaterial::create(['activity_group_id' => $this->group->id, 'author_id' => $this->teacher->id,
            'title' => 'Other', 'markdown' => '', 'published' => true, 'revision' => 1]);
        $file = ActivityMaterialFile::findOrFail($one['id']);
        Storage::disk(ActivityFiles::DISK)->put($file->path, 'invalid ciphertext');
        $this->refusal(fn () => app(ActivityFiles::class)->read($this->parent, (int) $this->group->id, (int) $other->id, $one['id']));
        Storage::disk(ActivityFiles::DISK)->put($file->path, Crypt::encryptString('tampered'));
        $this->refusal(fn () => $this->read($this->parent, $one['id']), [503]);
    }

    public function test_final_allocation_withdrawal_compensates_ciphertext_and_transaction_receipts(): void
    {
        $event = 'eloquent.created: '.ActivityMaterialFile::class;
        Event::listen($event, fn () => ActivityStaff::where('user_id', $this->teacher->id)->update(['active' => false]));
        try {
            $this->refusal(fn () => $this->upload());
            $this->assertSame(0, ActivityMaterialFile::count());
            $this->assertSame([], Storage::disk(ActivityFiles::DISK)->allFiles());
            $this->assertSame(1, $this->material->fresh()->revision);
        } finally {
            Event::forget($event);
        }
    }

    public function test_removal_is_revisioned_and_permitted_in_readonly_mode(): void
    {
        $one = $this->upload();
        config(['modules.groups' => ['default' => true, 'legacy' => true]]);
        app(ModuleGate::class)->forget();
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'groups'], ['enabled' => true, 'read_only' => true, 'in_package' => true]);
        app(ModuleGate::class)->forget();
        $this->refusal(fn () => $this->upload(), [403]);
        $intent = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'material_revision' => 2];
        $result = app(ActivityFiles::class)->remove($this->teacher, (int) $this->group->id, (int) $this->material->id, $one['id'], $intent);
        $this->assertTrue($result['removed']);
        $this->assertSame(3, $result['material_revision']);
        $this->assertSame($result, app(ActivityFiles::class)->remove($this->teacher, (int) $this->group->id, (int) $this->material->id, $one['id'], $intent));
        $this->refusal(fn () => $this->read($this->parent, $one['id']));
    }

    public function test_outer_rollback_ciphertext_has_no_reachable_reference_and_cleanup_preserves_fresh_other_school_files(): void
    {
        DB::beginTransaction();
        $one = $this->upload();
        $path = ActivityMaterialFile::findOrFail($one['id'])->path;
        DB::rollBack();
        $this->assertNull(ActivityMaterialFile::find($one['id']));
        $this->refusal(fn () => $this->read($this->parent, $one['id']));
        $disk = Storage::disk(ActivityFiles::DISK);
        touch($disk->path($path), now()->utc()->subDays(2)->timestamp);
        $fresh = dirname($path).'/fresh.enc';
        $disk->put($fresh, 'synthetic ciphertext');
        $other = app(CurrentSchool::class)->run(2, fn () => app(CurrentSchool::class)->storagePath('activity-materials/1').'/other.enc');
        $disk->put($other, 'other school');
        touch($disk->path($other), now()->utc()->subDays(2)->timestamp);
        $this->assertSame(1, app(ActivityFiles::class)->pruneOrphans());
        $disk->assertMissing($path);
        $disk->assertExists($fresh);
        $disk->assertExists($other);
    }

    public function test_retained_attachment_metadata_blocks_destructive_migration_rollback(): void
    {
        $this->upload();
        $migration = require database_path('migrations/2026_10_26_003000_create_activity_material_files.php');
        $this->expectException(RuntimeException::class);
        $migration->down();
    }

    public function test_another_allocated_facilitator_can_read_but_cannot_replace_the_authors_attachments(): void
    {
        $one = $this->upload();
        $coach = User::findOrFail($this->schoolA->ids['gina_user']);
        $coach->syncRoles(['Facilitator']);
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $coach->id, 'role' => 'facilitator', 'cascade' => false,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Second synthetic coach']);
        $this->assertNotEmpty($this->read($coach, $one['id'])['bytes']);
        $this->refusal(fn () => app(ActivityFiles::class)->upload($coach, (int) $this->group->id, (int) $this->material->id,
            ['operation_id' => (string) Str::uuid(), 'material_revision' => 2], UploadedFile::fake()->createWithContent('synthetic.pdf', '%PDF-1.4')));
        $this->refusal(fn () => app(ActivityFiles::class)->remove($coach, (int) $this->group->id, (int) $this->material->id, $one['id'],
            ['operation_id' => (string) Str::uuid(), 'material_revision' => 2, 'revision' => 1]));
    }

    public function test_attachment_limit_does_not_allow_an_eleventh_object_or_revision_change(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->upload();
        }
        $this->refusal(fn () => $this->upload(), [422]);
        $this->assertSame(10, ActivityMaterialFile::where('material_id', $this->material->id)->count());
        $this->assertSame(11, $this->material->fresh()->revision);
        $this->assertCount(10, Storage::disk(ActivityFiles::DISK)->allFiles());
    }

    public function test_oversized_or_unsupported_sources_are_refused_before_scanning(): void
    {
        foreach ([UploadedFile::fake()->createWithContent('oversized.pdf', '%PDF-1.4'.str_repeat('x', 5 * 1024 * 1024)),
            UploadedFile::fake()->createWithContent('unsupported.txt', 'Synthetic plain text')] as $file) {
            try {
                $this->upload(null, $file);
                $this->fail('An unsupported document passed validation.');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('document', $error->errors());
            }
        }
        $this->assertSame(0, $this->scanner->scans);
        $this->assertSame([], Storage::disk(ActivityFiles::DISK)->allFiles());
    }

    public function test_removal_erases_ciphertext_only_after_the_real_outer_commit(): void
    {
        $one = $this->upload();
        $path = ActivityMaterialFile::findOrFail($one['id'])->path;
        app(ActivityFiles::class)->remove($this->teacher, (int) $this->group->id, (int) $this->material->id, $one['id'],
            ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'material_revision' => 2]);
        Storage::disk(ActivityFiles::DISK)->assertExists($path);
        // End this synthetic fixture's outer transaction, proving the callback
        // does not run at an inner savepoint; restore the teardown transaction.
        DB::commit();
        try {
            Storage::disk(ActivityFiles::DISK)->assertMissing($path);
        } finally {
            DB::beginTransaction();
        }
    }

    public function test_password_change_refusal_precedes_private_ciphertext_and_upload_scanning(): void
    {
        $one = $this->upload();
        $file = ActivityMaterialFile::findOrFail($one['id']);
        Storage::disk(ActivityFiles::DISK)->put($file->path, 'must not be decrypted');
        $this->teacher->forceFill(['password_change_required' => true])->save();
        $this->refusal(fn () => $this->read($this->teacher, $one['id']));
        $scans = $this->scanner->scans;
        $this->refusal(fn () => $this->upload());
        $this->assertSame($scans, $this->scanner->scans);
        $this->parent->forceFill(['password_change_required' => true])->save();
        $this->refusal(fn () => $this->read($this->parent, $one['id']));
    }
}

class ActivityFileScanner implements UploadScanner
{
    public bool $clean = true;

    public bool $unavailable = false;

    public int $scans = 0;

    public function scan(string $bytes): array
    {
        $this->scans++;
        if ($this->unavailable) {
            throw new RuntimeException('Synthetic scanner unavailable');
        }

        return ['clean' => $this->clean, 'engine' => 'synthetic-qualified-protocol-only'];
    }
}
