<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\Intervention;
use App\Models\Growth\RiskCaseload;
use App\Models\Growth\RiskObservation;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Pastoral\SensitiveRecordsAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RiskViews
{
    private function scopes(User $actor, int $year): array
    {
        $authority = app(RiskAuthority::class);
        $authority->year($year);
        $fresh = $authority->actor($actor);
        $today = app(AcademicAuthority::class)->today();
        $grades = AcademicLeadership::where('user_id', $fresh->id)->where('kind', 'grade_head')
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('grade_level_id')->all();
        $learners = [];
        try {
            app(SensitiveRecordsAccess::class)->actor($fresh);
            $learners = RiskCaseload::where('professional_id', $fresh->id)->where('session_year_id', $year)->whereNull('revoked_at')
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('learner_id')->all();
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            // A dated grade head's academic role does not grant clinical access.
        }
        abort_unless($grades !== [] || $learners !== [], 403);

        return [$grades, $learners];
    }

    public function list(User $actor, int $year, int $page = 1): array
    {
        [$grades, $learners] = $this->scopes($actor, $year);
        $scope = fn (Builder $q) => $q->whereIn('grade_level_id', $grades)->orWhereIn('learner_id', $learners);
        $query = RiskObservation::where('session_year_id', $year)->where($scope);
        $people = Students::whereIn('id', (clone $query)->select('learner_id'))->with('user')->orderBy('id')->paginate(50, ['id', 'user_id'], 'page', max(1, $page));
        $rows = (clone $query)->whereIn('learner_id', $people->pluck('id'))->select('id', 'learner_id', 'grade_level_id', 'as_of', 'source', 'score', 'signal_count', 'reason_code')
            ->orderByDesc('id')->get()->unique(fn ($r) => $r->learner_id.':'.$r->source);

        return ['session_year_id' => $year, 'page' => $people->currentPage(), 'last_page' => $people->lastPage(),
            'learners' => $people->map(function ($learner) use ($rows, $actor, $year): array {
                $signals = $rows->where('learner_id', $learner->id)->map(function ($row) use ($actor, $year, $learner): array {
                    $signal = ['source' => $row->source, 'score' => $row->score, 'signal_count' => $row->signal_count,
                        'reason_code' => $row->reason_code, 'grade_level_id' => (int) $row->grade_level_id, 'as_of' => $row->as_of->toDateString()];
                    if ($row->source === 'wellbeing') {
                        $count = app(CounsellorCaseContext::class)->count($actor, (int) $learner->id, $year, (int) $row->grade_level_id);
                        if ($count !== null) {
                            $signal = array_replace($signal, ['score' => null, 'signal_count' => $count,
                                'reason_code' => 'own_authorised_active_cases', 'as_of' => app(AcademicAuthority::class)->today()]);
                        }
                    }

                    return $signal;
                })->values()->all();

                return ['learner_id' => (int) $learner->id, 'name' => trim($learner->user->first_name.' '.$learner->user->last_name), 'signals' => $signals];
            })->all()];
    }

    /** Allocated teachers receive academic/attendance facts, never professional source counts. */
    public function classroom(User $actor, int $groupId): array
    {
        app(RiskAuthority::class)->enabled();
        $fresh = app(RiskAuthority::class)->actor($actor);
        $group = app(AcademicAuthority::class)->group($fresh, $groupId);
        $today = app(AcademicAuthority::class)->today();
        $ids = TeachingGroupMembership::where('teaching_group_id', $groupId)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('learner_id');
        $people = Students::whereIn('id', $ids)->with('user')->get();
        $rows = RiskObservation::where('session_year_id', $group->session_year_id)->where('grade_level_id', $group->grade_level_id)
            ->whereIn('learner_id', $ids)->whereIn('source', ['academic', 'attendance'])->select('id', 'learner_id', 'source', 'score', 'reason_code', 'as_of')
            ->orderByDesc('id')->get()->unique(fn ($r) => $r->learner_id.':'.$r->source);

        return ['group_id' => $groupId, 'learners' => $people->map(function ($learner) use ($rows) {
            $signals = $rows->where('learner_id', $learner->id);

            return ['learner_id' => (int) $learner->id, 'name' => trim($learner->user->first_name.' '.$learner->user->last_name),
                'observed_score' => $signals->whereNotNull('score')->isEmpty() ? null : $signals->sum('score'),
                'signals' => $signals->map(fn ($r) => ['source' => $r->source, 'score' => $r->score, 'reason_code' => $r->reason_code,
                    'as_of' => $r->as_of->toDateString()])->values()->all()];
        })->sortByDesc(fn ($row) => $row['observed_score'] ?? -1)->values()->all()];
    }

    /** Review queue is metadata only; opening text still requires the exact fresh owner. */
    public function interventions(User $actor, int $year, int $page = 1): array
    {
        app(RiskAuthority::class)->year($year);
        $fresh = app(RiskAuthority::class)->actor($actor);
        try {
            [$grades, $learners] = $this->scopes($fresh, $year);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            $grades = $learners = [];
        }
        $authority = app(AcademicAuthority::class);
        $today = $authority->today();
        $groups = TeachingGroup::where('active', true)->where('session_year_id', (int) schoolSetting('session_year'));
        $principal = $authority->currentLeadership($fresh, 'principal', 0);
        if (! $principal) {
            $groups->whereIn('id', TeachingAllocation::whereIn('teacher_id', $authority->teachers($fresh))
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('teaching_group_id'));
        }
        $academic = TeachingGroupMembership::whereIn('teaching_group_id', $groups->select('id'))
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('learner_id')->all();
        abort_unless($principal || $grades !== [] || $learners !== [] || $academic !== [], 403);
        $rows = Intervention::select(Interventions::META)->where('session_year_id', $year)
            ->where(function ($q) use ($principal, $grades, $learners, $fresh, $academic) {
                if ($principal) {
                    $q->whereRaw('1 = 1');
                } else {
                    $q->whereIn('grade_level_id', $grades)->orWhereIn('learner_id', $learners)
                        ->orWhere(fn ($own) => $own->where('owner_id', $fresh->id)->where('owner_authority', 'academic')->whereIn('learner_id', $academic));
                }
            })
            ->orderByRaw("CASE WHEN state = 'active' THEN 0 ELSE 1 END")->orderBy('review_on')->orderBy('id')->paginate(50, ['*'], 'page', max(1, $page));

        $names = Students::whereIn('id', $rows->pluck('learner_id'))->with('user')->get()->mapWithKeys(fn ($learner) => [(int) $learner->id => trim(($learner->user?->first_name ?? '').' '.($learner->user?->last_name ?? ''))]);
        $owners = User::whereIn('id', $rows->pluck('owner_id'))->get(['id', 'first_name', 'last_name'])->mapWithKeys(fn ($person) => [(int) $person->id => trim($person->first_name.' '.$person->last_name)]);

        return ['session_year_id' => $year, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(),
            'interventions' => $rows->map(fn ($r) => ['id' => (int) $r->id, 'learner_id' => (int) $r->learner_id,
                'owner_id' => (int) $r->owner_id, 'learner_name' => $names->get((int) $r->learner_id, __('Archived learner')), 'owner_name' => $owners->get((int) $r->owner_id, __('Former staff member')), 'grade_level_id' => (int) $r->grade_level_id, 'review_on' => $r->review_on->toDateString(),
                'state' => $r->state, 'revision' => $r->revision, 'can_read_body' => $this->canReadBody($fresh, $r)])->all()];
    }

    private function canReadBody(User $actor, Intervention $row): bool
    {
        if ((int) $row->owner_id !== (int) $actor->id) {
            return false;
        }
        try {
            app(RiskAuthority::class)->caseActor($actor, (int) $row->learner_id, (int) $row->session_year_id,
                (int) $row->grade_level_id, false, $row->owner_authority);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
