<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Accuracy;

use App\Models\Learning\Marking\MarkingEvaluationRun;
use App\Models\Learning\Marking\MarkingSubjectGate;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The subject switch-on gate (Action Plan 3.18). Learners receive engine
 * marking in a subject only while all of these hold:
 *
 * - the subject is one the gate covers (config marking.gate.subjects);
 * - an HQ approver (marking.approve) switched it on against a passing run;
 * - that run measured the engine and profile versions in this build, so a new
 *   engine or profile version closes the gate until a new run passes and an
 *   approver switches it on again;
 * - the latest run on those versions still passed (a later failing run, for
 *   example after more scripts were imported, closes the gate).
 *
 * Switching on also needs the owner's confirmation of the thresholds, the
 * tolerance and the minimum script count (config marking.gate.owner_confirmed).
 * Every switch is audited. The gate is checked where learners receive engine
 * marking: SubjectProfile::learnerMarkingEnabled(), used by PaperMarking.
 */
class MarkingGate
{
    public static function switchOnRules(): array
    {
        return ['run_id' => ['required', 'integer', 'min:1'], 'expected_revision' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:10', 'max:500'], 'confirmed' => ['required', 'accepted']];
    }

    public static function switchOffRules(): array
    {
        return ['expected_revision' => ['required', 'integer', 'min:0'], 'reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    /** Whether learners may receive engine marking in the subject now. */
    public function open(string $subject): bool
    {
        if (! in_array($subject, AccuracyAccess::subjects(), true)) {
            return false;
        }
        $gate = MarkingSubjectGate::query()->where('subject', $subject)->first();
        if ($gate === null || ! $gate->enabled) {
            return false;
        }
        $versions = app(MarkingVersions::class);
        if ($gate->engine_version !== $versions->engine() || $gate->profile_version !== $versions->profile($subject)) {
            return false;
        }

        return $this->latestRun($subject)?->passed === true;
    }

    /** The latest run of the subject on the current engine and profile versions. */
    public function latestRun(string $subject): ?MarkingEvaluationRun
    {
        $versions = app(MarkingVersions::class);

        return MarkingEvaluationRun::query()->where('subject', $subject)->where('engine_version', $versions->engine())
            ->where('profile_version', $versions->profile($subject))->orderByDesc('id')->first();
    }

    /**
     * The gate for the HQ page: whether it is on and open, the run it was switched on
     * against, the run an approver could switch it on with now, and why not.
     *
     * @return array<string, mixed>
     */
    public function state(string $subject): array
    {
        $gate = MarkingSubjectGate::query()->where('subject', $subject)->first();
        $latest = $this->latestRun($subject);
        $versions = app(MarkingVersions::class);
        $blockers = [];
        if (($latest?->passed) !== true) {
            $blockers[] = $latest === null ? __('No run yet on the current engine and profile versions.') : __('The latest run on the current versions did not pass.');
        }
        if (config('marking.gate.owner_confirmed') !== true) {
            $blockers[] = __('The owner has not yet confirmed the threshold, tolerance and minimum number of scripts.');
        }
        $stale = $gate !== null && $gate->enabled && ($gate->engine_version !== $versions->engine() || $gate->profile_version !== $versions->profile($subject));

        return ['subject' => $subject, 'enabled' => (bool) $gate?->enabled, 'open' => $this->open($subject), 'stale' => $stale,
            'revision' => (int) ($gate->revision ?? 0), 'run_id' => $gate?->marking_evaluation_run_id, 'engine_version' => $gate?->engine_version,
            'profile_version' => $gate?->profile_version, 'approved_at' => $gate?->approved_at, 'approved_by' => $gate?->approved_by, 'reason' => $gate?->reason,
            'eligible_run_id' => $blockers === [] ? $latest?->id : null, 'blockers' => $blockers];
    }

    /** An HQ approver switches learner marking on in the subject, against the latest passing run on the current versions. */
    public function switchOn(User $actor, string $subject, array $input): MarkingSubjectGate
    {
        $actor = app(AccuracyAccess::class)->hq($actor, AccuracyAccess::APPROVE);
        $subject = AccuracyAccess::subject($subject);
        $data = validator($input, self::switchOnRules())->validate();
        if (config('marking.gate.owner_confirmed') !== true) {
            throw ValidationException::withMessages(['run_id' => __('The owner has not yet confirmed the threshold, tolerance and minimum number of scripts, so no subject can be switched on.')]);
        }

        return DB::transaction(function () use ($actor, $subject, $data) {
            $gate = $this->locked($subject, (int) $data['expected_revision']);
            // Read the runs again after taking the lock: a run finished meanwhile may have failed.
            $latest = $this->latestRun($subject);
            if ($latest === null || $latest->id !== (int) $data['run_id'] || ! $latest->passed) {
                throw ValidationException::withMessages(['run_id' => __('Learner marking can be switched on only with the latest run on the current engine and profile versions, and only if it passed. Reload the page.')]);
            }
            $before = $gate->only(['enabled', 'marking_evaluation_run_id', 'engine_version', 'profile_version']);
            $gate->fill(['enabled' => true, 'marking_evaluation_run_id' => $latest->id, 'engine_version' => $latest->engine_version,
                'profile_version' => $latest->profile_version, 'approved_by' => $actor->id, 'approved_at' => now(), 'reason' => trim((string) $data['reason']),
                'changed_by' => $actor->id, 'revision' => $gate->revision + 1])->save();
            Audit::record('marking_subject_switched_on', $gate, $before, ['subject' => $subject, 'run_id' => $latest->id, 'engine_version' => $latest->engine_version,
                'profile_version' => $latest->profile_version, 'scripts' => $latest->script_count, 'reason' => $gate->reason, 'changed_by' => $actor->id]);

            return $gate;
        });
    }

    /** Switching learner marking off in a subject: an approver or an evaluator, with a reason. */
    public function switchOff(User $actor, string $subject, array $input): MarkingSubjectGate
    {
        $actor = app(AccuracyAccess::class)->hq($actor, AccuracyAccess::APPROVE, AccuracyAccess::EVALUATE);
        $subject = AccuracyAccess::subject($subject);
        $data = validator($input, self::switchOffRules())->validate();

        return DB::transaction(function () use ($actor, $subject, $data) {
            $gate = $this->locked($subject, (int) $data['expected_revision']);
            if (! $gate->enabled) {
                throw ValidationException::withMessages(['expected_revision' => __('Learner marking is already off in this subject.')]);
            }
            $gate->fill(['enabled' => false, 'reason' => trim((string) $data['reason']), 'changed_by' => $actor->id, 'revision' => $gate->revision + 1])->save();
            Audit::record('marking_subject_switched_off', $gate, ['enabled' => true], ['subject' => $subject, 'run_id' => $gate->marking_evaluation_run_id,
                'reason' => $gate->reason, 'changed_by' => $actor->id]);

            return $gate;
        });
    }

    /** The subject's gate row, created when missing, locked, at the revision the form was showing. */
    private function locked(string $subject, int $expected): MarkingSubjectGate
    {
        MarkingSubjectGate::query()->insertOrIgnore(['subject' => $subject, 'enabled' => false, 'revision' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $gate = MarkingSubjectGate::query()->where('subject', $subject)->lockForUpdate()->firstOrFail();
        if ($gate->revision !== $expected) {
            throw ValidationException::withMessages(['expected_revision' => __('Someone changed this subject\'s learner marking meanwhile. Reload the page.')]);
        }

        return $gate;
    }
}
