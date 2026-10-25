<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GalleryQueries
{
    public function currentLearner(Students $learner): bool
    {
        return SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $learner->user_id)->where('role', 'Student')->exists();
    }

    public function children(User $actor): array
    {
        $a = app(GalleryAuthority::class);
        $items = [];
        foreach ($a->candidates($actor)->orderBy('id')->limit(1001)->get(['id', 'user_id']) as $learner) {
            try {
                $a->family($actor, (int) $learner->id);
                $person = User::acrossSchools()->whereKey($learner->user_id)->firstOrFail();
                $approve = true;
                try {
                    $a->family($actor, (int) $learner->id, approve: true);
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    $approve = false;
                }
                $items[] = ['id' => (int) $learner->id, 'name' => $person->full_name, 'handle' => $person->handle, 'can_approve' => $approve, 'archived' => ! $this->currentLearner($learner)];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }
        abort_if(count($items) > 1000, 422);

        return $items;
    }

    public function configuration(User $actor): array
    {
        $a = app(GalleryAuthority::class);
        $a->actor($actor);
        $groups = [];
        try {
            foreach (app(CommunityAuthority::class)->visible($actor) as $group) {
                try {
                    $a->group($actor, (int) $group->id);
                    $write = true;
                    try {
                        $a->group($actor, (int) $group->id, true);
                    } catch (HttpExceptionInterface|ModelNotFoundException) {
                        $write = false;
                    }
                    $groups[] = ['id' => (int) $group->id, 'name' => $group->name, 'session_year_id' => (int) $group->session_year_id, 'can_upload' => $write];
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                }
            }
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            // Personal history does not depend on the groups module being enabled.
        }
        $manage = true;
        try {
            $a->manage($actor);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            $manage = false;
        }
        $settings = app(GallerySettings::class)->current();
        app(ModuleGate::class)->forget();

        return ['school_id' => app(CurrentSchool::class)->id(), 'account_id' => (int) $actor->id, 'session_year_id' => (int) schoolSetting('session_year'), 'today' => now()->toDateString(),
            'years' => SessionYear::whereNotNull('start_date')->whereNotNull('end_date')->orderByDesc('start_date')->get()->map(fn ($year) => ['id' => (int) $year->id, 'name' => $year->name, 'starts_on' => substr((string) $year->start_date, 0, 10), 'ends_on' => substr((string) $year->end_date, 0, 10)])->all(),
            'children' => $this->children($actor), 'groups' => $groups, 'guardian_required' => $settings->guardian_required, 'settings_revision' => (int) $settings->revision,
            'can_manage' => $manage, 'can_write' => app(ModuleGate::class)->writable('gallery')];
    }

    /** The caller already owns this scope; never return storage paths or other tags. */
    public function photo(GalleryPhoto $photo): array
    {
        return ['id' => (int) $photo->id, 'public_id' => $photo->public_id, 'caption' => $photo->caption, 'captured_on' => $photo->captured_on->toDateString(), 'school_year' => $photo->year_label,
            'session_year_id' => (int) $photo->session_year_id, 'activity_group_id' => $photo->activity_group_id === null ? null : (int) $photo->activity_group_id, 'scope' => $photo->scope,
            'width' => (int) $photo->width, 'height' => (int) $photo->height, 'active' => $photo->active, 'revision' => (int) $photo->revision];
    }

    public function ownedTags(User $actor, int $learnerId, bool $pending = false): Builder
    {
        $a = app(GalleryAuthority::class);
        $learner = $a->family($actor, $learnerId, approve: $pending);
        $guardian = app(GallerySettings::class)->current()->guardian_required;
        $q = PhotoTag::where('learner_id', $learnerId)->whereExists(fn ($photos) => $photos->selectRaw('1')->from('gallery_photos')->whereColumn('gallery_photos.id', 'photo_tags.gallery_photo_id')->where('gallery_photos.school_id', app(CurrentSchool::class)->id())->where('gallery_photos.active', true));
        if (! $this->currentLearner($learner)) {
            $q->where('retained_on_transfer', true);
        }
        if ($pending) {
            $groups = $a->eligibleGroups($learnerId);
            $q->whereExists(fn ($photos) => $photos->selectRaw('1')->from('gallery_photos')->whereColumn('gallery_photos.id', 'photo_tags.gallery_photo_id')->where('gallery_photos.school_id', app(CurrentSchool::class)->id())
                ->where(fn ($scope) => $scope->where(fn ($family) => $family->where('gallery_photos.scope', 'family')->where('gallery_photos.owner_learner_id', $learnerId))->orWhere(fn ($group) => $group->where('gallery_photos.scope', 'group')->whereIn('gallery_photos.activity_group_id', $groups))));
            $q->where(fn ($state) => $state->where('status', 'proposed')->when($guardian, fn ($self) => $self->orWhere(fn ($s) => $s->where('status', 'approved')->where('approval_kind', 'self'))));
        } else {
            $q->where('status', 'approved')->when($guardian, fn ($approved) => $approved->where('approval_kind', 'guardian'));
        }

        return $q;
    }

    public function family(User $actor, int $learnerId, bool $pending = false, ?int $before = null, ?int $year = null): array
    {
        $settings = app(GallerySettings::class)->current();
        $query = $this->ownedTags($actor, $learnerId, $pending);
        if ($year !== null) {
            $query->whereExists(fn ($p) => $p->selectRaw('1')->from('gallery_photos')->whereColumn('gallery_photos.id', 'photo_tags.gallery_photo_id')->where('gallery_photos.school_id', app(CurrentSchool::class)->id())->where('gallery_photos.session_year_id', $year));
        }
        $total = (clone $query)->count();
        $rows = $query->when($before !== null, fn ($q) => $q->where('photo_tags.id', '<', $before))->orderByDesc('id')->limit(31)->get();
        $more = $rows->count() > 30;
        $items = [];
        foreach ($rows->take(30) as $tag) {
            $photo = GalleryPhoto::where('active', true)->findOrFail($tag->gallery_photo_id);
            if ($pending) {
                app(GalleryAuthority::class)->eligible($photo, $learnerId);
            }
            $items[] = ['tag_id' => (int) $tag->id, 'tag_revision' => (int) $tag->revision, 'status' => $pending ? 'awaiting_guardian' : 'approved', 'retained_on_transfer' => $tag->retained_on_transfer, 'photo' => $this->photo($photo)];
        }
        app(GalleryAuthority::class)->family($actor, $learnerId, approve: $pending);
        abort_unless((int) app(GallerySettings::class)->current()->revision === (int) $settings->revision, 409, 'The photo consent rule changed. Refresh this gallery.');

        return ['items' => $items, 'total' => $total, 'next_before' => $more ? (int) $rows->take(30)->last()->id : null];
    }

    public function group(User $actor, int $id, ?int $before = null): array
    {
        app(GalleryAuthority::class)->group($actor, $id);
        $rows = GalleryPhoto::where('scope', 'group')->where('activity_group_id', $id)->where('active', true)->when($before !== null, fn ($q) => $q->where('id', '<', $before))->orderByDesc('id')->limit(31)->get();
        $more = $rows->count() > 30;
        $items = $rows->take(30)->map(fn ($photo) => $this->photo($photo))->all();
        app(GalleryAuthority::class)->group($actor, $id);

        return ['items' => $items, 'next_before' => $more ? (int) $rows->take(30)->last()->id : null];
    }
}
