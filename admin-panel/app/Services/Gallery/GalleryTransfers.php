<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\GalleryTransferChoice;
use App\Models\Gallery\PhotoTag;
use App\Models\Platform\GuardianLink;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Transfers\TransferConsent;
use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferReceipt;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GalleryTransfers
{
    private function selection(User $guardian, TransferPackage $package, bool $write = false): array
    {
        app(GalleryAuthority::class)->family($guardian, (int) $package->learner_id, $write, true);
        $tags = app(GalleryQueries::class)->ownedTags($guardian, (int) $package->learner_id)->orderBy('id')->limit(201)->get();
        abort_if($tags->count() > 200, 422, 'Review a smaller private photo collection before keeping it in a transfer.');
        $items = [];
        foreach ($tags as $tag) {
            $photo = GalleryPhoto::where('active', true)->findOrFail($tag->gallery_photo_id);
            $items[] = ['tag_id' => (int) $tag->id, 'tag_revision' => (int) $tag->revision, 'photo_id' => (int) $photo->id, 'photo_revision' => (int) $photo->revision, 'sha256' => $photo->sha256, 'thumbnail_sha256' => $photo->thumbnail_sha256];
        }

        return $items;
    }

    private function fingerprint(User $guardian, TransferPackage $package, array $selection): string
    {
        return hash_hmac('sha256', json_encode([(int) $package->school_id, $package->public_id, $package->fingerprint, (int) $package->learner_id, (int) $guardian->id, $selection], JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public function preview(User $guardian, TransferPackage $package): array
    {
        abort_unless((int) $package->school_id === app(CurrentSchool::class)->id() && (int) $package->guardian_id === (int) $guardian->id, 404);
        $items = $this->selection($guardian, $package);
        app(ModuleGate::class)->forget();

        return ['fingerprint' => $this->fingerprint($guardian, $package, $items), 'photos' => count($items), 'can_keep' => $items !== [] && app(ModuleGate::class)->writable('gallery')];
    }

    public function freeze(User $guardian, TransferPackage $package, TransferConsent $consent, string $fingerprint): void
    {
        abort_unless(DB::transactionLevel() > 0 && (int) $consent->transfer_package_id === (int) $package->id && (int) $consent->guardian_id === (int) $guardian->id && $consent->decision === 'approve', 409);
        $items = $this->selection($guardian, $package, true);
        abort_unless($items !== [] && hash_equals($this->fingerprint($guardian, $package, $items), $fingerprint), 409, 'The private photo selection changed. Review your transfer choice again.');
        GalleryTransferChoice::create(['transfer_consent_id' => $consent->id, 'guardian_id' => $guardian->id, 'learner_id' => $package->learner_id, 'fingerprint' => $fingerprint, 'selection' => $items]);
    }

    private function choice(TransferPackage $package): ?GalleryTransferChoice
    {
        $consent = TransferConsent::where('transfer_package_id', $package->id)->where('decision', 'approve')->orderByDesc('id')->first(['id']);

        return $consent === null ? null : GalleryTransferChoice::where('transfer_consent_id', $consent->id)->first();
    }

    public function current(TransferPackage $package): void
    {
        if ($package->accepted_at !== null || ($package->to_school_id === null && $package->released_at !== null)) {
            return; // Academic archive access never grants fresh gallery file access.
        }
        $choice = $this->choice($package);
        if ($choice === null) {
            return; // Compatible old consent means remove, never implicit retention.
        }
        $guardian = User::whereKey($package->guardian_id)->firstOrFail();
        $items = $this->selection($guardian, $package);
        abort_unless((int) $choice->guardian_id === (int) $guardian->id && hash_equals($choice->fingerprint, $this->fingerprint($guardian, $package, $items)), 409, 'The kept photo selection changed. Obtain fresh transfer consent.');
    }

    /** Called atomically before the completed departure ends learner membership. */
    public function depart(TransferPackage $package): void
    {
        abort_unless(DB::transactionLevel() > 0 && (int) $package->school_id === app(CurrentSchool::class)->id(), 403);
        $this->current($package);
        $choice = $this->choice($package);
        $kept = $choice === null ? [] : array_column($choice->selection, 'tag_id');
        $guardian = $choice === null ? null : User::findOrFail($choice->guardian_id);
        foreach (PhotoTag::where('learner_id', $package->learner_id)->whereIn('status', ['proposed', 'approved'])->orderBy('id')->lockForUpdate()->get() as $tag) {
            $retain = in_array((int) $tag->id, $kept, true);
            if ($retain) {
                $item = collect($choice->selection)->firstWhere('tag_id', (int) $tag->id);
                $photo = GalleryPhoto::where('active', true)->lockForUpdate()->findOrFail($item['photo_id']);
                abort_unless((int) $tag->gallery_photo_id === (int) $photo->id && (int) $tag->revision === (int) $item['tag_revision'] && (int) $photo->revision === (int) $item['photo_revision'] && hash_equals($photo->sha256, $item['sha256']) && hash_equals($photo->thumbnail_sha256, $item['thumbnail_sha256']), 409, 'The kept photograph changed. Obtain fresh consent.');
            }
            $tag->fill(['status' => $retain ? 'approved' : 'removed', 'retained_on_transfer' => $retain, 'revision' => $tag->revision + 1]);
            if ($retain) {
                $tag->fill(['approval_kind' => 'guardian', 'approved_by' => $guardian->id, 'approved_at' => now()]);
            }
            $tag->save();
            app(GalleryEvents::class)->record($retain ? 'transfer_tag_retained' : 'transfer_tag_removed', $guardian, tag: $tag);
        }
    }

    /** Copy only explicitly consented pixels into the exact receiving family scope. */
    public function arrive(TransferPackage $package, TransferReceipt $receipt, int $yearId): void
    {
        $target = app(CurrentSchool::class)->id();
        abort_unless(DB::transactionLevel() > 0 && (int) $package->to_school_id === $target && (int) $receipt->school_id === $target && (int) $receipt->transfer_package_id === (int) $package->id, 403);
        $learner = Students::findOrFail($receipt->learner_id);
        abort_unless((int) $learner->user_id === (int) $package->contents['learner_user_id'] && GuardianLink::where('learner_id', $learner->id)->where('guardian_user_id', $package->guardian_id)->where('status', 'active')->exists(), 409);
        $year = SessionYear::findOrFail($yearId);
        $selection = app(CurrentSchool::class)->run((int) $package->school_id, function () use ($package): array {
            $choice = $this->choice($package);

            return $choice === null ? [] : $choice->selection;
        });
        foreach ($selection as $item) {
            // At most one clean image and thumbnail are resident at a time.
            $copy = app(CurrentSchool::class)->run((int) $package->school_id, function () use ($package, $item): array {
                $choice = $this->choice($package);
                abort_unless($choice !== null, 409);
                $tag = PhotoTag::where('learner_id', $package->learner_id)->where('status', 'approved')->where('retained_on_transfer', true)->where('approved_by', $choice->guardian_id)->lockForUpdate()->findOrFail($item['tag_id']);
                $photo = GalleryPhoto::where('active', true)->lockForUpdate()->findOrFail($item['photo_id']);
                abort_unless((int) $tag->gallery_photo_id === (int) $photo->id && (int) $tag->revision === (int) $item['tag_revision'] + 1 && (int) $photo->revision === (int) $item['photo_revision'] && hash_equals($photo->sha256, $item['sha256']) && hash_equals($photo->thumbnail_sha256, $item['thumbnail_sha256']), 409, 'The private transfer photo changed.');

                return ['photo' => $photo, 'bytes' => app(GalleryPhotos::class)->read($photo), 'thumbnail' => app(GalleryPhotos::class)->read($photo, true)];
            });
            $photo = $copy['photo'];
            $uuid = (string) Str::uuid();
            $prefix = app(CurrentSchool::class)->storagePath('gallery/'.$uuid);
            if (! Storage::disk(GalleryPhotos::DISK)->put($prefix.'/photo.enc', Crypt::encryptString($copy['bytes'])) || ! Storage::disk(GalleryPhotos::DISK)->put($prefix.'/thumbnail.enc', Crypt::encryptString($copy['thumbnail']))) {
                throw new RuntimeException('Private photo transfer storage is temporarily unavailable.');
            }
            $received = GalleryPhoto::create(['public_id' => $uuid, 'session_year_id' => $year->id, 'activity_group_id' => null, 'owner_learner_id' => $learner->id, 'uploaded_by' => $package->guardian_id,
                'scope' => 'family', 'caption' => $photo->caption, 'captured_on' => $photo->captured_on->toDateString(), 'year_label' => $photo->year_label, 'path' => $prefix.'/photo.enc', 'thumbnail_path' => $prefix.'/thumbnail.enc',
                'bytes' => strlen($copy['bytes']), 'thumbnail_bytes' => strlen($copy['thumbnail']), 'sha256' => $photo->sha256, 'thumbnail_sha256' => $photo->thumbnail_sha256, 'width' => $photo->width, 'height' => $photo->height, 'scanner' => $photo->scanner, 'scanned_at' => $photo->scanned_at, 'active' => true, 'revision' => 1]);
            $tag = PhotoTag::create(['gallery_photo_id' => $received->id, 'learner_id' => $learner->id, 'tagged_by' => $package->guardian_id, 'status' => 'approved', 'approval_kind' => 'guardian', 'approved_by' => $package->guardian_id, 'approved_at' => now(), 'retained_on_transfer' => true, 'revision' => 1]);
            app(GalleryEvents::class)->record('transfer_photo_received', tag: $tag);
        }
    }
}
