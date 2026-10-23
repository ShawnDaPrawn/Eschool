<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\ProfileFile;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Services\People\Profiles\ProfileDefinitions;
use App\Services\People\Profiles\ProfileFiles;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class ProfileFilesTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private ProfileFieldDefinition $definition;

    private const PDF = "%PDF-1.4\nPRIVATE DOCUMENT BODY\n%%EOF";

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Storage::fake(ProfileFiles::DISK);
        app(GuardianService::class)->syncLegacy(Students::findOrFail($this->schoolA->ids['asha']));
        User::findOrFail($this->schoolA->ids['farid_user'])->forceFill(['email_verified_at' => now()])->save();
        $category = ProfileFieldCategory::query()->where('profile_type', 'learner')->where('key', 'health')->firstOrFail();
        $this->definition = app(ProfileDefinitions::class)->field($this->superAdmin(), ['category_id' => $category->id,
            'key' => 'support_document', 'label' => 'Support document', 'type' => 'file', 'sensitive' => true,
            'visible_to' => ['owner', 'guardian', 'school_admin'], 'editable_by' => ['guardian', 'school_admin']]);
    }

    private function person(): int
    {
        return $this->schoolA->ids['asha_user'];
    }

    private function upload(): array
    {
        $this->actingAs($this->superAdmin())->post('/api/v2/people/'.$this->person().'/profile/fields/support_document/file',
            ['profile_type' => 'learner', 'document' => UploadedFile::fake()->createWithContent('PRIVATE-health.pdf', self::PDF)])->assertOk()->assertJsonPath('data', null);
        $form = $this->getJson('/api/v2/people/'.$this->person().'/profile?profile_type=learner')->assertOk();

        return collect($form->json('data.categories'))->pluck('fields')->flatten(1)->firstWhere('key', 'support_document')['value']['file'];
    }

    public function test_files_and_names_are_encrypted_and_only_authenticated_assets_serve_bytes(): void
    {
        $file = $this->upload();
        $this->assertSame('PRIVATE-health.pdf', $file['name']);
        $this->assertStringNotContainsString('PRIVATE', DB::table('profile_files')->value('name'));
        $path = DB::table('profile_files')->value('path');
        $this->assertStringNotContainsString('PRIVATE', Storage::disk(ProfileFiles::DISK)->get($path));
        $this->assertStringNotContainsString('PRIVATE', json_encode(ProfileFile::first()->toArray()));
        $this->get($file['url'], ['Accept' => 'application/json'])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Content-Type', 'application/pdf')->assertContent(self::PDF);
        $this->assertStringNotContainsString('PRIVATE', json_encode(DB::table('audit_log')->get()));
        $this->assertSame(1, (int) DB::table('audit_log')->where('action', 'profile.file_viewed')->sole()->school_id);
    }

    public function test_wrong_person_school_and_role_cannot_open_a_file(): void
    {
        $file = $this->upload();
        $this->actingAs(User::findOrFail($this->schoolA->ids['gina_user']))->getJson($file['url'])->assertNotFound();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->getJson($file['url'])->assertNotFound();
        $this->actingAs($this->superAdmin())->getJson('/protected/profile-files/people/'.$this->schoolA->ids['farid_user'].'/'.$file['id'])->assertNotFound();
        $this->withHeader('X-School-Id', '2')->getJson($file['url'])->assertForbidden();
    }

    public function test_a_verified_approving_guardian_can_upload_but_an_unverified_guardian_cannot(): void
    {
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->actingAs($parent)->post('/api/v2/people/'.$this->person().'/profile/fields/support_document/file', ['profile_type' => 'learner', 'document' => UploadedFile::fake()->createWithContent('letter.pdf', self::PDF)])->assertOk();
        $parent->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($parent)->post('/api/v2/people/'.$this->person().'/profile/fields/support_document/file', ['profile_type' => 'learner', 'document' => UploadedFile::fake()->createWithContent('letter.pdf', self::PDF)])->assertNotFound();
        $this->assertSame(1, ProfileFile::query()->count());
    }

    public function test_replacing_or_clearing_a_file_invalidates_old_links_and_references_cannot_be_forged(): void
    {
        $old = $this->upload();
        $new = $this->upload();
        $this->getJson($old['url'])->assertNotFound();
        $this->getJson($new['url'])->assertOk();
        $this->putJson('/api/v2/people/'.$this->person().'/profile', ['profile_type' => 'learner', 'values' => ['support_document' => ['text' => $old['id']]]])->assertUnprocessable();
        $this->putJson('/api/v2/people/'.$this->person().'/profile', ['profile_type' => 'learner', 'values' => ['support_document' => ['clear' => true]]])->assertOk();
        $this->getJson($new['url'])->assertNotFound();
    }

    public function test_hidden_fields_and_wrong_storage_paths_fail_before_content_decryption(): void
    {
        $file = $this->upload();
        $path = ProfileFile::first()->path;
        Storage::disk(ProfileFiles::DISK)->put($path, 'not valid ciphertext');
        $this->definition->update(['hidden' => true]);
        $this->getJson($file['url'])->assertNotFound();
        $this->definition->update(['hidden' => false]);
        ProfileFile::first()->update(['path' => 'schools/2/profile-files/foreign.enc']);
        $this->getJson($file['url'])->assertNotFound();
    }

    public function test_exports_filter_sensitive_files_before_decrypting_metadata_and_include_authorised_names(): void
    {
        $this->upload();
        $csv = $this->actingAs($this->superAdmin(), 'web')->get('/people-profiles/'.$this->person().'/export?profile_type=learner')->assertOk()->streamedContent();
        $this->assertStringContainsString('PRIVATE-health.pdf', $csv);
        DB::table('profile_files')->update(['name' => 'not valid ciphertext']);
        $csv = $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']), 'web')->get('/people-profiles/'.$this->person().'/export?profile_type=learner')->assertOk()->streamedContent();
        $this->assertStringNotContainsString('support_document', $csv);
    }

    public function test_required_files_cannot_be_cleared_and_deleted_definitions_revoke_old_links(): void
    {
        $file = $this->upload();
        $this->definition->update(['required' => true]);
        $this->putJson('/api/v2/people/'.$this->person().'/profile', ['profile_type' => 'learner', 'values' => ['support_document' => ['clear' => true]]])->assertUnprocessable();
        $this->getJson($file['url'])->assertOk();
        $this->definition->delete();
        $this->getJson($file['url'])->assertNotFound();
    }

    public function test_sensitive_assets_are_unavailable_to_support_before_decryption(): void
    {
        $file = $this->upload();
        Storage::disk(ProfileFiles::DISK)->put(ProfileFile::first()->path, 'invalid ciphertext');
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        try {
            app(ProfileFiles::class)->read($this->superAdmin(), User::findOrFail($this->person()), $file['id']);
            $this->fail('Support must not decrypt sensitive attachments.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    public function test_failed_value_write_rolls_back_metadata_and_removes_the_new_ciphertext(): void
    {
        // Isolate a write failure after the blob and metadata were created.
        $original = ProfileFieldValue::getEventDispatcher();
        $events = clone $original;
        ProfileFieldValue::setEventDispatcher($events);
        $events->listen('eloquent.creating: '.ProfileFieldValue::class, function () {
            throw new RuntimeException('Injected profile value write failure');
        });
        try {
            app(ProfileFiles::class)->upload($this->superAdmin(), User::findOrFail($this->person()), 'learner', 'support_document', UploadedFile::fake()->createWithContent('letter.pdf', self::PDF));
            $this->fail('The injected write failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected profile value write failure', $exception->getMessage());
        } finally {
            ProfileFieldValue::setEventDispatcher($original);
        }
        $this->assertSame(0, ProfileFile::query()->count());
        $this->assertSame(0, ProfileFieldValue::query()->where('definition_id', $this->definition->id)->count());
        $this->assertSame([], Storage::disk(ProfileFiles::DISK)->allFiles());
    }

    public function test_native_token_and_portal_cors_use_the_protected_asset_route_and_anonymous_access_is_denied(): void
    {
        $file = $this->upload();
        $admin = $this->superAdmin();
        $token = $admin->createToken('profile-file-test')->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->getJson($file['url'])->assertUnauthorized();
        $this->withToken($token)->getJson($file['url'])->assertOk()->assertContent(self::PDF)->assertHeader('Content-Security-Policy', 'sandbox');
        $origin = config('cors.allowed_origins')[0];
        $this->options($file['url'], [], ['Origin' => $origin, 'Access-Control-Request-Method' => 'GET', 'Access-Control-Request-Headers' => 'Authorization,X-School-Id'])
            ->assertSuccessful()->assertHeader('Access-Control-Allow-Origin', $origin)->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_file_input_validation_does_not_write_metadata_or_storage(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/api/v2/people/'.$this->person().'/profile/fields/support_document/file', ['profile_type' => 'learner', 'document' => UploadedFile::fake()->create('bad.html', 1, 'text/html')])->assertUnprocessable();
        $this->assertSame(0, ProfileFile::query()->count());
        $this->assertSame([], Storage::disk(ProfileFiles::DISK)->allFiles());
    }

    public function test_an_owner_uploads_a_png_and_reads_it_through_the_own_asset_route(): void
    {
        $category = ProfileFieldCategory::query()->where('profile_type', 'parent')->firstOrFail();
        app(ProfileDefinitions::class)->field($this->superAdmin(), ['category_id' => $category->id,
            'key' => 'own_document', 'label' => 'Own document', 'type' => 'file',
            'visible_to' => ['owner'], 'editable_by' => ['owner']]);
        $document = UploadedFile::fake()->image('letter.png', 2, 2);
        $bytes = $document->getContent();
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']))
            ->post('/api/v2/me/profile/fields/own_document/file', ['profile_type' => 'parent', 'document' => $document])->assertOk();
        $form = $this->getJson('/api/v2/me/profile?profile_type=parent')->assertOk();
        $file = collect($form->json('data.categories'))->pluck('fields')->flatten(1)->firstWhere('key', 'own_document')['value']['file'];
        $this->getJson('/protected/profile-files/me/'.$file['id'])->assertOk()->assertHeader('Content-Type', 'image/png')->assertContent($bytes);
    }
}
