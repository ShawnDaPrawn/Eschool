<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Learning\Marking\MarkingEvaluationRun;
use App\Models\Learning\Marking\MarkingSubjectGate;
use App\Services\Learning\Marking\Accuracy\AgreementMetrics;
use App\Services\Learning\Marking\Accuracy\MarkingVersions;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Opens or closes a subject's learner marking (Action Plan 3.18) for tests
 * about what learners receive once it is open: a passing run on the current
 * engine and profile versions and today's acceptance settings, and a
 * switched-on gate, written as HQ would.
 * How the gate opens is tested in MarkingAccuracyGateTest.
 */
trait OpensMarkingGate
{
    protected function openMarkingGate(string $subject, int $staffUserId): void
    {
        app(CurrentSchool::class)->acrossSchools(function () use ($subject, $staffUserId) {
            $versions = app(MarkingVersions::class);
            $run = MarkingEvaluationRun::create(['subject' => $subject, 'engine_version' => $versions->engine(), 'profile_version' => $versions->profile($subject),
                'script_count' => 200, 'point_count' => 600, 'passed' => true, 'failures' => [], 'metrics' => [], 'point_stats' => [], 'settings' => AgreementMetrics::settings(),
                'scripts_fingerprint' => str_repeat('0', 64), 'started_by' => $staffUserId]);
            MarkingSubjectGate::query()->updateOrCreate(['subject' => $subject], ['enabled' => true, 'marking_evaluation_run_id' => $run->id,
                'engine_version' => $run->engine_version, 'profile_version' => $run->profile_version, 'approved_by' => $staffUserId, 'approved_at' => now(),
                'reason' => 'Synthetic test gate', 'changed_by' => $staffUserId, 'revision' => 1]);
        });
    }

    protected function closeMarkingGate(string $subject): void
    {
        app(CurrentSchool::class)->acrossSchools(fn () => MarkingSubjectGate::query()->where('subject', $subject)->update(['enabled' => false]));
    }
}
