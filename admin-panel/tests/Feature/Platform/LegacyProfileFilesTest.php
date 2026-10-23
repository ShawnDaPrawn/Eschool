<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\FormField;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFile;
use App\Models\User;
use App\Services\People\Profiles\LegacyProfileBackfill;
use App\Services\People\Profiles\LegacyProfileFiles;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\People\Profiles\ProfileForms;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class LegacyProfileFilesTest extends TestCase
{
    use UsesTwoSchools;

    private const PDF = "%PDF-1.4\nPRIVATE LEGACY FILE\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Storage::fake('public');
        Storage::fake(ProfileFiles::DISK);
    }

    private function source(string $path, string $profile = 'learner', int $schoolId = 1): FormField
    {
        return app(CurrentSchool::class)->run($schoolId, function () use ($path, $profile, $schoolId) {
            [$table, $for, $who] = ['learner' => ['students', 1, 'asha'], 'parent' => ['parents', 2, 'farid'],
                'teacher' => ['teachers', 3, 'tara_teacher']][$profile];
            $school = $schoolId === 1 ? $this->schoolA : $this->schoolB;
            DB::table($table)->where('id', $school->ids[$who])->update(['dynamic_fields' => json_encode([['Document' => $path]])]);

            return FormField::query()->forceCreate(['name' => 'Document', 'type' => 'file', 'for' => $for, 'rank' => 1, 'is_required' => 0]);
        });
    }

    private function batch(bool $apply = true, string $profile = 'learner', int $schoolId = 1, bool $files = true): array
    {
        return app(LegacyProfileBackfill::class)->run($schoolId, $profile, apply: $apply, files: $files);
    }

    public function test_opt_in_dry_run_reads_valid_files_without_any_writes(): void
    {
        $this->source('students/PRIVATE.pdf');
        Storage::disk('public')->put('students/PRIVATE.pdf', self::PDF);
        $this->assertSame('private_file_migration_pending', $this->batch(files: false)['issues'][0]['code']);
        // The first apply creates metadata only; file handling remains opt in.
        $before = DB::table('audit_log')->count();
        $report = $this->batch(false);
        $this->assertSame(1, $report['files_added']);
        $this->assertSame(1, $report['values_added']);
        $this->assertSame(0, $report['issue_count']);
        $this->assertSame(0, DB::table('profile_files')->count());
        $this->assertSame(0, DB::table('profile_field_values')->count());
        $this->assertSame([], Storage::disk(ProfileFiles::DISK)->allFiles());
        $this->assertSame($before, DB::table('audit_log')->count());
        Storage::disk('public')->assertExists('students/PRIVATE.pdf');
        $this->assertStringNotContainsString('PRIVATE', json_encode($report));
    }

    public function test_apply_copies_encrypted_bytes_and_names_while_preserving_public_source_and_private_authority(): void
    {
        $old = $this->source('schools/1/students/PRIVATE.pdf');
        Storage::disk('public')->put('schools/1/students/PRIVATE.pdf', self::PDF);
        $this->assertSame(1, $this->batch()['files_added']);
        $field = ProfileFieldDefinition::query()->where('legacy_form_field_id', $old->id)->firstOrFail();
        $file = ProfileFile::query()->sole();
        $this->assertSame('PRIVATE.pdf', $file->name);
        $this->assertStringNotContainsString('PRIVATE', DB::table('profile_files')->value('name'));
        $this->assertStringNotContainsString('PRIVATE', Storage::disk(ProfileFiles::DISK)->get($file->path));
        $this->assertStringNotContainsString('PRIVATE', DB::table('audit_log')->get()->toJson());
        Storage::disk('public')->assertExists('schools/1/students/PRIVATE.pdf');
        $token = $file->id.'.pdf';
        $person = $this->schoolA->ids['asha_user'];
        $this->actingAs($this->superAdmin())->getJson('/protected/profile-files/people/'.$person.'/'.$token)->assertNotFound();
        $field->update(['hidden' => false]);
        $this->get('/protected/profile-files/people/'.$person.'/'.$token, ['Accept' => 'application/json'])->assertOk()->assertContent(self::PDF);
        $this->actingAs(User::findOrFail($this->schoolA->ids['gina_user']))->getJson('/protected/profile-files/people/'.$person.'/'.$token)->assertNotFound();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->getJson('/protected/profile-files/people/'.$person.'/'.$token)->assertNotFound();
        $this->actingAs($this->superAdmin())->withHeader('X-School-Id', '2')->getJson('/protected/profile-files/people/'.$person.'/'.$token)->assertForbidden();
    }

    public function test_each_profile_folder_and_school_prefix_must_match_the_selected_subject(): void
    {
        foreach (['learner' => 'students', 'parent' => 'parents', 'teacher' => 'teachers'] as $profile => $folder) {
            $this->source('schools/2/'.$folder.'/PRIVATE.pdf', $profile, 2);
            Storage::disk('public')->put('schools/2/'.$folder.'/PRIVATE.pdf', self::PDF);
            $report = $this->batch(profile: $profile, schoolId: 2);
            $this->assertSame(0, $report['issue_count']);
            $this->assertSame(1, $report['files_added']);
        }
        $this->assertSame(3, DB::table('profile_files')->where('school_id', 2)->count());
        $this->assertSame(0, DB::table('profile_files')->where('school_id', 1)->count());
        app(CurrentSchool::class)->run(2, function () {
            $this->assertSame('legacy_file_school_mismatch', app(LegacyProfileFiles::class)->prepare('students/PRIVATE.pdf', 'learner'));
            $this->assertSame('legacy_file_school_mismatch', app(LegacyProfileFiles::class)->prepare('schools/1/students/PRIVATE.pdf', 'learner'));
            $this->assertSame('legacy_file_school_mismatch', app(LegacyProfileFiles::class)->prepare('schools/2/teachers/PRIVATE.pdf', 'learner'));
        });
    }

    public function test_actual_parent_documents_in_the_legacy_students_folder_require_a_verified_owner(): void
    {
        $this->source('students/PRIVATE_parent.pdf', 'parent');
        Storage::disk('public')->put('students/PRIVATE_parent.pdf', self::PDF);
        $service = app(LegacyProfileFiles::class);
        $this->assertSame('legacy_file_school_mismatch', $service->prepare('students/PRIVATE_parent.pdf', 'parent'));
        $this->assertSame('legacy_file_owner_ambiguous', $service->prepare('students/PRIVATE_parent.pdf', 'parent', $this->schoolA->ids['gina_user']));
        $report = $this->batch(profile: 'parent');
        $this->assertSame(0, $report['issue_count']);
        $this->assertSame(1, $report['files_added']);
        $this->assertSame($this->schoolA->ids['farid_user'], (int) ProfileFile::query()->sole()->user_id);
        Storage::disk('public')->assertExists('students/PRIVATE_parent.pdf');
        $this->source('schools/2/students/PRIVATE_parent.pdf', 'parent', 2);
        Storage::disk('public')->put('schools/2/students/PRIVATE_parent.pdf', self::PDF);
        $report = $this->batch(profile: 'parent', schoolId: 2);
        $this->assertSame(0, $report['issue_count']);
        $this->assertSame(1, $report['files_added']);
    }

    public function test_references_claimed_by_another_person_school_or_archived_record_are_not_reassigned(): void
    {
        $path = 'schools/1/students/PRIVATE_shared.pdf';
        $this->source($path, 'parent');
        Storage::disk('public')->put($path, self::PDF);
        foreach ([$this->schoolA->ids['ben'], $this->schoolB->ids['ben']] as $otherStudent) {
            DB::table('students')->where('id', $otherStudent)->update(['dynamic_fields' => json_encode([['Other_file' => $path]]), 'deleted_at' => now()]);
            $report = $this->batch(profile: 'parent');
            $this->assertSame('legacy_file_owner_ambiguous', $report['issues'][0]['code']);
            $this->assertSame(0, $report['files_added']);
            DB::table('students')->where('id', $otherStudent)->update(['dynamic_fields' => '[]']);
        }
        $this->assertSame(0, ProfileFile::query()->count());
        $this->assertSame([], Storage::disk(ProfileFiles::DISK)->allFiles());
    }

    public function test_urls_traversal_encoded_paths_and_missing_objects_are_refused_without_private_reports(): void
    {
        $service = app(LegacyProfileFiles::class);
        foreach (['https://example.test/PRIVATE.pdf', '/students/PRIVATE.pdf', '../PRIVATE.pdf', 'students/../PRIVATE.pdf',
            'students//PRIVATE.pdf', 'students/./PRIVATE.pdf', 'students/%2e%2e/PRIVATE.pdf', 'students/PRIVATE.pdf?token=x',
            "students/PRIVATE\0.pdf", 'students\\PRIVATE.pdf'] as $path) {
            $this->assertSame('invalid_legacy_file_path', $service->prepare($path, 'learner'), $path);
        }
        $this->assertSame('legacy_file_unavailable', $service->prepare('students/PRIVATE.pdf', 'learner'));
        $this->source('schools/2/students/PRIVATE.pdf');
        $report = $this->batch();
        $this->assertSame('legacy_file_school_mismatch', $report['issues'][0]['code']);
        $this->assertStringNotContainsString('PRIVATE', json_encode($report));
        $this->assertSame([], Storage::disk(ProfileFiles::DISK)->allFiles());
    }

    public function test_symlinks_in_file_or_directory_components_are_refused(): void
    {
        $disk = Storage::disk('public');
        $disk->put('students/PRIVATE.pdf', self::PDF);
        symlink($disk->path('students/PRIVATE.pdf'), $disk->path('students/link.pdf'));
        symlink($disk->path('students'), $disk->path('students/linked'));
        try {
            $this->assertSame('legacy_file_symlink', app(LegacyProfileFiles::class)->prepare('students/link.pdf', 'learner'));
            $this->assertSame('legacy_file_symlink', app(LegacyProfileFiles::class)->prepare('students/linked/PRIVATE.pdf', 'learner'));
        } finally {
            unlink($disk->path('students/link.pdf'));
            unlink($disk->path('students/linked'));
        }
    }

    public function test_mime_extension_size_and_disk_checks_match_the_private_file_boundary(): void
    {
        $service = app(LegacyProfileFiles::class);
        foreach (['empty.pdf' => '', 'big.pdf' => str_repeat('x', ProfileFiles::MAX_KB * 1024 + 1)] as $name => $bytes) {
            Storage::disk('public')->put('students/'.$name, $bytes);
            $this->assertSame('legacy_file_size_invalid', $service->prepare('students/'.$name, 'learner'));
        }
        Storage::disk('public')->put('students/false.pdf', '<html>PRIVATE</html>');
        Storage::disk('public')->put('students/false.png', self::PDF);
        $this->assertSame('legacy_file_type_invalid', $service->prepare('students/false.pdf', 'learner'));
        $this->assertSame('legacy_file_type_invalid', $service->prepare('students/false.png', 'learner'));
        config(['filesystems.disks.public.driver' => 's3']);
        $this->assertSame('legacy_file_disk_unsupported', $service->prepare('students/false.pdf', 'learner'));
    }

    public function test_png_and_jpeg_content_is_validated_and_copied_without_conversion(): void
    {
        foreach (['png' => 'image/png', 'jpeg' => 'image/jpeg'] as $extension => $mime) {
            $bytes = UploadedFile::fake()->image('photo.'.$extension, 2, 2)->getContent();
            $path = 'students/PRIVATE.'.$extension;
            Storage::disk('public')->put($path, $bytes);
            $prepared = app(LegacyProfileFiles::class)->prepare($path, 'learner');
            $this->assertIsArray($prepared);
            $this->assertSame($bytes, $prepared['bytes']);
            $this->assertSame($mime, $prepared['mime']);
            $this->assertSame($extension === 'jpeg' ? 'jpg' : 'png', $prepared['extension']);
        }
    }

    public function test_retries_and_explicit_clears_never_create_extra_ciphertext(): void
    {
        $old = $this->source('students/PRIVATE.pdf');
        Storage::disk('public')->put('students/PRIVATE.pdf', self::PDF);
        $this->batch();
        $paths = Storage::disk(ProfileFiles::DISK)->allFiles();
        $this->assertSame(0, $this->batch()['files_added']);
        $field = ProfileFieldDefinition::query()->where('legacy_form_field_id', $old->id)->firstOrFail();
        $field->update(['hidden' => false]);
        app(ProfileForms::class)->save($this->superAdmin(), User::findOrFail($this->schoolA->ids['asha_user']), 'learner', [$field->key => null]);
        $this->assertSame(1, $this->batch()['preserved']);
        $this->assertSame($paths, Storage::disk(ProfileFiles::DISK)->allFiles());
        $this->assertSame(1, DB::table('profile_files')->count());
    }

    public function test_failed_record_transaction_removes_new_ciphertext_and_keeps_the_public_source(): void
    {
        $this->source('students/PRIVATE.pdf');
        Storage::disk('public')->put('students/PRIVATE.pdf', self::PDF);
        DB::table('students')->where('id', $this->schoolA->ids['asha'])->update(['religion' => 'PRIVATE RELIGION']);
        $this->mock(ProfileForms::class, function (MockInterface $mock) {
            $mock->shouldReceive('validateValue')->once()->andThrow(new RuntimeException('Synthetic failure after private copy'));
        });
        try {
            $this->batch();
            $this->fail('Expected the synthetic failure.');
        } catch (RuntimeException $error) {
            $this->assertSame('Synthetic failure after private copy', $error->getMessage());
        }
        $this->assertSame([], Storage::disk(ProfileFiles::DISK)->allFiles());
        $this->assertSame(0, DB::table('profile_files')->count());
        $this->assertSame(0, DB::table('profile_field_values')->count());
        Storage::disk('public')->assertExists('students/PRIVATE.pdf');
    }

    public function test_console_files_option_is_explicit_and_dry_run_remains_the_default(): void
    {
        $this->source('students/PRIVATE.pdf');
        Storage::disk('public')->put('students/PRIVATE.pdf', self::PDF);
        $this->assertSame(0, Artisan::call('profiles:backfill-legacy', ['--school' => 1, '--profile' => 'learner', '--files' => true]));
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('dry_run', $report['mode']);
        $this->assertSame(1, $report['files_added']);
        $this->assertSame(0, DB::table('profile_files')->count());
    }
}
