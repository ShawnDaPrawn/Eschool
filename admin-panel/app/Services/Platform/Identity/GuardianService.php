<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;

class GuardianService
{
    /** Explicit links override legacy relationships, including an ended link. */
    public function childrenFor(int $guardianUserId, int $legacyParentId): Builder
    {
        return Students::query()->where(function ($query) use ($guardianUserId, $legacyParentId) {
            $query->whereExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')
                ->whereColumn('guardian_links.learner_id', 'students.id')->whereColumn('guardian_links.school_id', 'students.school_id')
                ->where('guardian_user_id', $guardianUserId)->where('status', 'active'))
                ->orWhere(function ($legacy) use ($guardianUserId, $legacyParentId) {
                    $legacy->whereNotExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')
                        ->whereColumn('guardian_links.learner_id', 'students.id')->whereColumn('guardian_links.school_id', 'students.school_id')
                        ->where('guardian_user_id', $guardianUserId))
                        ->where(fn ($parents) => $parents->where('father_id', $legacyParentId)->orWhere('mother_id', $legacyParentId)->orWhere('guardian_id', $legacyParentId));
                });
        });
    }

    /** Keep legacy parent edits in sync; never grant consent as a side effect. */
    public function syncLegacy(Students $learner): void
    {
        if (app(CurrentSchool::class)->isMigrating()) {
            return;
        }
        $ids = [];
        foreach (['father_id' => 'father', 'mother_id' => 'mother', 'guardian_id' => 'guardian'] as $field => $relationship) {
            $parent = $learner->getAttribute($field) ? Parents::query()->find($learner->getAttribute($field)) : null;
            if ($parent === null) {
                continue;
            }
            $ids[] = $parent->user_id;
            $link = GuardianLink::query()->firstOrNew(['learner_id' => $learner->id, 'guardian_user_id' => $parent->user_id]);
            if (! $link->exists || $learner->wasChanged($field)) {
                $link->fill(['relationship' => $relationship, 'status' => 'active'])->save();
            }
        }
        GuardianLink::query()->where('learner_id', $learner->id)->whereIn('relationship', ['father', 'mother', 'guardian'])
            ->whereNotIn('guardian_user_id', $ids)->where('status', 'active')->get()
            ->each(fn (GuardianLink $link) => $link->update(['status' => 'ended']));
    }
}
