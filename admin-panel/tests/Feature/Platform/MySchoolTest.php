<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\SchoolMembership;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\School\SchoolInformation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class MySchoolTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        Storage::fake('public');
    }

    private function pdf(string $name = 'schedule.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    public function test_admin_edits_contacts_and_branding_using_the_existing_scoped_settings(): void
    {
        $this->actingAs($this->superAdmin(), 'web')->get('/school-information')->assertOk()->assertSee('School contacts and branding');
        $this->put('/school-information', ['school_name' => 'Jacaranda School', 'school_short_name' => 'JS',
            'school_phone' => '012 345 6789', 'school_email' => 'office@school.test', 'school_website' => 'https://school.test',
            'school_address' => '1 School Road', 'time_zone' => 'Africa/Johannesburg', 'school_tagline' => 'Learning together'])->assertOk();
        $answer = $this->getJson('/api/v2/schools/1/my-school')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame('Jacaranda School', $answer->json('data.school.name'));
        $this->assertSame('012 345 6789', $answer->json('data.phone'));
        $this->assertNotEmpty($answer->json('data.term_dates'));
        $this->getJson('/api/v2/me/context')->assertOk()->assertJsonPath('data.current_school.name', 'Jacaranda School');
        $this->assertSame('Baobab High', DB::table('schools')->where('id', 2)->value('name'));
        $this->assertSame(1, (int) DB::table('audit_log')->where('action', 'school.information_updated')->sole()->school_id);
        $this->assertTrue(DB::table('domain_events')->where('school_id', 1)->where('type', 'context.changed')->exists());
        $this->actingAs($this->superAdmin(), 'web')->putJson('/school-information', ['school_short_name' => null, 'school_phone' => null])->assertOk();
        $this->getJson('/api/v2/schools/1/my-school')->assertOk()->assertJsonPath('data.phone', null)->assertJsonPath('data.school.short_name', null);
    }

    public function test_school_documents_appear_for_all_school_profiles_and_can_be_unlisted(): void
    {
        $this->actingAs($this->superAdmin(), 'web')->post('/school-information/documents', ['title' => 'Fee schedule', 'kind' => 'fee_schedule', 'document' => $this->pdf()])->assertRedirect('/school-information');
        foreach (['asha_user', 'farid_user', 'tara_user'] as $key) {
            $reply = $this->actingAs(User::findOrFail($this->schoolA->ids[$key]))->getJson('/api/v2/schools/1/my-school')->assertOk();
            $this->assertSame('Fee schedule', $reply->json('data.documents.0.title'));
            $this->assertStringContainsString('/storage/schools/1/school-documents/', $reply->json('data.documents.0.url'));
        }
        $id = $reply->json('data.documents.0.id');
        $path = json_decode(Settings::query()->where('type', 'school_documents')->value('message'), true)[0]['path'];
        Storage::disk('public')->assertExists($path);
        $this->actingAs($this->superAdmin(), 'web')->delete('/school-information/documents/'.$id)->assertRedirect();
        $this->getJson('/api/v2/schools/1/my-school')->assertOk()->assertJsonPath('data.documents', []);
        Storage::disk('public')->assertExists($path); // Explicitly published links remain valid.
    }

    public function test_wrong_roles_and_owners_cannot_edit_or_publish(): void
    {
        foreach (['asha_user', 'farid_user', 'tara_user'] as $key) {
            $this->actingAs(User::findOrFail($this->schoolA->ids[$key]))->get('/school-information')->assertForbidden();
            $this->put('/school-information', ['school_phone' => 'wrong'])->assertForbidden();
            $this->post('/school-information/documents', ['title' => 'No', 'kind' => 'other', 'document' => $this->pdf()])->assertForbidden();
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_wrong_school_routes_and_foreign_document_ids_are_refused(): void
    {
        $this->actingAs($this->superAdmin(), 'web')->getJson('/api/v2/schools/2/my-school')->assertNotFound();
        $this->actingAs($this->superAdmin(), 'web')->delete('/school-information/documents/550e8400-e29b-41d4-a716-446655440000')->assertNotFound();
        $this->withHeader('X-School-Id', '2')->getJson('/api/v2/schools/2/my-school')->assertForbidden();
        $this->actingAs($this->superAdmin(), 'web')->put('/school-information', ['school_name' => 'No'])->assertForbidden();
        $this->assertSame('Baobab High', DB::table('schools')->where('id', 2)->value('name'));
    }

    public function test_an_admin_cannot_unlist_an_existing_document_in_another_school(): void
    {
        $admin = $this->superAdmin();
        $id = app(CurrentSchool::class)->run(2, function () use ($admin) {
            $admin->assignRole('Super Admin');
            app(SchoolInformation::class)->publish($admin, ['title' => 'Baobab policy', 'kind' => 'code_of_conduct', 'document' => $this->pdf()]);

            return json_decode(Settings::query()->where('type', 'school_documents')->value('message'), true)[0]['id'];
        });
        $this->actingAs($admin, 'web')->delete('/school-information/documents/'.$id)->assertNotFound();
        $this->getJson('/api/v2/schools/1/my-school')->assertOk()->assertDontSee('Baobab policy');
        $this->withHeader('X-School-Id', '2')->getJson('/api/v2/schools/2/my-school')->assertOk()->assertJsonPath('data.documents.0.id', $id);
    }

    public function test_cached_admin_roles_do_not_grant_editing_after_their_membership_expires(): void
    {
        $admin = $this->superAdmin();
        $admin->assignRole('Teacher');
        $admin->load('roles');
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $admin->id)->where('role', 'Super Admin')->update(['ends_on' => now()->subDay()->toDateString()]);
        $this->actingAs($admin)->put('/school-information', ['school_phone' => 'wrong'])->assertForbidden();
        $this->getJson('/api/v2/schools/1/my-school')->assertOk();
    }

    public function test_a_person_at_two_schools_reads_each_only_under_its_own_scope(): void
    {
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->run(2, function () use ($admin) {
            $admin->assignRole('Super Admin');
            Settings::query()->updateOrCreate(['type' => 'school_phone'], ['message' => 'Baobab phone']);
        });
        $this->actingAs($admin)->getJson('/api/v2/schools/1/my-school')->assertOk()->assertDontSee('Baobab phone');
        $this->withHeader('X-School-Id', '2')->getJson('/api/v2/schools/2/my-school')->assertOk()->assertJsonPath('data.phone', 'Baobab phone');
        $this->getJson('/api/v2/schools/1/my-school')->assertNotFound();
    }

    public function test_invalid_contact_and_uploads_do_not_change_settings_or_store_files(): void
    {
        $this->actingAs($this->superAdmin(), 'web')->putJson('/school-information', ['school_name' => 'Should not save', 'school_website' => 'javascript:alert(1)'])->assertUnprocessable();
        $bad = UploadedFile::fake()->createWithContent('bad.pdf', '<?php echo 1;');
        $this->post('/school-information/documents', ['title' => 'No', 'kind' => 'other', 'document' => new UploadedFile($bad->getRealPath(), 'bad.pdf', null, null, true)])->assertRedirect();
        $this->post('/school-information/documents', ['title' => 'No', 'kind' => 'other', 'document' => $this->pdf('bad.php.pdf')])->assertRedirect();
        $this->assertNotSame('Should not save', DB::table('schools')->where('id', 1)->value('name'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_crest_is_reencoded_and_published_in_school_context(): void
    {
        $this->actingAs($this->superAdmin(), 'web')->put('/school-information', ['crest' => UploadedFile::fake()->image('crest.png')])->assertOk();
        $path = Settings::query()->where('type', 'logo1')->value('message');
        $this->assertStringStartsWith('schools/1/school-branding/', $path);
        Storage::disk('public')->assertExists($path);
        $this->getJson('/api/v2/schools/1/my-school')->assertOk()->assertJsonPath('data.school.crest_url', url(Storage::disk('public')->url($path)));
    }

    public function test_unsafe_legacy_website_values_are_not_published(): void
    {
        Settings::query()->updateOrCreate(['type' => 'school_website'], ['message' => 'javascript:alert(1)']);
        $this->actingAs($this->superAdmin(), 'web')->getJson('/api/v2/schools/1/my-school')->assertOk()->assertJsonPath('data.website', null);
    }
}
