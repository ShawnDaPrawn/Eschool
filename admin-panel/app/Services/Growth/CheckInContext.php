<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\User;
use App\Models\Wellbeing\CheckIn;
use App\Services\Academics\AcademicAuthority;
use App\Services\Wellbeing\CheckIns;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Wellbeing check-ins as context on the early warning list (Action Plan 4.11):
 * read on request for the reader's own authorised learners only. It is never
 * stored in risk observations, never scored, never raises a risk and never
 * changes the order of the list. Everyone else gets nothing.
 */
class CheckInContext
{
    public const WEEKS = 6;

    /**
     * @param  list<int>  $learners
     * @return array<int, array{answered: int, latest_scale: ?int, latest_on: ?string}>
     */
    public function forLearners(User $actor, array $learners): array
    {
        try {
            $checkIns = app(CheckIns::class);
            $fresh = $checkIns->professional($actor);
            $own = array_values(array_intersect(array_map('intval', $learners), $checkIns->learnerIds($fresh)));
            if ($own === []) {
                return [];
            }
            $since = Carbon::parse(app(AcademicAuthority::class)->today())->subWeeks(self::WEEKS)->startOfDay();
            $year = (int) schoolSetting('session_year');
            // Count over the whole window per learner (school-scoped by BelongsToSchool), no row cap.
            $window = fn () => CheckIn::query()->whereIn('wellbeing_check_ins.learner_id', $own)->where('wellbeing_check_ins.session_year_id', $year)
                ->where('wellbeing_check_ins.status', 'answered')->where('wellbeing_check_ins.answered_at', '>=', $since);
            $stats = $window()->select('wellbeing_check_ins.learner_id')->selectRaw('COUNT(*) AS answered, MAX(wellbeing_check_ins.answered_at) AS latest_at')
                ->groupBy('wellbeing_check_ins.learner_id');
            $counts = (clone $stats)->get()->mapWithKeys(fn ($row) => [(int) $row->learner_id => (int) $row->answered])->all();
            // One latest row per learner (the highest id at that learner's latest time); only these answers are decrypted.
            $latestIds = $window()->joinSub($stats, 'latest', fn ($join) => $join->on('wellbeing_check_ins.learner_id', '=', 'latest.learner_id')
                ->on('wellbeing_check_ins.answered_at', '=', 'latest.latest_at'))
                ->groupBy('wellbeing_check_ins.learner_id')->selectRaw('MAX(wellbeing_check_ins.id) AS id')->pluck('id');
            $latest = CheckIn::whereIn('id', $latestIds)->whereIn('learner_id', $own)->get(['id', 'learner_id', 'answer', 'answered_at'])->keyBy('learner_id');
            // Recheck after reading: a grant withdrawn meanwhile shows nothing.
            $still = array_values(array_intersect($own, $checkIns->learnerIds($fresh)));
            $context = [];
            foreach ($still as $learner) {
                $row = $latest->get($learner);
                $context[$learner] = ['answered' => $counts[$learner] ?? 0, 'latest_scale' => $row?->answer['scale'] ?? null,
                    'latest_on' => $row?->answered_at?->toDateString()];
            }

            return $context;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return [];
        }
    }
}
