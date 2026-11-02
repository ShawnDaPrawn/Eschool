<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Activities\ActivityMembership;
use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Activities\CommunityAuthority;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GalleryTags
{
    private function locks(int $learner): void
    {
        $row = Students::whereKey($learner)->lockForUpdate()->firstOrFail();
        DB::table('users')->where('id', $row->user_id)->lockForUpdate()->first();
        GuardianLink::where('learner_id', $learner)->orderBy('id')->lockForUpdate()->get(['id']);
    }

    private function retireUnlinkedFamilyPhoto(int $id): void
    {
        $photo = GalleryPhoto::lockForUpdate()->findOrFail($id);
        if ($photo->scope === 'family' && $photo->active && ! PhotoTag::where('gallery_photo_id', $id)->whereIn('status', ['proposed', 'approved'])->exists()) {
            $photo->update(['active' => false, 'revision' => $photo->revision + 1]);
            app(GalleryEvents::class)->record('family_photo_unlinked', photo: $photo);
        }
    }

    private function proposalAuthority(User $actor, GalleryPhoto $photo, int $learner): void
    {
        $a = app(GalleryAuthority::class);
        if ($photo->scope === 'group') {
            $roles = app(ProfilePolicy::class)->roles($a->actor($actor, true));
            if (array_diff($roles, ['Parent', 'Student']) !== []) {
                try {
                    $a->group($actor, (int) $photo->activity_group_id, true);
                    $a->eligible($photo, $learner);

                    return;
                } catch (HttpExceptionInterface|ModelNotFoundException $error) {
                    if (array_intersect($roles, ['Parent', 'Student']) === []) {
                        throw $error;
                    }
                }
            }
        }
        $a->family($actor, $learner, true);
        $a->eligible($photo, $learner);
    }

    public function propose(User $actor, int $photoId, array $input): PhotoTag
    {
        app(GalleryAuthority::class)->actor($actor, true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required_without:handle', 'nullable', 'integer', 'min:1'], 'handle' => ['required_without:learner_id', 'nullable', 'string', 'regex:/^@?[a-z][a-z0-9._]{1,18}[a-z0-9]$/D']])->validate();
        $photo = GalleryPhoto::where('active', true)->select(['id', 'scope', 'activity_group_id', 'owner_learner_id'])->findOrFail($photoId);
        if (! empty($d['handle'])) {
            // Exact approved handle only, inside this school's authorised source.
            $query = Students::whereHas('user', fn ($q) => $q->where('handle', ltrim($d['handle'], '@')));
            if ($photo->scope === 'family') {
                $query->whereKey($photo->owner_learner_id);
            } else {
                $allowed = app(CommunityAuthority::class)->learnerIds($actor);
                try {
                    app(GalleryAuthority::class)->group($actor, (int) $photo->activity_group_id, true);
                    $allowed = ActivityMembership::where('activity_group_id', $photo->activity_group_id)->where('active', true)->pluck('learner_id')->all();
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                }
                $query->whereIn('id', $allowed);
            }
            $resolved = (int) $query->value('id');
            abort_unless($resolved > 0, 404);
            abort_if(isset($d['learner_id']) && (int) $d['learner_id'] !== $resolved, 422, 'The selected learner and handle do not match.');
            $d['learner_id'] = $resolved;
        }
        $learner = (int) $d['learner_id'];
        $authority = fn () => $this->proposalAuthority($actor, GalleryPhoto::where('active', true)->select(['id', 'scope', 'activity_group_id', 'owner_learner_id'])->findOrFail($photoId), $learner);
        $authority();
        $d += ['kind' => 'tag.propose', 'photo_id' => $photoId];
        $result = app(GalleryOperations::class)->once($actor, $d, $authority, function () use ($actor, $photoId, $learner): array {
            $this->locks($learner);
            $photo = GalleryPhoto::where('active', true)->lockForUpdate()->findOrFail($photoId);
            $this->proposalAuthority($actor, $photo, $learner);
            $tag = PhotoTag::where('gallery_photo_id', $photoId)->where('learner_id', $learner)->lockForUpdate()->first();
            abort_if($tag !== null && in_array($tag->status, ['proposed', 'approved'], true), 409, 'This photo already has a current tag for the learner.');
            $tag ??= new PhotoTag(['gallery_photo_id' => $photoId, 'learner_id' => $learner]);
            $kind = null;
            try {
                app(GalleryAuthority::class)->family($actor, $learner, true, true);
                $kind = 'guardian';
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                $self = Students::whereKey($learner)->where('user_id', $actor->id)->exists() && in_array('Student', app(ProfilePolicy::class)->roles($actor), true);
                if ($self && ! app(GallerySettings::class)->current()->guardian_required) {
                    $kind = 'self';
                }
            }
            $tag->fill(['tagged_by' => $actor->id, 'status' => $kind === null ? 'proposed' : 'approved', 'approval_kind' => $kind, 'approved_by' => $kind === null ? null : $actor->id, 'approved_at' => $kind === null ? null : now(), 'retained_on_transfer' => false, 'revision' => (int) ($tag->revision ?? 0) + 1])->save();
            app(GalleryEvents::class)->record($kind === null ? 'tag_proposed' : 'tag_approved', $actor, tag: $tag);

            return ['id' => (int) $tag->id];
        });

        return PhotoTag::findOrFail($result['id']);
    }

    public function decisionAuthority(User $actor, int $learner): string
    {
        $authority = app(GalleryAuthority::class);
        try {
            $authority->family($actor, $learner, true, true);

            return 'guardian';
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            if (app(GallerySettings::class)->current()->guardian_required || ! in_array('Student', app(ProfilePolicy::class)->roles($actor), true) || ! Students::whereKey($learner)->where('user_id', $actor->id)->exists()) {
                throw $error;
            }
            $authority->family($actor, $learner, true);

            return 'self';
        }
    }

    public function decide(User $actor, int $id, array $input): PhotoTag
    {
        $a = app(GalleryAuthority::class);
        $a->actor($actor, true);
        $tag = PhotoTag::findOrFail($id);
        $this->decisionAuthority($actor, (int) $tag->learner_id);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:approve,reject']])->validate() + ['kind' => 'tag.decide', 'tag_id' => $id];
        $authority = fn () => $this->decisionAuthority($actor, (int) $tag->learner_id);
        $result = app(GalleryOperations::class)->once($actor, $d, $authority, function () use ($actor, $id, $d, $a): array {
            $initial = PhotoTag::findOrFail($id);
            $this->locks((int) $initial->learner_id);
            $row = PhotoTag::lockForUpdate()->findOrFail($id);
            $kind = $this->decisionAuthority($actor, (int) $row->learner_id);
            abort_unless((int) $row->revision === (int) $d['revision'] && in_array($row->status, ['proposed', 'approved'], true), 409, 'Compare the current photo tag.');
            $photo = GalleryPhoto::where('active', true)->lockForUpdate()->findOrFail($row->gallery_photo_id);
            $a->eligible($photo, (int) $row->learner_id);
            $approved = $d['decision'] === 'approve';
            $row->update(['status' => $approved ? 'approved' : 'rejected', 'approval_kind' => $approved ? $kind : null, 'approved_by' => $actor->id, 'approved_at' => $approved ? now() : null, 'revision' => $row->revision + 1]);
            app(GalleryEvents::class)->record($approved ? 'tag_approved' : 'tag_rejected', $actor, tag: $row);
            if (! $approved) {
                $this->retireUnlinkedFamilyPhoto((int) $row->gallery_photo_id);
            }

            return ['id' => (int) $row->id];
        });

        return PhotoTag::findOrFail($result['id']);
    }

    public function remove(User $actor, int $id, array $input): PhotoTag
    {
        $a = app(GalleryAuthority::class);
        $a->actor($actor, true, removal: true);
        $tag = PhotoTag::findOrFail($id);
        $authority = fn () => $a->family($actor, (int) $tag->learner_id, true, removal: true);
        $authority();
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']])->validate() + ['kind' => 'tag.remove', 'tag_id' => $id];
        $result = app(GalleryOperations::class)->once($actor, $d, $authority, function () use ($actor, $tag, $d, $authority): array {
            $this->locks((int) $tag->learner_id);
            $row = PhotoTag::lockForUpdate()->findOrFail($tag->id);
            $authority();
            abort_unless((int) $row->revision === (int) $d['revision'], 409, 'Compare the current photo tag.');
            $row->update(['status' => 'removed', 'retained_on_transfer' => false, 'revision' => $row->revision + 1]);
            app(GalleryEvents::class)->record('tag_removed', $actor, tag: $row);
            $this->retireUnlinkedFamilyPhoto((int) $row->gallery_photo_id);

            return ['id' => (int) $row->id];
        }, removal: true);

        return PhotoTag::findOrFail($result['id']);
    }
}
