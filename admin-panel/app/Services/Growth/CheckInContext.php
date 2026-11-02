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
            $rows = CheckIn::whereIn('learner_id', $own)->where('session_year_id', (int) schoolSetting('session_year'))->where('status', 'answered')
                ->where('answered_at', '>=', $since)->orderByDesc('answered_at')->orderByDesc('id')->limit(500)->get(['id', 'learner_id', 'answer', 'answered_at']);
            // Recheck after reading: a grant withdrawn meanwhile shows nothing.
            $still = array_values(array_intersect($own, $checkIns->learnerIds($fresh)));
            $context = [];
            foreach ($still as $learner) {
                $mine = $rows->where('learner_id', $learner)->values();
                $context[$learner] = ['answered' => $mine->count(), 'latest_scale' => $mine->first()?->answer['scale'] ?? null,
                    'latest_on' => $mine->first()?->answered_at?->toDateString()];
            }

            return $context;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return [];
        }
    }
}
