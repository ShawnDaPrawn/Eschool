<?php

declare(strict_types=1);

namespace Tests\Feature\Gallery;

use App\Events\Transfers\TransferCompleted;
use App\Models\Academics\GradeLevel;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\ClassSection;
use App\Models\Gallery\GalleryEvent;
use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\GalleryTransferChoice;
use App\Models\Gallery\PhotoTag;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferReceipt;
use App\Models\User;
use App\Services\Gallery\GalleryAuthority;
use App\Services\Gallery\GalleryPhotos;
use App\Services\Gallery\GalleryQueries;
use App\Services\Gallery\GalleryRetention;
use App\Services\Gallery\GallerySettings;
use App\Services\Gallery\GalleryTags;
use App\Services\Gallery\GalleryTransfers;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Transfers\TransferAccept;
use App\Services\Transfers\TransferPackages;
use App\Services\Transfers\TransferVault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class PrivateGalleryTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $parent;

    private User $student;

    private int $learner;

    private int $year;

    private UploadScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T10:00:00+02:00'));
        config(['modules.gallery' => ['always_on' => true], 'modules.groups' => ['always_on' => true]]);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $this->student = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->learner = (int) $this->schoolA->ids['asha'];
        $this->year = (int) $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        GuardianLink::updateOrCreate(['learner_id' => $this->learner, 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        LearnerApproval::create(['learner_id' => $this->learner, 'guardian_user_id' => $this->parent->id, 'consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        Storage::fake(GalleryPhotos::DISK);
        $this->scanner = new class implements UploadScanner
        {
            public bool $clean = true;

            public bool $unavailable = false;

            public function scan(string $bytes): array
            {
                if ($this->unavailable) {
                    throw new \RuntimeException('Synthetic scanner outage');
                }

                return ['clean' => $this->clean, 'engine' => 'synthetic-clean', 'signature' => null];
            }
        };
        $this->app->instance(UploadScanner::class, $this->scanner);
    }

    private function op(array $d = []): array
    {
        return ['operation_id' => (string) Str::uuid()] + $d;
    }

    private function upload(?User $actor = null, array $extra = []): GalleryPhoto
    {
        return app(GalleryPhotos::class)->upload($actor ?? $this->parent, $this->op($extra + ['scope' => 'family', 'learner_id' => $this->learner, 'session_year_id' => $this->year, 'captured_on' => '2026-02-09', 'caption' => 'Private synthetic photograph']), UploadedFile::fake()->image('original-child-name.jpg', 30, 20));
    }

    private function refuse(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected private gallery refusal.');
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (ValidationException $error) {
            $this->assertSame($status, $error->status);
        }
    }

    public function test_guardian_upload_is_private_scanned_encrypted_and_exact_retry_is_stable(): void
    {
        $file = UploadedFile::fake()->image('sensitive-original-name.jpg', 24, 20);
        $d = $this->op(['scope' => 'family', 'learner_id' => $this->learner, 'session_year_id' => $this->year, 'captured_on' => '2026-02-09', 'caption' => 'Private photograph caption']);
        $service = app(GalleryPhotos::class);
        $photo = $service->upload($this->parent, $d, $file);
        $this->assertSame($photo->id, $service->upload($this->parent, $d, $file)->id);
        $this->assertSame(1, GalleryPhoto::count());
        $this->assertSame('approved', PhotoTag::firstOrFail()->status);
        $this->assertSame('guardian', PhotoTag::firstOrFail()->approval_kind);
        $bytes = $service->bytes($this->parent, (int) $photo->id);
        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($bytes)[2]);
        $this->assertStringNotContainsString('sensitive-original-name', $photo->path);
        $this->assertStringNotContainsString($bytes, Storage::disk($service::DISK)->get($photo->path));
        $this->assertStringNotContainsString('Private photograph caption', DB::table('gallery_photos')->where('id', $photo->id)->value('caption'));
    }

    public function test_self_tag_requires_guardian_by_default_and_exact_revision_decision_is_idempotent(): void
    {
        $photo = $this->upload($this->student);
        $tag = PhotoTag::firstOrFail();
        $this->assertSame('proposed', $tag->status);
        $this->assertFalse(app(GalleryAuthority::class)->approved($tag));
        $request = $this->op(['revision' => $tag->revision, 'decision' => 'approve']);
        $service = app(GalleryTags::class);
        $approved = $service->decide($this->parent, (int) $tag->id, $request);
        $this->assertSame($approved->revision, $service->decide($this->parent, (int) $tag->id, $request)->revision);
        $this->assertTrue(app(GalleryAuthority::class)->approved($approved));
        $this->assertNotEmpty(app(GalleryPhotos::class)->bytes($this->student, (int) $photo->id));
    }

    public function test_tightening_school_rule_immediately_hides_self_approved_tag(): void
    {
        $settings = app(GallerySettings::class);
        $settings->save($this->superAdmin(), $this->op(['revision' => 0, 'guardian_required' => false]));
        $this->upload($this->student);
        $tag = PhotoTag::firstOrFail();
        $this->assertSame('self', $tag->approval_kind);
        $this->assertTrue(app(GalleryAuthority::class)->approved($tag));
        $settings->save($this->superAdmin(), $this->op(['revision' => 1, 'guardian_required' => true]));
        $this->assertFalse(app(GalleryAuthority::class)->approved($tag->fresh()));
        app(GalleryTags::class)->decide($this->parent, (int) $tag->id, $this->op(['revision' => $tag->revision, 'decision' => 'approve']));
        $this->assertTrue(app(GalleryAuthority::class)->approved($tag->fresh()));
    }

    public function test_staff_wrong_parent_other_school_and_ended_link_cannot_read_family_photo(): void
    {
        $photo = $this->upload();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $other = User::findOrFail($this->schoolA->ids['gina_user']);
        $other->forceFill(['email_verified_at' => now()])->save();
        $photos = app(GalleryPhotos::class);
        $this->refuse(404, fn () => $photos->bytes($teacher, (int) $photo->id));
        $this->refuse(404, fn () => $photos->bytes($other, (int) $photo->id));
        app(CurrentSchool::class)->run(2, fn () => $this->refuse(404, fn () => GalleryPhoto::findOrFail($photo->id)));
        GuardianLink::where('learner_id', $this->learner)->where('guardian_user_id', $this->parent->id)->update(['status' => 'ended']);
        $this->refuse(404, fn () => $photos->bytes($this->parent, (int) $photo->id));
    }

    public function test_removed_tag_cannot_be_reapproved_by_old_retry_and_stops_private_file_reads(): void
    {
        $photo = $this->upload();
        $tag = PhotoTag::firstOrFail();
        $service = app(GalleryTags::class);
        $request = $this->op(['revision' => $tag->revision]);
        $removed = $service->remove($this->student, (int) $tag->id, $request);
        $this->assertSame('removed', $removed->status);
        $this->assertSame($removed->revision, $service->remove($this->student, (int) $tag->id, $request)->revision);
        $this->refuse(404, fn () => app(GalleryPhotos::class)->bytes($this->parent, (int) $photo->id));
        $this->refuse(409, fn () => $service->decide($this->parent, (int) $tag->id, $this->op(['revision' => $tag->revision, 'decision' => 'approve'])));
    }

    public function test_scanner_outage_or_refusal_keeps_no_photo_or_tag(): void
    {
        $this->scanner->unavailable = true;
        $this->refuse(503, fn () => $this->upload());
        $this->scanner->unavailable = false;
        $this->scanner->clean = false;
        $this->refuse(422, fn () => $this->upload());
        $this->assertSame(0, GalleryPhoto::count());
        $this->assertSame(0, PhotoTag::count());
    }

    public function test_revoked_learner_approval_blocks_self_but_guardian_keeps_own_control(): void
    {
        $photo = $this->upload();
        LearnerApproval::where('learner_id', $this->learner)->update(['withdrawn_at' => now()]);
        $this->refuse(404, fn () => app(GalleryPhotos::class)->bytes($this->student, (int) $photo->id));
        $tag = PhotoTag::firstOrFail();
        $this->assertSame('removed', app(GalleryTags::class)->remove($this->parent, (int) $tag->id, $this->op(['revision' => $tag->revision]))->status);
    }

    public function test_missing_or_ended_guardian_approval_rights_cannot_decide_tag(): void
    {
        $this->upload($this->student);
        $tag = PhotoTag::firstOrFail();
        GuardianLink::where('learner_id', $this->learner)->where('guardian_user_id', $this->parent->id)->update(['can_approve' => false]);
        $this->refuse(404, fn () => app(GalleryTags::class)->decide($this->parent, (int) $tag->id, $this->op(['revision' => $tag->revision, 'decision' => 'approve'])));
        $this->assertSame('proposed', $tag->fresh()->status);
    }

    public function test_approved_and_pending_queries_filter_ownership_and_rule_before_counts(): void
    {
        $photo = $this->upload($this->student);
        $q = app(GalleryQueries::class);
        $this->assertSame(0, $q->family($this->parent, $this->learner)['total']);
        $this->assertSame(1, $q->family($this->parent, $this->learner, true)['total']);
        $this->refuse(403, fn () => $q->family(User::findOrFail($this->schoolA->ids['tara_user']), $this->learner));
        $tag = PhotoTag::firstOrFail();
        app(GalleryTags::class)->decide($this->parent, (int) $tag->id, $this->op(['revision' => $tag->revision, 'decision' => 'approve']));
        $result = $q->family($this->parent, $this->learner);
        $this->assertSame(1, $result['total']);
        $this->assertSame($photo->id, $result['items'][0]['photo']['id']);
        $this->assertArrayNotHasKey('path', $result['items'][0]['photo']);
        $this->assertSame(0, $q->family($this->parent, $this->learner, true)['total']);
    }

    public function test_current_group_staff_can_propose_by_approved_handle_but_departed_members_are_hidden(): void
    {
        $group = ActivityGroup::create(['session_year_id' => $this->year, 'name' => 'Synthetic photo group', 'discipline' => 'Sport', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true]);
        $coach = User::findOrFail($this->schoolA->ids['tara_user']);
        ActivityStaff::create(['activity_group_id' => $group->id, 'user_id' => $coach->id, 'role' => 'coach', 'cascade' => false, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic photo assignment', 'revision' => 1]);
        $membership = ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $this->learner, 'role' => 'member', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'active' => true, 'reason' => 'Synthetic photo membership', 'revision' => 1]);
        Enrolment::create(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'reason' => 'Synthetic gallery enrolment', 'legacy_dates' => false]);
        $this->apiAs($this->parent);
        $this->getJson('/api/v2/gallery/configuration')->assertOk()->assertJsonPath('data.groups.0.learner_ids', [$this->learner]);
        $this->apiAs($coach);
        $this->getJson('/api/v2/gallery/configuration')->assertOk()->assertJsonPath('data.groups.0.learner_ids', []);
        $this->student->forceFill(['handle' => 'asha.demo'])->save();
        $photo = $this->upload($coach, ['scope' => 'group', 'activity_group_id' => $group->id]);
        $this->apiAs($coach);
        $this->getJson('/api/v2/gallery/groups/'.$group->id.'/photos')->assertOk()->assertJsonPath('data.items.0.id', (int) $photo->id);
        $reference = $this->postJson('/api/v2/gallery/photos/'.$photo->id.'/tags', $this->op(['handle' => '@asha.demo']))->assertOk();
        $tag = PhotoTag::findOrFail($reference->json('data.id'));
        $this->assertSame($this->learner, (int) $tag->learner_id);
        $this->assertSame('proposed', $tag->status);
        $q = app(GalleryQueries::class);
        $this->assertSame(1, $q->family($this->parent, $this->learner, true)['total']);
        $this->apiAs($this->parent);
        $this->getJson('/api/v2/gallery/groups/'.$group->id.'/photos')->assertOk();
        SchoolMembership::where('user_id', $this->student->id)->where('role', 'Student')->update(['status' => 'inactive']);
        $this->getJson('/api/v2/gallery/groups/'.$group->id.'/photos')->assertNotFound()->assertDontSee('Private synthetic photograph');
        $this->getJson('/api/v2/gallery/configuration')->assertOk()->assertJsonPath('data.groups', []);
        SchoolMembership::where('user_id', $this->student->id)->where('role', 'Student')->update(['status' => 'active']);
        $this->apiAs($coach);
        $membership->update(['active' => false]);
        $this->assertSame(0, $q->family($this->parent, $this->learner, true)['total']);
        $this->refuse(404, fn () => app(GalleryPhotos::class)->bytes($this->parent, (int) $photo->id));
        $this->refuse(404, fn () => app(GalleryTags::class)->decide($this->parent, (int) $tag->id, $this->op(['revision' => $tag->revision, 'decision' => 'approve'])));
        $withdraw = $this->op(['revision' => $photo->revision]);
        $this->postJson('/api/v2/gallery/photos/'.$photo->id.'/withdraw', $withdraw)->assertOk();
        $this->postJson('/api/v2/gallery/photos/'.$photo->id.'/withdraw', $withdraw)->assertOk();
        $this->getJson('/api/v2/gallery/groups/'.$group->id.'/photos')->assertOk()->assertJsonPath('data.items', []);
    }

    public function test_module_withdrawal_and_impersonation_deny_before_private_caption_query(): void
    {
        $photo = $this->upload();
        config(['modules.gallery' => ['default' => false]]);
        DB::enableQueryLog();
        $this->refuse(403, fn () => app(GalleryPhotos::class)->bytes($this->parent, (int) $photo->id));
        $this->assertSame([], array_values(array_filter(array_column(DB::getQueryLog(), 'query'), fn ($sql) => str_contains($sql, 'from `gallery_photos`'))));
        DB::disableQueryLog();
        config(['modules.gallery' => ['always_on' => true]]);
        $scope = app(CurrentSchool::class);
        $scope->impersonate(School::findOrFail(1));
        try {
            $this->refuse(403, fn () => app(GalleryPhotos::class)->bytes($this->parent, (int) $photo->id));
        } finally {
            $scope->set(1);
        }
    }

    public function test_completed_transfer_listener_is_registered_once(): void
    {
        $this->assertCount(1, Event::getListeners(TransferCompleted::class));
    }

    private function transferFixture(): array
    {
        config(['modules.school_transfers' => ['always_on' => true]]);
        Storage::fake(TransferVault::DISK);
        Enrolment::firstOrCreate(['learner_id' => $this->learner, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a']], ['starts_on' => '2026-01-01', 'ends_on' => null, 'reason' => 'Synthetic photo transfer', 'legacy_dates' => false]);
        app(StudentNumbers::class)->assign(Students::findOrFail($this->learner));
        $receiver = app(CurrentSchool::class)->run(2, function (): User {
            $actor = User::create(['first_name' => 'Photo', 'last_name' => 'Receiver', 'email' => 'photo-receiver@example.test', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 1]);
            $actor->assignRole('Super Admin');
            SessionYear::whereKey($this->schoolB->ids['session_year'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
            ClassSection::with('class')->findOrFail($this->schoolB->ids['section_a'])->class->forceFill(['grade_level_id' => GradeLevel::where('code', '5')->value('id')])->save();

            return $actor;
        });
        $package = app(TransferPackages::class)->prepare($this->superAdmin(), $this->op(['learner_id' => $this->learner, 'guardian_id' => $this->parent->id, 'to_school_id' => 2, 'external_school' => null, 'last_attended_on' => '2026-02-09', 'starts_on' => '2026-02-10', 'report_ids' => [], 'document_ids' => []]));

        return [$package, $receiver];
    }

    private function transferConsent(TransferPackage $package, bool $keep = false, ?string $fingerprint = null): TransferPackage
    {
        return app(TransferPackages::class)->consent($this->parent, (int) $package->id, $this->op(['revision' => $package->revision, 'fingerprint' => $package->fingerprint, 'version' => config('school_transfers.consent_version'), 'confirmation' => true, 'keep_photo_tags' => $keep, 'gallery_fingerprint' => $fingerprint]));
    }

    private function acceptPhotoTransfer(TransferPackage $package, User $receiver): TransferReceipt
    {
        $released = app(TransferPackages::class)->release($this->superAdmin(), (int) $package->id, $this->op(['revision' => $package->revision]));

        return app(CurrentSchool::class)->run(2, fn () => app(TransferAccept::class)->accept($receiver, (int) $released->id, $this->op(['revision' => $released->revision, 'fingerprint' => $released->fingerprint, 'session_year_id' => $this->schoolB->ids['session_year'], 'class_section_id' => $this->schoolB->ids['section_a'], 'category_id' => $this->schoolB->ids['category'], 'confirmation' => true])));
    }

    public function test_completed_transfer_removes_tags_by_default_without_adding_photo_access(): void
    {
        $photo = $this->upload();
        [$package, $receiver] = $this->transferFixture();
        $package = $this->transferConsent($package);
        $this->acceptPhotoTransfer($package, $receiver);
        $this->assertSame('removed', PhotoTag::where('learner_id', $this->learner)->firstOrFail()->status);
        $this->assertSame(0, GalleryTransferChoice::count());
        $this->refuse(404, fn () => app(GalleryPhotos::class)->bytes($this->parent, (int) $photo->id));
        app(CurrentSchool::class)->run(2, fn () => $this->assertSame(0, GalleryPhoto::count()));
    }

    public function test_exact_guardian_choice_keeps_only_consented_pixels_in_receiving_family_without_activation(): void
    {
        $photo = $this->upload();
        $original = app(GalleryPhotos::class)->bytes($this->parent, (int) $photo->id);
        [$package, $receiver] = $this->transferFixture();
        $preview = app(GalleryTransfers::class)->preview($this->parent, $package);
        $this->assertSame(1, $preview['photos']);
        $this->assertTrue($preview['can_keep']);
        $package = $this->transferConsent($package, true, $preview['fingerprint']);
        $choice = GalleryTransferChoice::firstOrFail();
        $this->assertStringNotContainsString('tag_id', DB::table('gallery_transfer_choices')->where('id', $choice->id)->value('selection'));
        $receipt = $this->acceptPhotoTransfer($package, $receiver);
        $this->assertTrue(PhotoTag::where('learner_id', $this->learner)->firstOrFail()->retained_on_transfer);
        $this->assertSame($original, app(GalleryPhotos::class)->bytes($this->parent, (int) $photo->id));
        app(CurrentSchool::class)->run(2, function () use ($receipt, $original, $photo): void {
            $copy = GalleryPhoto::firstOrFail();
            $this->assertSame('family', $copy->scope);
            $this->assertNull($copy->activity_group_id);
            $this->assertSame((int) $receipt->learner_id, (int) $copy->owner_learner_id);
            $this->assertSame($original, app(GalleryPhotos::class)->bytes($this->parent, (int) $copy->id));
            $this->refuse(404, fn () => app(GalleryPhotos::class)->bytes($this->parent, (int) $photo->id));
            $this->assertSame(0, LearnerApproval::where('learner_id', $receipt->learner_id)->count());
            $this->refuse(404, fn () => app(GalleryPhotos::class)->bytes($this->student, (int) $copy->id));
        });
    }

    public function test_changed_photo_selection_invalidates_keep_consent_before_release(): void
    {
        $this->upload();
        [$package] = $this->transferFixture();
        $preview = app(GalleryTransfers::class)->preview($this->parent, $package);
        $package = $this->transferConsent($package, true, $preview['fingerprint']);
        $tag = PhotoTag::where('learner_id', $this->learner)->firstOrFail();
        app(GalleryTags::class)->remove($this->student, (int) $tag->id, $this->op(['revision' => $tag->revision]));
        $this->refuse(409, fn () => app(TransferPackages::class)->release($this->superAdmin(), (int) $package->id, $this->op(['revision' => $package->revision])));
        $this->assertSame('consented', $package->fresh()->state);
        $this->assertSame('removed', $tag->fresh()->status);
    }

    public function test_untag_erasure_continues_with_module_disabled_and_never_erases_other_school_bytes(): void
    {
        $photo = $this->upload();
        $disk = Storage::disk(GalleryPhotos::DISK);
        $foreign = app(CurrentSchool::class)->run(2, fn () => app(CurrentSchool::class)->storagePath('gallery/foreign/photo.enc'));
        $disk->put($foreign, 'foreign encrypted bytes');
        $tag = PhotoTag::firstOrFail();
        app(GalleryTags::class)->remove($this->parent, (int) $tag->id, $this->op(['revision' => $tag->revision]));
        $this->assertFalse($photo->fresh()->active);
        $disk->assertExists($photo->path);
        config(['modules.gallery' => ['default' => false]]);
        app(GalleryRetention::class)->prune();
        $disk->assertMissing($photo->path);
        $disk->assertMissing($photo->thumbnail_path);
        $disk->assertExists($foreign);
        $this->assertNotNull($photo->fresh()->erased_at);
        app(GalleryRetention::class)->prune();
        $this->assertSame(1, GalleryEvent::where('kind', 'photo_bytes_erased')->count());
    }

    public function test_orphan_erasure_preserves_both_known_variants_recent_and_foreign_files(): void
    {
        $photo = $this->upload();
        $disk = Storage::disk(GalleryPhotos::DISK);
        $prefix = app(CurrentSchool::class)->storagePath('gallery');
        $old = $prefix.'/orphan/photo.enc';
        $fresh = $prefix.'/fresh/photo.enc';
        $foreign = app(CurrentSchool::class)->run(2, fn () => app(CurrentSchool::class)->storagePath('gallery/foreign/photo.enc'));
        foreach ([$old, $fresh, $foreign] as $path) {
            $disk->put($path, 'synthetic encrypted bytes');
        }
        foreach ([$old, $foreign, $photo->path, $photo->thumbnail_path] as $path) {
            touch($disk->path($path), now()->subDays(3)->getTimestamp());
        }
        $this->assertSame(1, app(GalleryRetention::class)->orphans());
        $disk->assertMissing($old);
        foreach ([$fresh, $foreign, $photo->path, $photo->thumbnail_path] as $path) {
            $disk->assertExists($path);
        }
    }

    public function test_default_transfer_erases_unlinked_source_pixels_without_touching_destination(): void
    {
        $photo = $this->upload();
        [$package, $receiver] = $this->transferFixture();
        $package = $this->transferConsent($package);
        $this->acceptPhotoTransfer($package, $receiver);
        app(GalleryRetention::class)->prune();
        Storage::disk(GalleryPhotos::DISK)->assertMissing($photo->path);
        $this->assertNotNull($photo->fresh()->erased_at);
        app(CurrentSchool::class)->run(2, fn () => $this->assertSame(0, GalleryPhoto::count()));
    }

    private function apiAs(User $actor): void
    {
        Sanctum::actingAs($actor);
        $this->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id, 'Accept' => 'application/json']);
    }

    public function test_http_upload_pending_guardian_decision_private_pixels_and_removal_round_trip(): void
    {
        $this->apiAs($this->student);
        $request = $this->op(['scope' => 'family', 'learner_id' => $this->learner, 'session_year_id' => $this->year, 'captured_on' => '2026-02-09', 'caption' => 'Private API photo']);
        $result = $this->post('/api/v2/gallery/photos', $request + ['photo' => UploadedFile::fake()->image('private-name.jpg', 30, 20)])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $photo = (int) $result->json('data.id');
        $this->getJson('/api/v2/gallery/learners/'.$this->learner.'/photos')->assertOk()->assertJsonPath('data.total', 0);
        $this->apiAs($this->parent);
        $this->getJson('/api/v2/gallery/configuration')->assertOk()->assertJsonPath('data.guardian_required', true)->assertJsonPath('data.children.0.id', $this->learner);
        $pending = $this->getJson('/api/v2/gallery/learners/'.$this->learner.'/photos?pending=1')->assertOk()->assertJsonPath('data.total', 1);
        $item = $pending->json('data.items.0');
        $this->assertArrayNotHasKey('path', $item['photo']);
        $this->get('/api/v2/gallery/photos/'.$photo.'/content?thumbnail=1')->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
        $decision = $this->op(['revision' => $item['tag_revision'], 'decision' => 'approve']);
        $approved = $this->postJson('/api/v2/gallery/tags/'.$item['tag_id'].'/decision', $decision)->assertOk();
        $this->postJson('/api/v2/gallery/tags/'.$item['tag_id'].'/decision', $decision)->assertOk()->assertJsonPath('data.revision', $approved->json('data.revision'));
        $this->getJson('/api/v2/gallery/learners/'.$this->learner.'/photos')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.items.0.photo.caption', 'Private API photo');
        $this->postJson('/api/v2/gallery/tags/'.$item['tag_id'].'/remove', $this->op(['revision' => $approved->json('data.revision')]))->assertOk();
        $this->get('/api/v2/gallery/photos/'.$photo.'/content')->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(1, GalleryPhoto::count());
    }

    public function test_http_wrong_role_owner_school_and_account_refuse_before_caption_or_storage_retrieval(): void
    {
        $photo = $this->upload();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->apiAs($teacher);
        DB::enableQueryLog();
        $this->get('/api/v2/gallery/photos/'.$photo->id.'/content')->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v2/gallery/learners/'.$this->learner.'/photos')->assertForbidden()->assertDontSee('Private synthetic photograph');
        $this->postJson('/api/v2/gallery/tags/'.PhotoTag::firstOrFail()->id.'/decision', [])->assertForbidden();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();
        $this->assertSame([], array_values(array_filter($queries, fn ($sql) => str_starts_with(strtolower($sql), 'select * from `gallery_photos`'))));
        $this->apiAs($this->parent);
        $this->withHeader('X-School-Id', '2')->get('/api/v2/gallery/photos/'.$photo->id.'/content')->assertForbidden()->assertDontSee('Private synthetic photograph');
        $this->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $teacher->id])->getJson('/api/v2/gallery/configuration')->assertForbidden();
    }

    public function test_http_settings_require_current_manager_and_replay_never_bypasses_module_revocation(): void
    {
        $admin = $this->superAdmin();
        $this->apiAs($this->parent);
        $this->postJson('/api/v2/gallery/settings', [])->assertForbidden();
        $this->apiAs($admin);
        $settings = $this->op(['revision' => 0, 'guardian_required' => false]);
        $this->postJson('/api/v2/gallery/settings', $settings)->assertOk()->assertJsonPath('data.guardian_required', false)->assertJsonPath('data.revision', 1);
        $this->postJson('/api/v2/gallery/settings', $settings)->assertOk()->assertJsonPath('data.revision', 1);
        config(['modules.gallery' => ['default' => false]]);
        $this->postJson('/api/v2/gallery/settings', $settings)->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertFalse(app(GallerySettings::class)->current()->guardian_required);
    }

    public function test_http_transfer_choice_is_own_guardian_only_and_optional_retention_reaches_completion(): void
    {
        $this->upload();
        [$package, $receiver] = $this->transferFixture();
        $this->apiAs($this->parent);
        $result = $this->getJson('/api/v2/transfers/consents/'.$package->id)->assertOk()->assertJsonPath('data.gallery_choice.photos', 1)->assertJsonPath('data.gallery_choice.can_keep', true);
        $choice = $result->json('data.gallery_choice.fingerprint');
        $this->postJson('/api/v2/transfers/packages/'.$package->id.'/consent', $this->op(['revision' => $package->revision, 'fingerprint' => $package->fingerprint, 'version' => config('school_transfers.consent_version'), 'confirmation' => true, 'keep_photo_tags' => true, 'gallery_fingerprint' => $choice]))->assertOk();
        $this->assertSame(1, GalleryTransferChoice::count());
        $this->acceptPhotoTransfer($package->fresh(), $receiver);
        $this->assertTrue(PhotoTag::where('learner_id', $this->learner)->firstOrFail()->retained_on_transfer);
        app(CurrentSchool::class)->run(2, fn () => $this->assertSame(1, GalleryPhoto::count()));
    }

    public function test_self_can_read_and_remove_own_pending_tag_without_guardian_approval_power(): void
    {
        $this->upload($this->student);
        $tag = PhotoTag::firstOrFail();
        $this->apiAs($this->student);
        $this->getJson('/api/v2/gallery/learners/'.$this->learner.'/photos?pending=true')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.items.0.tag_id', (int) $tag->id);
        $this->postJson('/api/v2/gallery/tags/'.$tag->id.'/decision', $this->op(['revision' => $tag->revision, 'decision' => 'approve']))->assertNotFound();
        $this->postJson('/api/v2/gallery/tags/'.$tag->id.'/remove', $this->op(['revision' => $tag->revision]))->assertOk();
        $this->assertSame('removed', $tag->fresh()->status);
        $this->getJson('/api/v2/gallery/learners/'.$this->learner.'/photos?pending=true')->assertOk()->assertJsonPath('data.total', 0);
    }

    public function test_existing_pending_tag_self_consent_requires_explicit_school_rule_and_fresh_replay_authority(): void
    {
        $this->upload($this->student);
        $tag = PhotoTag::firstOrFail();
        $settings = app(GallerySettings::class);
        $settings->save($this->superAdmin(), $this->op(['revision' => 0, 'guardian_required' => false]));
        $this->apiAs($this->student);
        $this->getJson('/api/v2/gallery/configuration')->assertOk()->assertJsonPath('data.children.0.can_approve', true);
        $decision = $this->op(['revision' => $tag->revision, 'decision' => 'approve']);
        $this->postJson('/api/v2/gallery/tags/'.$tag->id.'/decision', $decision)->assertOk();
        $this->assertSame('self', $tag->fresh()->approval_kind);
        $settings->save($this->superAdmin(), $this->op(['revision' => 1, 'guardian_required' => true]));
        $this->getJson('/api/v2/gallery/configuration')->assertOk()->assertJsonPath('data.children.0.can_approve', false);
        $this->getJson('/api/v2/gallery/learners/'.$this->learner.'/photos')->assertOk()->assertJsonPath('data.total', 0);
        $this->getJson('/api/v2/gallery/learners/'.$this->learner.'/photos?pending=true')->assertOk()->assertJsonPath('data.total', 1);
        $this->postJson('/api/v2/gallery/tags/'.$tag->id.'/decision', $decision)->assertNotFound();
        $this->apiAs($this->parent);
        $this->postJson('/api/v2/gallery/tags/'.$tag->id.'/decision', $this->op(['revision' => $tag->fresh()->revision, 'decision' => 'approve']))->assertOk();
        $this->assertSame('guardian', $tag->fresh()->approval_kind);
    }
}
