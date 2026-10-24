<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\MarkSnapshot;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MarkSnapshots
{
    /** Recomputations append immutable evidence; identical retries write nothing. */
    public function recompute(int $groupId, ?int $termId = null, bool $allTerms = false): array
    {
        return DB::transaction(function () use ($groupId, $termId, $allTerms) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $group = TeachingGroup::query()->findOrFail($groupId);
            $periods = $allTerms ? app(MarkDefinitions::class)->plans($group)->pluck('term_id')->map(fn ($id) => (int) $id)->all() : ($termId === null ? [] : [$termId]);
            $periods[] = null;
            $learners = app(MarkInputs::class)->memberships($group)->pluck('learner_id')->unique()->map(fn ($id) => (int) $id)->all();
            $created = 0;
            $unchanged = 0;
            foreach ($periods as $period) {
                $prepared = app(MarkInputs::class)->prepare($group, $period);
                foreach ($learners as $learner) {
                    $input = app(MarkInputs::class)->forLearner($group, $learner, $period, [], $prepared);
                    $fingerprint = hash('sha256', json_encode($input, JSON_THROW_ON_ERROR));
                    $latest = $this->query($group, $learner, $period)->orderByDesc('version')->first();
                    if ($latest?->fingerprint === $fingerprint) {
                        $unchanged++;

                        continue;
                    }
                    $result = app(MarkInputs::class)->calculate($input);
                    MarkSnapshot::query()->create(['subject_id' => $group->subject_id, 'grade_level_id' => $group->grade_level_id,
                        'session_year_id' => $group->session_year_id, 'learner_id' => $learner, 'term_id' => $period,
                        'period_key' => $input['period_key'], 'version' => (int) ($latest?->version ?? 0) + 1,
                        'fingerprint' => $fingerprint, 'percentage_bp' => $result['percentage_bp'], 'running_bp' => $result['running_bp'],
                        'coverage_bp' => $result['coverage_bp'], 'rating_level' => $result['rating_level'], 'complete' => $result['complete'],
                        'inputs' => $input, 'result' => $result, 'created_at' => now()]);
                    $created++;
                }
            }

            return ['created' => $created, 'unchanged' => $unchanged];
        }, 3);
    }

    public function query(TeachingGroup $group, int $learner, ?int $termId): Builder
    {
        return MarkSnapshot::query()->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)
            ->where('session_year_id', $group->session_year_id)->where('learner_id', $learner)->where('period_key', $termId === null ? 'year' : 'term:'.$termId);
    }
}
