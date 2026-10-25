<?php

declare(strict_types=1);

namespace Tests\Feature\Gallery;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Facilitators\FacilitatorAccess;
use App\Services\Gallery\GalleryPhotos;
use App\Services\Gallery\GallerySettings;
use App\Services\Platform\Files\UploadScanner;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class GalleryPanelTest extends TestCase
{
    use UsesTwoSchools;

    private User $coach;

    private ActivityGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T10:00:00+02:00'));
        config(['modules.gallery' => ['always_on' => true], 'modules.groups' => ['always_on' => true]]);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        $year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->coach = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->group = ActivityGroup::create(['session_year_id' => $year, 'name' => 'Private gallery group', 'discipline' => 'Sport', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        ActivityStaff::create(['activity_group_id' => $this->group->id, 'user_id' => $this->coach->id, 'role' => 'coach', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic photo group', 'revision' => 1]);
        ActivityMembership::create(['activity_group_id' => $this->group->id, 'learner_id' => $this->schoolA->ids['asha'], 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic photo membership', 'revision' => 1]);
        User::whereKey($this->schoolA->ids['asha_user'])->update(['handle' => 'asha.gallery']);
        Storage::fake(GalleryPhotos::DISK);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-clean', 'signature' => null];
            }
        });
    }

    private function pin(array $data, ?User $actor = null): array
    {
        return ['operation_id' => (string) Str::uuid(), 'expected_account_id' => ($actor ?? $this->coach)->id, 'expected_school_id' => 1] + $data;
    }

    private function upload(): GalleryPhoto
    {
        $this->actingAs($this->coach)->post('/school-gallery/upload', $this->pin(['scope' => 'group', 'activity_group_id' => $this->group->id, 'session_year_id' => $this->group->session_year_id, 'captured_on' => '2026-02-09', 'caption' => 'Private caption <script>unsafe()</script>', 'photo' => UploadedFile::fake()->image('private-name.jpg', 30, 20)]))->assertRedirect();

        return GalleryPhoto::firstOrFail();
    }

    public function test_group_upload_private_image_handle_proposal_and_withdrawal(): void
    {
        $this->actingAs($this->coach)->get('/school-gallery')->assertOk()->assertSee('Private gallery group')->assertHeader('Cache-Control', 'no-store, private');
        $photo = $this->upload();
        $this->get('/school-gallery?group_id='.$this->group->id)->assertOk()->assertSee('Private caption &lt;script&gt;', false)->assertDontSee('<script>unsafe()', false);
        $url = '/school-gallery/photos/'.$photo->id.'?expected_account_id='.$this->coach->id.'&expected_school_id=1&thumbnail=1';
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/school-gallery/photos/'.$photo->id.'/tags', $this->pin(['handle' => '@asha.gallery']))->assertRedirect();
        $this->assertSame('proposed', PhotoTag::firstOrFail()->status);
        $this->post('/school-gallery/photos/'.$photo->id.'/withdraw', $this->pin(['revision' => $photo->revision]))->assertRedirect();
        $this->get($url)->assertNotFound();
        $this->assertFalse($photo->fresh()->active);
    }

    public function test_private_validation_preserves_only_author_response_not_session_and_stale_pin_never_reflects(): void
    {
        $request = $this->pin(['scope' => 'group', 'activity_group_id' => $this->group->id, 'session_year_id' => $this->group->session_year_id, 'captured_on' => '2026-02-09', 'caption' => 'Private unsaved caption']);
        $this->actingAs($this->coach)->post('/school-gallery/upload', $request)->assertStatus(422)->assertSee('Private unsaved caption')->assertSessionMissing('_old_input.caption');
        $request['expected_account_id'] = $this->schoolA->ids['theo_user'];
        $this->post('/school-gallery/upload', $request)->assertForbidden()->assertDontSee('Private unsaved caption')->assertSessionMissing('_old_input');
        $this->assertSame(0, GalleryPhoto::count());
    }

    public function test_unassigned_staff_parent_and_foreign_pin_cannot_open_photo_or_personal_gallery(): void
    {
        $photo = $this->upload();
        $url = '/school-gallery/photos/'.$photo->id.'?expected_account_id='.$this->schoolA->ids['theo_user'].'&expected_school_id=1';
        $this->actingAs(User::findOrFail($this->schoolA->ids['theo_user']))->get($url)->assertNotFound();
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']))->get('/school-gallery')->assertRedirect()->assertDontSee('Private caption');
        $this->actingAs($this->coach)->get('/school-gallery/photos/'.$photo->id.'?expected_account_id='.$this->coach->id.'&expected_school_id=2')->assertForbidden();
        $photo->update(['scope' => 'family', 'activity_group_id' => null, 'owner_learner_id' => $this->schoolA->ids['asha']]);
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/school-gallery/photos/'.$photo->id.'?expected_account_id='.$admin->id.'&expected_school_id=1')->assertNotFound()->assertDontSee('Private caption');
    }

    public function test_only_manager_changes_school_consent(): void
    {
        $this->actingAs($this->coach)->post('/school-gallery/settings', $this->pin(['revision' => 0, 'guardian_required' => false]))->assertForbidden();
        $admin = $this->superAdmin();
        $this->actingAs($admin)->post('/school-gallery/settings', $this->pin(['revision' => 0, 'guardian_required' => false], $admin))->assertRedirect();
        $this->assertFalse(app(GallerySettings::class)->current()->guardian_required);
    }

    public function test_old_year_upload_is_refused_without_flash(): void
    {
        $old = SessionYear::create(['name' => 'Gallery historical year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $this->actingAs($this->coach)->withSession(['session_year' => $old->id])->post('/school-gallery/upload', $this->pin(['scope' => 'group', 'activity_group_id' => $this->group->id, 'session_year_id' => $this->group->session_year_id, 'captured_on' => '2026-02-09', 'caption' => 'Private historical draft', 'photo' => UploadedFile::fake()->image('synthetic.jpg')]))->assertStatus(423)->assertSee('Private historical draft')->assertSessionMissing('_old_input.caption');
        $this->assertSame(0, GalleryPhoto::count());
    }

    public function test_facilitator_gallery_flag_tracks_current_group_source_and_disabled_module(): void
    {
        $access = app(FacilitatorAccess::class);
        $this->assertTrue($access->profile($this->coach)['features']['gallery']);
        ActivityStaff::where('activity_group_id', $this->group->id)->where('user_id', $this->coach->id)->update(['active' => false]);
        $this->assertFalse($access->profile($this->coach)['features']['gallery']);
        ActivityStaff::where('activity_group_id', $this->group->id)->where('user_id', $this->coach->id)->update(['active' => true]);
        config(['modules.gallery' => ['default' => false]]);
        $this->assertFalse($access->profile($this->coach)['features']['gallery']);
    }
}
