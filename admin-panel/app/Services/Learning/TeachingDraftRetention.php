<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TeachingDraft;
use App\Models\Learning\TeachingDraftPhoto;
use App\Models\Learning\TeachingDraftVersion;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Storage;

/** Temporary feature copies only; human-approved official marks/curriculum and metering are retained. */
class TeachingDraftRetention
{
    public function purge(TeachingDraft $draft): void
    {
        abort_unless((int) $draft->getRawOriginal('school_id') === app(CurrentSchool::class)->id(), 403);
        foreach (TeachingDraftPhoto::where('author_id', $draft->author_id)->where('operation_id', $draft->operation_id)->get() as $photo) {
            $this->photo($photo);
        }
        TeachingDraftVersion::where('teaching_draft_id', $draft->id)->update(['definition' => null]);
        $draft->update(['request' => null, 'source_manifest' => null, 'context_text' => null, 'state' => 'purged', 'purged_at' => $draft->purged_at ?? now('UTC')]);
    }

    public function photo(TeachingDraftPhoto $photo): void
    {
        abort_unless((int) $photo->getRawOriginal('school_id') === app(CurrentSchool::class)->id(), 403);
        $path = app(CurrentSchool::class)->storagePath('teaching-draft-photos').'/'.$photo->id.'.enc';
        abort_unless($photo->path === $path, 404);
        Storage::disk(TeachingDraftPhotos::DISK)->delete($path);
        $photo->update(['crop' => [], 'purged_at' => $photo->purged_at ?? now('UTC'), 'revoked_at' => $photo->revoked_at ?? now('UTC')]);
    }

    public function prune(): int
    {
        $count = 0;
        foreach (TeachingDraft::whereNull('purged_at')->where('created_at', '<=', now('UTC')->subDays(90))->limit(100)->get() as $draft) {
            $this->purge($draft);
            $count++;
        }
        $photos = TeachingDraftPhoto::whereNull('purged_at')->where(fn ($q) => $q->where('created_at', '<=', now('UTC')->subDays(90))
            ->orWhere(fn ($unused) => $unused->where('created_at', '<=', now('UTC')->subDays(7))->whereNotExists(fn ($draft) => $draft->selectRaw('1')->from('teaching_drafts')
                ->whereColumn('teaching_drafts.school_id', 'teaching_draft_photos.school_id')->whereColumn('teaching_drafts.author_id', 'teaching_draft_photos.author_id')
                ->whereColumn('teaching_drafts.operation_id', 'teaching_draft_photos.operation_id'))))->limit(100)->get();
        foreach ($photos as $photo) {
            $this->photo($photo);
            $count++;
        }
        // A process can stop after encrypting a derivative but before committing
        // its row. Reconcile only this school's expired, recognized filenames.
        $disk = Storage::disk(TeachingDraftPhotos::DISK);
        $prefix = app(CurrentSchool::class)->storagePath('teaching-draft-photos');
        $expired = now('UTC')->subDays(7)->getTimestamp();
        $orphans = 0;
        foreach ($disk->files($prefix) as $path) {
            if ($orphans >= 100) {
                break;
            }
            $name = basename($path);
            if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.enc$/D', $name)
                || $disk->lastModified($path) > $expired || TeachingDraftPhoto::whereKey(substr($name, 0, -4))->exists()) {
                continue;
            }
            $disk->delete($path);
            $orphans++;
            $count++;
        }

        return $count;
    }
}
