<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Gallery\GalleryPhoto;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GalleryPanelScope
{
    public function available(User $user): bool
    {
        try {
            $this->staff($user);
            $configuration = app(GalleryQueries::class)->configuration($user);

            return $configuration['can_manage'] || $configuration['groups'] !== [];
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    private function staff(User $user, bool $write = false): void
    {
        $fresh = app(GalleryAuthority::class)->actor($user, $write);
        abort_unless(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) !== [], 403);
    }

    public function authorize(Request $r): bool
    {
        $a = app(GalleryAuthority::class);
        $write = ! $r->isMethodSafe();
        $this->staff($r->user(), $write);
        $operation = (string) $r->route('operation');
        if ($write || $operation === 'image') {
            abort_unless((int) $r->input('expected_account_id') === (int) $r->user()->id && (int) $r->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
        }
        if ($write) {
            abort_if($r->query->count() > 0, 422, 'Submit photo changes in the private form.');
        }
        if ($operation === 'settings') {
            $a->manage($r->user(), true);
        } elseif ($r->route('photo') !== null) {
            $photo = GalleryPhoto::select(['id', 'scope', 'activity_group_id'])->findOrFail((int) $r->route('photo'));
            abort_unless($photo->scope === 'group', 404);
            try {
                $a->group($r->user(), (int) $photo->activity_group_id, $write);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                abort(404);
            }
            $r->attributes->set('gallery_group_id', (int) $photo->activity_group_id);
        } elseif ($operation === 'upload' || $r->filled('group_id')) {
            $group = (int) ($operation === 'upload' ? $r->input('activity_group_id') : $r->input('group_id'));
            $a->group($r->user(), $group, $write);
            $r->attributes->set('gallery_group_id', $group);
        } else {
            abort_unless($this->available($r->user()), 403);
        }

        return true;
    }
}
