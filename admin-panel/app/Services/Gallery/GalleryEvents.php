<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Gallery\GalleryEvent;
use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Models\User;
use App\Services\Platform\Audit\Audit;

class GalleryEvents
{
    public function record(string $kind, ?User $actor = null, ?GalleryPhoto $photo = null, ?PhotoTag $tag = null): void
    {
        $event = GalleryEvent::create(['gallery_photo_id' => $photo?->id ?? $tag?->gallery_photo_id, 'photo_tag_id' => $tag?->id, 'actor_id' => $actor?->id, 'kind' => $kind, 'revision' => $tag?->revision ?? $photo?->revision ?? 1]);
        Audit::record('gallery.'.$kind, $event, [], ['photo_id' => $event->gallery_photo_id, 'tag_id' => $event->photo_tag_id, 'revision' => $event->revision]);
    }
}
