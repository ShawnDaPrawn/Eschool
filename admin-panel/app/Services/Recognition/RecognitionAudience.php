<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Metadata-only audience shared by private notices and private person live events. */
class RecognitionAudience
{
    public function learners(User $actor, string $module): array
    {
        $a = app(RecognitionAuthority::class);
        $fresh = $a->actor($actor, $module);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $parents = in_array('Parent', $roles, true) ? Parents::where('user_id', $fresh->id)->pluck('id')->all() : [];
        $school = app(CurrentSchool::class)->id();
        $candidates = Students::where(function (Builder $q) use ($fresh, $roles, $parents, $school) {
            $q->whereRaw('1 = 0');
            if (in_array('Student', $roles, true)) {
                $q->orWhere('user_id', $fresh->id);
            }
            if (in_array('Parent', $roles, true)) {
                $q->orWhereExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $school)
                    ->whereColumn('guardian_links.learner_id', 'students.id')->where('guardian_links.guardian_user_id', $fresh->id)->where('guardian_links.status', 'active'));
                if ($parents !== []) {
                    $q->orWhere(function ($legacy) use ($parents, $fresh, $school) {
                        $legacy->where(fn ($p) => $p->whereIn('father_id', $parents)->orWhereIn('mother_id', $parents)->orWhereIn('guardian_id', $parents))
                            ->whereNotExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $school)
                                ->whereColumn('guardian_links.learner_id', 'students.id')->where('guardian_links.guardian_user_id', $fresh->id));
                    });
                }
            }
        })->get(['id', 'user_id']);
        $ids = [];
        foreach ($candidates as $learner) {
            try {
                $a->family($fresh, (int) $learner->id, $module);
                $ids[] = (int) $learner->id;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }

        return $ids;
    }

    public function recipients(int $learnerId, string $module): array
    {
        $learner = Students::findOrFail($learnerId);
        $parents = array_values(array_filter([(int) $learner->father_id, (int) $learner->mother_id, (int) $learner->guardian_id]));
        $candidates = GuardianLink::where('learner_id', $learnerId)->where('status', 'active')->pluck('guardian_user_id')->all();
        if ($parents !== []) {
            $candidates = array_merge($candidates, Parents::whereIn('id', $parents)->pluck('user_id')->all());
        }
        $candidates[] = (int) $learner->user_id;
        $ids = [];
        foreach (User::whereIn('id', array_unique($candidates))->get(['id']) as $user) {
            try {
                if (in_array($learnerId, $this->learners($user, $module), true)) {
                    $ids[] = (int) $user->id;
                }
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }

        return $ids;
    }
}
