<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Support\SignInFixtures;
use Tests\TestCase;

/**
 * The upload guards: a file with two extensions, a type that does not match
 * or a name that climbs out of its folder is refused; the sanitiser names
 * every stored file; UploadService stores under
 * the school's folder.
 */
class UploadsTest extends TestCase
{
    use RefreshDatabase;
    use SignInFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Stores what it is sent the way UploadService does, through the real api group.
        Route::middleware('api')->post('api/_store-test', fn (Request $request) => response()->json([
            'path' => UploadService::upload($request->file('file'), 'tests'),
        ]));
    }

    public function test_a_double_extension_is_refused(): void
    {
        foreach (['photo.php.jpg', 'report.pdf.exe', 'cv.phtml.pdf', 'notes.html.txt'] as $name) {
            $this->post('api/_store-test', ['file' => UploadedFile::fake()->create($name, 1)])
                ->assertStatus(422)
                ->assertJson(['error' => true, 'code' => 102]);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_file_whose_content_is_not_what_its_name_says_is_refused(): void
    {
        $pdf = "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n";
        $png = UploadedFile::fake()->image('real.png', 4, 4);

        foreach ([
            $this->realUpload('photo.jpg', $pdf),
            $this->realUpload('scan.pdf', (string) file_get_contents($png->getRealPath())),
            $this->realUpload('avatar.png', 'just some text'),
            UploadedFile::fake()->create('selfie.jpeg', 1, 'application/pdf'),
        ] as $upload) {
            $this->post('api/_store-test', ['file' => $upload])
                ->assertStatus(422)
                ->assertJson(['error' => true, 'message' => trans('The file content does not match its extension.')]);
        }

        // The right content under its own name is fine.
        $this->post('api/_store-test', ['file' => $this->realUpload('scan.pdf', $pdf)])->assertOk();
        $this->post('api/_store-test', ['file' => UploadedFile::fake()->image('photo.jpg', 4, 4)])->assertOk();
    }

    public function test_a_traversal_name_is_refused_or_kept_inside_its_folder(): void
    {
        foreach (['..', '.', "evil\x01.png"] as $name) {
            $this->post('api/_store-test', ['file' => $this->realUpload($name, 'x')])
                ->assertStatus(422)
                ->assertJson(['error' => true]);
        }

        // PHP and Symfony keep only the last path segment; the rest can't climb out.
        $path = $this->post('api/_store-test', ['file' => UploadedFile::fake()->image('../../../../avatar.png', 4, 4)])
            ->assertOk()
            ->json('path');
        $this->assertStringStartsWith('schools/1/tests/', $path);
        $this->assertStringNotContainsString('..', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_upload_service_stores_under_the_schools_folder(): void
    {
        $this->assertSame('schools/1/lessons', app(CurrentSchool::class)->storagePath('lessons/'));

        $pdf = UploadService::upload(UploadedFile::fake()->create('Term 1 plan.pdf', 3, 'application/pdf'), 'lessons');
        $photo = UploadService::upload(UploadedFile::fake()->image('class photo.png', 10, 10), 'gallery');

        $this->assertMatchesRegularExpression('#^schools/1/lessons/[A-Za-z0-9]{40}\.pdf$#', $pdf);
        // Pictures are re-encoded as JPEG and named so.
        $this->assertMatchesRegularExpression('#^schools/1/gallery/[A-Za-z0-9]{40}\.jpg$#', $photo);
        Storage::disk('public')->assertExists([$pdf, $photo]);
    }

    public function test_paths_stored_before_stay_readable_and_deletable(): void
    {
        Storage::disk('public')->put('super-admin/logo/old.png', 'old');
        Storage::disk('public')->put('lessons/1700000000-worksheet.pdf', 'old');

        $this->assertSame('/storage/super-admin/logo/old.png', parse_url(Storage::disk('public')->url('super-admin/logo/old.png'), PHP_URL_PATH));
        $this->assertTrue(UploadService::delete('super-admin/logo/old.png'));
        $this->assertTrue(UploadService::delete('lessons/1700000000-worksheet.pdf'));
        Storage::disk('public')->assertMissing(['super-admin/logo/old.png', 'lessons/1700000000-worksheet.pdf']);
    }

    public function test_stored_names_come_from_the_sanitiser(): void
    {
        $this->seedLikeInstaller();
        $this->buildSignInSchool();
        DB::table('leave_masters')->insert(['total_leave' => '2', 'holiday_days' => '0', 'session_year_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $token = $this->person('teacher')->createToken('teacher-app')->plainTextToken;

        $this->withToken($token)->post('/api/teacher/apply-leave', [
            'reason' => 'Doctor',
            'leave_details' => [['date' => now()->addDay()->toDateString(), 'type' => 'Full']],
            'files' => [UploadedFile::fake()->create("Doctor's note #1 <b>.pdf", 2, 'application/pdf')],
        ])->assertOk()->assertJson(['error' => false]);

        $this->assertSame('Doctor_s note _1 _b_.pdf', DB::table('files')->where('modal_type', 'App\Models\Leave')->value('file_name'));
    }

    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }
}
