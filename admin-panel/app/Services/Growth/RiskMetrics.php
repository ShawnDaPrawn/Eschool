<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\RiskObservation;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\Validator;

/** SQL counts only: no evidence, private case text, labels or learner IDs leave this service. */
class RiskMetrics
{
    public function forPrincipal(User $actor, int $year, string $from, string $to, ?array $learnersByGrade = null): array
    {
        app(RiskAuthority::class)->aggregate($actor);
        SessionYear::withTrashed()->findOrFail($year);
        Validator::make(['from' => $from, 'to' => $to], ['from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from']])->validate();
        $gate = app(ModuleGate::class);
        $gate->forget();
        if (! $gate->allows('early_warning')) {
            return ['available' => false, 'grades' => []];
        }
        $query = RiskObservation::where('session_year_id', $year)->whereBetween('as_of', [$from, $to])->where('source', 'academic');
        $grades = $learnersByGrade === null ? (clone $query)->distinct()->pluck('grade_level_id')->all() : array_keys($learnersByGrade);
        $result = [];
        foreach ($grades as $grade) {
            $scope = (clone $query)->where('grade_level_id', $grade)
                ->when($learnersByGrade !== null, fn ($q) => $q->whereIn('learner_id', $learnersByGrade[$grade] ?? []));
            $known = (clone $scope)->whereNotNull('score')->exists();
            $count = $known ? (clone $scope)->where('score', '>', 0)->distinct()->count('learner_id') : null;
            $result[(int) $grade] = ['academic_risk' => $count];
        }

        return ['available' => true, 'grades' => $result];
    }
}
