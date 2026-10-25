<?php

declare(strict_types=1);

namespace Tests\Feature\Wellbeing;

use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Models\Wellbeing\Category;
use App\Models\Wellbeing\Event;
use App\Models\Wellbeing\IncidentPhoto;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\TeamAssignment;
use App\Services\ParentPolicyService;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\IncidentPhotos;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\WellbeingSettings;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class PhotoScanner implements UploadScanner
{
    public array $scanned = [];

    public bool $reject = false;

    public bool $offline = false;

    public ?Closure $effect = null;

    public function scan(string $bytes): array
    {
        $this->scanned[] = $bytes;
        if ($this->effect !== null) {
            ($this->effect)();
        }
        if ($this->offline) {
            throw new RuntimeException('Synthetic scanner offline');
        }

        return ['clean' => ! $this->reject, 'engine' => 'synthetic-scanner', 'signature' => 'synthetic-only'];
    }
}

class IncidentPhotosTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private PhotoScanner $scanner;

    private string $secret;

    private User $professional;

    private array $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(ModuleGate::class)->change('wellbeing', true, $this->superAdmin());
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic photo enrolment']);
        $this->professional = User::create(['first_name' => 'Synthetic', 'last_name' => 'Counsellor', 'email' => 'photo-counsellor@example.test', 'password' => 'synthetic', 'password_change_required' => false, 'status' => 1]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $this->professional->id, 'role' => 'Counsellor', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $this->professional->assignRole('Counsellor');
        $settings = app(WellbeingSettings::class);
        $this->category = $settings->saveCategory($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Safety concern', 'emergency_guidance' => 'Seek a trusted adult.', 'active' => true]);
        $settings->assignTeam($this->superAdmin(), $this->category['id'], null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'user_id' => $this->professional->id, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic photo triage']);
        $this->secret = bin2hex(random_bytes(32));
        app(IncidentReporting::class)->submit(['secret' => $this->secret, 'category_id' => $this->category['id'], 'body' => 'Private synthetic photo report', 'urgency' => 'routine', 'anonymous' => true]);
        Storage::fake(IncidentPhotos::DISK);
        $this->scanner = new PhotoScanner;
        $this->app->instance(UploadScanner::class, $this->scanner);
    }

    private function photo(): UploadedFile
    {
        // A real JPEG with orientation and identifying metadata, never a real person.
        $image = imagecreatetruecolor(80, 40);
        imagefill($image, 0, 0, imagecolorallocate($image, 30, 70, 110));
        ob_start();
        imagejpeg($image);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);
        $tiff = hex2bin('49492a0008000000010012010300010000000600000000000000');
        $metadata = "Exif\0\0".$tiff.'GPS Private Location; Device Private Device';
        $bytes = substr($jpeg, 0, 2)."\xff\xe1".pack('n', strlen($metadata) + 2).$metadata.substr($jpeg, 2);

        return UploadedFile::fake()->createWithContent('private-person-device.jpg', $bytes);
    }

    private function refuses(callable $work, int $status): void
    {
        try {
            $work();
            $this->fail('Unauthorized private photo access succeeded.');
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }

    public function test_anonymous_photo_is_oriented_stripped_scanned_encrypted_and_exactly_retryable(): void
    {
        $service = app(IncidentPhotos::class);
        $file = $this->photo();
        $uuid = (string) Str::uuid();
        $first = $service->upload($this->secret, $file, $uuid);
        $this->assertSame($first, $service->upload($this->secret, $file, $uuid));
        $this->assertSame(2, count($this->scanner->scanned));
        $this->assertStringContainsString('GPS Private Location', $this->scanner->scanned[0]);
        $this->assertStringNotContainsString('GPS Private Location', $this->scanner->scanned[1]);
        $this->assertSame(40, $first['width']);
        $this->assertSame(80, $first['height']);
        $this->assertSame(2, $first['report_revision']);
        $this->assertSame(1, IncidentPhoto::count());
        $this->assertNull(IncidentReport::firstOrFail()->reporter_user_id);
        $this->assertFalse(DB::getSchemaBuilder()->hasColumn('incident_photos', 'user_id'));
        $row = IncidentPhoto::firstOrFail();
        $raw = DB::table('incident_photos')->first();
        $this->assertStringNotContainsString('synthetic-scanner', $raw->scan_receipt);
        $this->assertStringNotContainsString('JFIF', Storage::disk(IncidentPhotos::DISK)->get($row->path));
        $this->assertNotSame($row->thumbnail, $raw->thumbnail);
        $this->assertStringNotContainsString('private-person-device', json_encode($row->toArray(), JSON_THROW_ON_ERROR));
        $download = $service->reporterDownload($this->secret, $row->id);
        $this->assertSame('report-photo.jpg', $download['name']);
        $this->assertSame($this->scanner->scanned[1], $download['bytes']);
        $this->assertStringNotContainsString('Private Device', $download['bytes']);
        $this->assertCount(1, $service->reporter($this->secret));
        $this->assertSame($download, $service->staffDownload($this->professional, (int) $row->incident_report_id, $row->id));
        $this->refuses(fn () => $service->upload($this->secret, UploadedFile::fake()->image('different.jpg'), $uuid), 409);
        $this->assertSame(2, IncidentReport::firstOrFail()->revision);
    }

    public function test_wrong_code_school_role_owner_and_revoked_triage_never_return_bytes(): void
    {
        $service = app(IncidentPhotos::class);
        $photo = $service->upload($this->secret, $this->photo(), (string) Str::uuid());
        $report = (int) IncidentPhoto::firstOrFail()->incident_report_id;
        $this->refuses(fn () => $service->reporterDownload(str_repeat('b', 64), $photo['id']), 404);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $service->reporterDownload($this->secret, $photo['id'])), 404);
        $this->refuses(fn () => $service->staffDownload($this->superAdmin(), $report, $photo['id']), 403);
        $other = $this->schoolA->ids['tara_user'];
        $this->refuses(fn () => $service->staffDownload(User::findOrFail($other), $report, $photo['id']), 403);
        PlatformStaffIdentity::create(['user_id' => $this->professional->id, 'active' => true]);
        $this->refuses(fn () => $service->staffDownload($this->professional, $report, $photo['id']), 403);
        PlatformStaffIdentity::where('user_id', $this->professional->id)->delete();
        TeamAssignment::query()->update(['ends_on' => '2026-02-09']);
        $this->refuses(fn () => $service->staffDownload($this->professional, $report, $photo['id']), 404);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        app(IncidentReporting::class)->identify($parent, $this->secret, ['revision' => 2, 'identify_consent' => true, 'learner_id' => $this->schoolA->ids['asha']]);
        $this->refuses(fn () => $service->reporterDownload($this->secret, $photo['id']), 403);
        $this->assertNotEmpty($service->reporterDownload($this->secret, $photo['id'], $parent)['bytes']);
        $this->refuses(fn () => $service->reporterDownload($this->secret, $photo['id'], $this->professional), 403);
    }

    public function test_scanner_rejection_outage_and_context_swap_store_nothing(): void
    {
        $service = app(IncidentPhotos::class);
        $this->scanner->reject = true;
        $this->refuses(fn () => $service->upload($this->secret, $this->photo(), (string) Str::uuid()), 422);
        $this->scanner->reject = false;
        $this->scanner->offline = true;
        $this->refuses(fn () => $service->upload($this->secret, $this->photo(), (string) Str::uuid()), 503);
        $this->scanner->offline = false;
        $this->scanner->effect = fn () => app(CurrentSchool::class)->set(2);
        $this->refuses(fn () => $service->upload($this->secret, $this->photo(), (string) Str::uuid()), 403);
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertSame(0, IncidentPhoto::count());
        $this->assertSame(1, IncidentReport::firstOrFail()->revision);
        Storage::disk(IncidentPhotos::DISK)->assertDirectoryEmpty('/');
    }

    public function test_late_identification_during_scanning_refuses_the_file(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->scanner->effect = function () use ($parent) {
            app(IncidentReporting::class)->identify($parent, $this->secret, ['revision' => 1, 'identify_consent' => true, 'learner_id' => $this->schoolA->ids['asha']]);
        };
        $this->refuses(fn () => app(IncidentPhotos::class)->upload($this->secret, $this->photo(), (string) Str::uuid()), 403);
        $this->assertSame(0, IncidentPhoto::count());
        $this->assertSame(2, IncidentReport::firstOrFail()->revision);
    }

    public function test_invalid_images_are_refused_and_storage_is_cleaned_if_the_database_rolls_back(): void
    {
        try {
            app(IncidentPhotos::class)->upload($this->secret, UploadedFile::fake()->createWithContent('photo.jpg', 'not an image'), (string) Str::uuid());
            $this->fail('A fake image was accepted.');
        } catch (ValidationException) {
            $this->assertSame(0, IncidentPhoto::count());
        }
        IncidentPhoto::creating(fn () => throw new RuntimeException('Synthetic row failure'));
        try {
            app(IncidentPhotos::class)->upload($this->secret, $this->photo(), (string) Str::uuid());
            $this->fail('A failed transaction was accepted.');
        } catch (RuntimeException $error) {
            $this->assertSame('Synthetic row failure', $error->getMessage());
        } finally {
            IncidentPhoto::flushEventListeners();
            IncidentPhoto::bootBelongsToSchool();
        }
        Storage::disk(IncidentPhotos::DISK)->assertDirectoryEmpty('/');
        $this->assertSame(0, IncidentPhoto::count());
        $this->assertSame(1, IncidentReport::firstOrFail()->revision);
    }

    public function test_photo_limit_is_bounded_and_read_only_keeps_existing_reads(): void
    {
        $service = app(IncidentPhotos::class);
        for ($i = 0; $i < IncidentPhotos::LIMIT; $i++) {
            $service->upload($this->secret, UploadedFile::fake()->image('picture.jpg', 20 + $i, 20), (string) Str::uuid());
        }
        $this->refuses(fn () => $service->upload($this->secret, $this->photo(), (string) Str::uuid()), 422);
        $this->assertCount(10, $this->scanner->scanned);
        SchoolModule::where('module', 'wellbeing')->update(['read_only' => true]);
        app(ModuleGate::class)->forget();
        $this->assertCount(5, $service->reporter($this->secret));
        $this->refuses(fn () => $service->upload($this->secret, $this->photo(), (string) Str::uuid()), 404);
        $this->assertCount(10, $this->scanner->scanned);
    }

    public function test_context_bound_storage_refuses_path_tamper_and_ciphertext_tamper(): void
    {
        $service = app(IncidentPhotos::class);
        $photo = $service->upload($this->secret, $this->photo(), (string) Str::uuid());
        $row = IncidentPhoto::firstOrFail();
        $path = $row->path;
        $row->forceFill(['path' => '../../other-tenant-file.enc'])->save();
        $this->refuses(fn () => $service->reporterDownload($this->secret, $photo['id']), 404);
        $row->forceFill(['path' => $path])->save();
        Storage::disk(IncidentPhotos::DISK)->put($path, 'tampered ciphertext');
        $this->expectException(DecryptException::class);
        $service->reporterDownload($this->secret, $photo['id']);
    }

    public function test_password_readiness_and_module_are_rechecked_after_scanning(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        app(IncidentReporting::class)->identify($parent, $this->secret, ['revision' => 1, 'identify_consent' => true, 'learner_id' => $this->schoolA->ids['asha']]);
        $this->scanner->effect = fn () => $parent->forceFill(['password_change_required' => true])->save();
        $this->refuses(fn () => app(IncidentPhotos::class)->upload($this->secret, $this->photo(), (string) Str::uuid(), $parent), 403);
        $parent->forceFill(['password_change_required' => false])->save();
        $this->scanner->effect = function () {
            SchoolModule::where('module', 'wellbeing')->update(['read_only' => true]);
            app(ModuleGate::class)->forget();
        };
        $this->refuses(fn () => app(IncidentPhotos::class)->upload($this->secret, $this->photo(), (string) Str::uuid(), $parent), 404);
        $this->assertSame(0, IncidentPhoto::count());
        Storage::disk(IncidentPhotos::DISK)->assertDirectoryEmpty('/');
    }

    public function test_database_foreign_key_refuses_a_cross_school_photo_even_without_model_policies(): void
    {
        $other = app(CurrentSchool::class)->run(2, function () {
            $category = Category::create(['name' => 'Other school concern', 'emergency_guidance' => 'Seek trusted help.', 'active' => true, 'revision' => 1]);

            return IncidentReport::create(['category_id' => $category->id, 'anonymous' => true, 'secret_digest' => str_repeat('c', 64), 'submission_hash' => str_repeat('d', 64), 'body' => 'Other school private report', 'urgency' => 'routine', 'status' => 'open', 'revision' => 1])->id;
        });
        try {
            DB::table('incident_photos')->insert(['id' => (string) Str::uuid(), 'school_id' => 1, 'incident_report_id' => $other, 'operation_id' => (string) Str::uuid(), 'source_digest' => str_repeat('a', 64), 'sha256' => str_repeat('b', 64), 'path' => 'unused', 'bytes' => 1, 'width' => 1, 'height' => 1, 'report_revision' => 1, 'thumbnail' => 'unused', 'scan_receipt' => 'unused']);
            $this->fail('A photo was linked across school boundaries.');
        } catch (QueryException $error) {
            $this->assertStringContainsString('incident_photo_report_scope', $error->getMessage());
        }
        $this->assertSame(0, IncidentPhoto::count());
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('wellbeing-photo-test')->plainTextToken)
            ->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id, 'Accept' => 'application/json']);
    }

    public function test_anonymous_multipart_transport_ignores_cookie_and_keeps_code_out_of_urls(): void
    {
        $slug = School::findOrFail(1)->slug;
        $base = '/api/v2/wellbeing/public/'.$slug;
        config(['sanctum.stateful' => ['localhost']]);
        $this->actingAs($this->superAdmin())->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/', 'Accept' => 'application/json']);
        $input = ['secret' => $this->secret, 'operation_id' => (string) Str::uuid(), 'photo' => $this->photo()];
        $uploaded = $this->post($base.'/photos', $input)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame([], $uploaded->headers->getCookies());
        $this->assertNull(IncidentReport::firstOrFail()->reporter_user_id);
        $this->assertSame(1, IncidentPhoto::count());
        $id = $uploaded->json('data.id');
        $this->post($base.'/photos', $input)->assertOk()->assertJsonPath('data.id', $id);
        $this->assertCount(2, $this->scanner->scanned);
        $read = $this->postJson($base.'/follow-up', ['secret' => $this->secret])->assertOk()->assertJsonPath('data.photos.0.id', $id);
        $this->assertStringNotContainsString('incident-photos/', $read->getContent());
        $download = $this->postJson($base.'/photos/download', ['secret' => $this->secret, 'photo_id' => $id])->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Content-Disposition', 'attachment; filename="report-photo.jpg"')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame($this->scanner->scanned[1], $download->getContent());
        $this->post($base.'/photos?secret='.$this->secret, $input)->assertUnprocessable()->assertHeader('Cache-Control', 'no-store, private');
        $this->post($base.'/photos?anything=1', $input)->assertUnprocessable();
        $this->postJson($base.'/photos/download', ['secret' => str_repeat('0', 64), 'photo_id' => $id])->assertNotFound();
        $this->postJson($base.'/photos/download', ['secret' => $this->secret, 'photo_id' => (string) Str::uuid()])->assertNotFound();
    }

    public function test_identified_photo_reads_and_staff_images_require_fresh_independent_record_scope(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        app(IncidentReporting::class)->identify($parent, $this->secret, ['revision' => 1, 'identify_consent' => true, 'learner_id' => $this->schoolA->ids['asha']]);
        $photo = $this->api($parent)->post('/api/v2/wellbeing/own/photos', ['secret' => $this->secret, 'operation_id' => (string) Str::uuid(), 'photo' => $this->photo()])->assertOk()->json('data');
        $report = IncidentPhoto::firstOrFail()->incident_report_id;
        $this->postJson('/api/v2/wellbeing/own/follow-up', ['secret' => $this->secret])->assertOk()->assertJsonPath('data.photos.0.id', $photo['id']);
        $this->postJson('/api/v2/wellbeing/own/photos/download', ['secret' => $this->secret, 'photo_id' => $photo['id']])->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->api($this->professional)->getJson('/api/v2/wellbeing/reports/'.$report)->assertOk()->assertJsonPath('data.photos.0.id', $photo['id']);
        $this->getJson('/api/v2/wellbeing/reports/'.$report.'/photos/'.$photo['id'])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->api($this->superAdmin())->getJson('/api/v2/wellbeing/reports/'.$report.'/photos/'.$photo['id'])->assertForbidden();
        $this->api($this->professional)->postJson('/api/v2/wellbeing/own/photos/download', ['secret' => $this->secret, 'photo_id' => $photo['id']])->assertForbidden();
        $this->api($parent)->withHeader('X-Account-Id', (string) $this->professional->id)->postJson('/api/v2/wellbeing/own/photos/download', ['secret' => $this->secret, 'photo_id' => $photo['id']])->assertForbidden();
        $this->api($parent)->withHeader('X-School-Id', '2')->postJson('/api/v2/wellbeing/own/photos/download', ['secret' => $this->secret, 'photo_id' => $photo['id']])->assertForbidden();
        TeamAssignment::query()->update(['ends_on' => '2026-02-09']);
        $this->api($this->professional)->getJson('/api/v2/wellbeing/reports/'.$report.'/photos/'.$photo['id'])->assertNotFound();
        SchoolModule::where('module', 'wellbeing')->update(['read_only' => true]);
        app(ModuleGate::class)->forget();
        $this->api($parent)->postJson('/api/v2/wellbeing/own/photos/download', ['secret' => $this->secret, 'photo_id' => $photo['id']])->assertOk();
        $this->post('/api/v2/wellbeing/own/photos', ['secret' => $this->secret, 'operation_id' => (string) Str::uuid(), 'photo' => $this->photo()])->assertForbidden();
    }

    public function test_public_photo_html_retains_private_thread_on_scan_failure_and_readonly_without_session(): void
    {
        $slug = School::findOrFail(1)->slug;
        $base = '/school/'.$slug.'/report';
        $this->withHeaders(['Accept' => 'text/html']);
        $this->post($base.'/follow-up', ['secret' => $this->secret])->assertOk()->assertSee('multipart/form-data', false)->assertSee('capture="environment"', false);
        $input = ['secret' => $this->secret, 'operation_id' => (string) Str::uuid(), 'photo' => $this->photo()];
        $this->scanner->offline = true;
        $response = $this->post($base.'/photos', $input)->assertStatus(503)->assertSee('Private synthetic photo report')->assertSee('Your report remains available.');
        $this->assertSame([], $response->headers->getCookies());
        $this->assertFalse(session()->has('_old_input'));
        $this->scanner->offline = false;
        $response = $this->post($base.'/photos', $input)->assertOk()->assertSee('Your photo was received.')->assertSee('data:image/jpeg;base64,', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString('img-src data:', $response->headers->get('Content-Security-Policy'));
        $photo = IncidentPhoto::firstOrFail();
        $this->post($base.'/photos/download', ['secret' => $this->secret, 'photo_id' => $photo->id])->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        SchoolModule::where('module', 'wellbeing')->update(['read_only' => true]);
        app(ModuleGate::class)->forget();
        $this->post($base.'/photos', ['secret' => $this->secret, 'operation_id' => (string) Str::uuid(), 'photo' => $this->photo()])->assertStatus(423)->assertSee('Private synthetic photo report')->assertDontSee('multipart/form-data', false);
        $this->post($base.'/photos/download', ['secret' => $this->secret, 'photo_id' => $photo->id])->assertOk();
        $this->assertSame(1, IncidentPhoto::count());
    }

    public function test_oversize_and_foreign_report_photos_are_rejected_before_pixels_are_returned(): void
    {
        $photo = app(IncidentPhotos::class)->upload($this->secret, $this->photo(), (string) Str::uuid());
        $other = bin2hex(random_bytes(32));
        app(IncidentReporting::class)->submit(['secret' => $other, 'category_id' => $this->category['id'], 'body' => 'Separate private report', 'urgency' => 'routine', 'anonymous' => true]);
        $this->refuses(fn () => app(IncidentPhotos::class)->reporterDownload($other, $photo['id']), 404);
        try {
            app(IncidentPhotos::class)->upload($this->secret, UploadedFile::fake()->create('big.jpg', 4097, 'image/jpeg'), (string) Str::uuid());
            $this->fail('Oversize photo accepted.');
        } catch (ValidationException) {
            $this->assertCount(2, $this->scanner->scanned);
        }
        DB::table('school_modules')->updateOrInsert(['school_id' => 2, 'module' => 'wellbeing'], ['in_package' => true, 'enabled' => true, 'read_only' => false, 'created_at' => now(), 'updated_at' => now()]);
        app(ModuleGate::class)->forget();
        $slug = School::findOrFail(2)->slug;
        $this->postJson('/api/v2/wellbeing/public/'.$slug.'/photos/download', ['secret' => $this->secret, 'photo_id' => $photo['id']])->assertNotFound();
    }

    public function test_panel_displays_only_assigned_photos_and_pins_download_account_and_school(): void
    {
        ParentPolicyService::acceptCurrent($this->professional);
        $photo = app(IncidentPhotos::class)->upload($this->secret, $this->photo(), (string) Str::uuid());
        $report = IncidentPhoto::firstOrFail()->incident_report_id;
        $url = '/wellbeing/reports/'.$report.'/photos/'.$photo['id'];
        $pins = '?expected_account_id='.$this->professional->id.'&expected_school_id=1';
        $this->actingAs($this->professional)->get('/wellbeing?mode=clinical&report='.$report)->assertOk()->assertSee('data:image/jpeg;base64,', false)->assertSee('Download report photo');
        $this->get($url.$pins)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
        $this->get($url.'?expected_account_id='.$this->superAdmin()->id.'&expected_school_id=1')->assertForbidden();
        $this->get($url.'?expected_account_id='.$this->professional->id.'&expected_school_id=2')->assertForbidden();
        $this->actingAs($this->superAdmin())->get($url.$pins)->assertForbidden();
        TeamAssignment::query()->update(['ends_on' => '2026-02-09']);
        $this->actingAs($this->professional)->get($url.$pins)->assertNotFound();
    }

    public function test_signed_in_transport_does_not_identify_an_anonymous_photo_in_private_audit(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->api($parent)->post('/api/v2/wellbeing/own/photos', ['secret' => $this->secret, 'operation_id' => (string) Str::uuid(), 'photo' => $this->photo()])->assertOk();
        $this->assertNull(IncidentReport::firstOrFail()->reporter_user_id);
        $this->assertNull(Event::where('event', 'wellbeing.photo_added')->firstOrFail()->actor_id);
    }

    public function test_supplied_actor_never_becomes_an_anonymous_photo_audit_identity(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        app(IncidentPhotos::class)->upload($this->secret, $this->photo(), (string) Str::uuid(), $parent);
        $this->assertNull(IncidentReport::firstOrFail()->reporter_user_id);
        $this->assertNull(Event::where('event', 'wellbeing.photo_added')->firstOrFail()->actor_id);
    }
}
