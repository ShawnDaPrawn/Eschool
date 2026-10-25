<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\Transfers\TransferPackage;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GalleryRetention
{
    private function context(): string
    {
        $s = app(CurrentSchool::class);
        abort_unless($s->isSet() && ! $s->isAcrossSchools() && ! $s->isImpersonating(), 403);

        return rtrim($s->storagePath('gallery'), '/').'/';
    }

    public function prune(): void
    {
        $prefix = $this->context();
        $disk = Storage::disk(GalleryPhotos::DISK);
        // Future-dated moves may finish before departure. Remove any later,
        // unconsented tags when that departure has actually taken effect.
        TransferPackage::where(fn ($q) => $q->where('state', 'accepted')->orWhere(fn ($external) => $external->where('state', 'released')->whereNull('to_school_id')))
            ->whereExists(fn ($departure) => $departure->selectRaw('1')->from('academic_transitions')->whereColumn('academic_transitions.operation_id', 'transfer_packages.public_id')->where('academic_transitions.school_id', app(CurrentSchool::class)->id())->where('academic_transitions.kind', 'leave'))
            ->whereDate('last_attended_on', '<', now()->toDateString())->select(['id', 'learner_id', 'last_attended_on'])->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $package) {
                    DB::transaction(function () use ($package): void {
                        School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                        $learner = Students::select(['id', 'user_id'])->lockForUpdate()->find($package->learner_id);
                        if ($learner === null || app(GalleryQueries::class)->currentLearner($learner)) {
                            return;
                        }
                        // A recorded return remains a separate episode after
                        // suspension, later departure or school deactivation.
                        $last = $package->last_attended_on->toDateString();
                        if (Enrolment::withTrashed()->where('learner_id', $learner->id)->whereDate('starts_on', '>', $last)->exists()
                            || SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('user_id', $learner->user_id)->where('role', 'Student')->whereDate('starts_on', '>', $last)->exists()) {
                            return;
                        }
                        foreach (PhotoTag::where('learner_id', $package->learner_id)->where('retained_on_transfer', false)->whereIn('status', ['proposed', 'approved'])->orderBy('id')->lockForUpdate()->get() as $tag) {
                            $tag->update(['status' => 'removed', 'revision' => $tag->revision + 1]);
                            app(GalleryEvents::class)->record('departure_tag_removed', tag: $tag);
                        }
                    });
                }
            });
        GalleryPhoto::where('scope', 'family')->where('active', true)->whereNotExists(fn ($tags) => $tags->selectRaw('1')->from('photo_tags')->whereColumn('photo_tags.gallery_photo_id', 'gallery_photos.id')->where('photo_tags.school_id', app(CurrentSchool::class)->id())->whereIn('photo_tags.status', ['proposed', 'approved']))
            ->select(['id'])->orderBy('id')->chunkById(100, function ($rows): void {
                foreach ($rows as $candidate) {
                    DB::transaction(function () use ($candidate): void {
                        $row = GalleryPhoto::lockForUpdate()->findOrFail($candidate->id);
                        if ($row->active && ! PhotoTag::where('gallery_photo_id', $row->id)->whereIn('status', ['proposed', 'approved'])->exists()) {
                            $row->update(['active' => false, 'revision' => $row->revision + 1]);
                            app(GalleryEvents::class)->record('family_photo_unlinked', photo: $row);
                        }
                    });
                }
            });
        GalleryPhoto::where('active', false)->whereNull('erased_at')->select(['id'])->orderBy('id')->chunkById(100, function ($rows) use ($prefix, $disk): void {
            foreach ($rows as $candidate) {
                DB::transaction(function () use ($candidate, $prefix, $disk): void {
                    $row = GalleryPhoto::where('active', false)->whereNull('erased_at')->lockForUpdate()->find($candidate->id);
                    if ($row === null) {
                        return;
                    }
                    foreach ([$row->path, $row->thumbnail_path] as $path) {
                        if (! str_starts_with($path, $prefix) || str_contains($path, '..')) {
                            throw new RuntimeException('Private gallery erasure needs review.');
                        }
                        if ($disk->exists($path) && ! $disk->delete($path)) {
                            throw new RuntimeException('Private photo erasure is temporarily unavailable.');
                        }
                    }
                    $row->update(['erased_at' => now(), 'revision' => $row->revision + 1]);
                    app(GalleryEvents::class)->record('photo_bytes_erased', photo: $row);
                });
            }
        });
        $this->orphans();
    }

    /** Bounded streaming erasure of bytes left by enclosing transaction rollback. */
    public function orphans(): int
    {
        $prefix = rtrim($this->context(), '/');
        $disk = Storage::disk(GalleryPhotos::DISK);
        $cutoff = now()->subDays(2)->getTimestamp();
        $batch = [];
        $removed = 0;
        $clean = function (array $entries) use ($disk, $cutoff, &$removed): void {
            $paths = array_column($entries, 'path');
            $known = [];
            foreach (GalleryPhoto::where(fn ($q) => $q->whereIn('path', $paths)->orWhereIn('thumbnail_path', $paths))->get(['path', 'thumbnail_path']) as $photo) {
                $known[$photo->path] = $known[$photo->thumbnail_path] = true;
            }
            foreach ($entries as $entry) {
                if ($removed >= 1000) {
                    break;
                }
                if (! isset($known[$entry['path']]) && $entry['modified'] !== null && $entry['modified'] < $cutoff) {
                    if (! $disk->delete($entry['path'])) {
                        throw new RuntimeException('Private photo orphan erasure is temporarily unavailable.');
                    }
                    $removed++;
                }
            }
        };
        foreach ($disk->getDriver()->listContents($prefix, true) as $entry) {
            if (! $entry->isFile() || ! str_starts_with($entry->path(), $prefix.'/') || str_contains($entry->path(), '..')) {
                continue;
            }
            $batch[] = ['path' => $entry->path(), 'modified' => $entry->lastModified()];
            if (count($batch) === 100) {
                $clean($batch);
                $batch = [];
            }
            if ($removed >= 1000) {
                break;
            }
        }
        if ($batch !== []) {
            $clean($batch);
        }

        return $removed;
    }
}
