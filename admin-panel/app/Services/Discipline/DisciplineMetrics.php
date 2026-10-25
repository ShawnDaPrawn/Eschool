<?php

declare(strict_types=1);

namespace App\Services\Discipline;

use App\Models\Discipline\DisciplineFramework;
use App\Models\Discipline\DisciplineRecord;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Growth\RiskAuthority;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\Validator;

class DisciplineMetrics
{
    public function available(int $year): bool
    {
        $gate = app(ModuleGate::class);
        $gate->forget();

        return $gate->allows('discipline') && DisciplineFramework::where('session_year_id', $year)->exists();
    }

    /** Trusted risk worker: counts only, never description, staff note or clinical input. */
    public function observed(int $learner, int $year, int $grade, string $start, string $end): array
    {
        if (! $this->available($year)) {
            return ['score' => null, 'signal_count' => null, 'reason_code' => 'source_unavailable', 'evidence' => []];
        }
        $query = DisciplineRecord::where('learner_id', $learner)->where('session_year_id', $year)->where('grade_level_id', $grade)->whereIn('status', ['confirmed', 'hearing'])->whereBetween('occurred_on', [$start, $end]);
        $count = (clone $query)->count();

        return ['score' => min(100, 10 * $count), 'signal_count' => $count, 'reason_code' => $count === 0 ? 'no_observed_signal' : 'confirmed_discipline_count',
            'evidence' => ['rule_version' => 'confirmed-count-v1', 'confirmed_count' => $count, 'latest_record_id' => (clone $query)->max('id')]];
    }

    public function forLeadership(User $actor, int $year, string $start, string $end, array $learnersByGrade): array
    {
        app(RiskAuthority::class)->aggregate($actor);
        SessionYear::withTrashed()->findOrFail($year);
        Validator::make(['start' => $start, 'end' => $end], ['start' => ['required', 'date_format:Y-m-d'], 'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start']])->validate();
        $available = $this->available($year);
        $result = [];
        foreach ($learnersByGrade as $grade => $learners) {
            $result[$grade] = $available ? DisciplineRecord::where('session_year_id', $year)->where('grade_level_id', $grade)->whereIn('learner_id', $learners)
                ->whereIn('status', ['confirmed', 'hearing'])->whereBetween('occurred_on', [$start, $end])->distinct()->count('learner_id') : null;
        }

        return $result;
    }
}
