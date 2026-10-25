<?php

declare(strict_types=1);

namespace Tests\Feature\Gallery;

use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Gallery\GalleryPhotos;
use App\Services\Gallery\GalleryRetention;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Transfers\TransferPackages;
use App\Services\Transfers\TransferVault;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class GalleryReturnRetentionTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10T10:00:00+02:00'));
        config(['modules.gallery' => ['always_on' => true], 'modules.school_transfers' => ['always_on' => true]]);
        SchoolMembership::query()->update(['starts_on' => '2026-01-01', 'ends_on' => null]);
        Storage::fake(GalleryPhotos::DISK);
        Storage::fake(TransferVault::DISK);
        $this->app->instance(UploadScanner::class, new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-clean', 'signature' => null];
            }
        });
    }

    private function op(array $data = []): array
    {
        return ['operation_id' => (string) Str::uuid()] + $data;
    }

    private function photo(User $parent, int $learner, int $year): GalleryPhoto
    {
        return app(GalleryPhotos::class)->upload($parent, $this->op(['scope' => 'family', 'learner_id' => $learner, 'session_year_id' => $year, 'captured_on' => now()->subDay()->toDateString(), 'caption' => 'Synthetic episode photograph']), UploadedFile::fake()->image('synthetic.jpg', 30, 20));
    }

    public static function inactiveReturnStates(): array
    {
        return [['suspended'], ['ended'], ['school_disabled']];
    }

    #[DataProvider('inactiveReturnStates')]
    public function test_old_completed_departure_never_erases_the_later_return_episode(string $state): void
    {
        $learner = (int) $this->schoolA->ids['asha'];
        $year = (int) $this->schoolA->ids['session_year'];
        $student = User::findOrFail($this->schoolA->ids['asha_user']);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $parent->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $learner, 'guardian_user_id' => $parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        SessionYear::whereKey($year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Enrolment::create(['learner_id' => $learner, 'session_year_id' => $year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false]);
        app(StudentNumbers::class)->assign(Students::findOrFail($learner));
        $original = $this->photo($parent, $learner, $year);
        $transfers = app(TransferPackages::class);
        $package = $transfers->prepare($this->superAdmin(), $this->op(['learner_id' => $learner, 'guardian_id' => $parent->id, 'to_school_id' => null, 'external_school' => 'Synthetic external school', 'last_attended_on' => '2026-02-09', 'starts_on' => '2026-02-10', 'report_ids' => [], 'document_ids' => []]));
        $package = $transfers->consent($parent, (int) $package->id, $this->op(['revision' => $package->revision, 'fingerprint' => $package->fingerprint, 'version' => config('school_transfers.consent_version'), 'confirmation' => true]));
        $transfers->release($this->superAdmin(), (int) $package->id, $this->op(['revision' => $package->revision]));
        $this->travelTo(CarbonImmutable::parse('2026-02-11T10:00:00+02:00'));
        SchoolMembership::where('user_id', $student->id)->where('role', 'Student')->update(['starts_on' => '2026-02-11', 'ends_on' => null, 'status' => 'active']);
        $returned = app(EnrolmentService::class)->place($learner, (int) $this->schoolA->ids['section_a'], $year, '2026-02-11', 'Synthetic return episode');
        $photo = $this->photo($parent, $learner, $year);
        $tag = PhotoTag::where('gallery_photo_id', $photo->id)->firstOrFail();
        if ($state === 'suspended') {
            SchoolMembership::where('user_id', $student->id)->where('role', 'Student')->update(['status' => 'suspended']);
        } elseif ($state === 'ended') {
            $returned->update(['ends_on' => '2026-02-12']);
            SchoolMembership::where('user_id', $student->id)->where('role', 'Student')->update(['ends_on' => '2026-02-12']);
            $this->travelTo(CarbonImmutable::parse('2026-02-13T10:00:00+02:00'));
        } else {
            School::whereKey(1)->update(['status' => 'inactive']);
        }
        app(GalleryRetention::class)->prune();
        $this->assertSame('approved', $tag->fresh()->status);
        $this->assertTrue($photo->fresh()->active);
        $this->assertNull($photo->fresh()->erased_at);
        $disk = Storage::disk(GalleryPhotos::DISK);
        $disk->assertExists($photo->path);
        $disk->assertExists($photo->thumbnail_path);
        $disk->assertMissing($original->path);
    }
}
