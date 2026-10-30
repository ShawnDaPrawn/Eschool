<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GuardianService
{
    /** Current relationship rights, including ended-link overrides of v1 fields. */
    public function guardianUserIds(Students $learner, bool $fees = false): Collection
    {
        abort_unless((int) $learner->school_id === app(CurrentSchool::class)->id(), 404);
        $parentIds = array_filter([$learner->father_id, $learner->mother_id, $learner->guardian_id]);
        $legacy = Parents::query()->whereIn('id', $parentIds)->whereNotExists(function ($query) use ($learner) {
            $query->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $learner->school_id)
                ->where('guardian_links.learner_id', $learner->id)->whereColumn('guardian_links.guardian_user_id', 'parents.user_id');
        })->pluck('user_id');
        $linked = GuardianLink::query()->where('learner_id', $learner->id)->where('status', 'active')
            ->when($fees, fn ($query) => $query->where('can_see_fees', true))->pluck('guardian_user_id');

        return $legacy->merge($linked)->filter()->map(fn ($id) => (int) $id)->unique()->values();
    }

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
        // Only links that came from these fields end with them; guardians the
        // office manages directly (GuardianLinks) are never ended by a legacy edit.
        GuardianLink::query()->where('learner_id', $learner->id)->whereIn('relationship', ['father', 'mother', 'guardian'])
            ->where('source', GuardianLinks::LEGACY)->whereNotIn('guardian_user_id', $ids)->where('status', 'active')->get()
            ->each(fn (GuardianLink $link) => $link->update(['status' => 'ended']));
    }
}
