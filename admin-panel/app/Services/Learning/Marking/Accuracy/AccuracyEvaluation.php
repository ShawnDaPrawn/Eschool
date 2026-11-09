<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Accuracy;

use App\Models\Learning\Marking\MarkingEvaluationPaper;
use App\Models\Learning\Marking\MarkingEvaluationRun;
use App\Models\Learning\Marking\MarkingEvaluationScript;
use App\Models\User;
use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\Marking\MarkingEngine;
use App\Services\Learning\Marking\SubjectProfiles;
use App\Services\Platform\Audit\Audit;
use Illuminate\Validation\ValidationException;

/**
 * Runs the marking engine over every evaluation script of a subject and
 * stores the agreement with the teachers (Action Plan 3.18), tied to the
 * engine and profile versions measured and the settings of the day. The
 * engine is the one learners get (judgement switched off), so a run measures
 * exactly what learners would receive.
 */
class AccuracyEvaluation
{
    private const CHUNK = 200;

    public function run(User $actor, string $subject): MarkingEvaluationRun
    {
        $actor = app(AccuracyAccess::class)->hq($actor, AccuracyAccess::EVALUATE);
        $subject = AccuracyAccess::subject($subject);
        $papers = MarkingEvaluationPaper::query()->where('subject', $subject)->orderBy('id')->get()->keyBy('id');
        $count = $papers->isEmpty() ? 0 : MarkingEvaluationScript::query()->whereIn('marking_evaluation_paper_id', $papers->keys())->count();
        $limit = (int) config('marking.gate.max_scripts_per_run', 5000);
        if ($count === 0) {
            throw ValidationException::withMessages(['subject' => __('There are no evaluation scripts for this subject yet. Import some first.')]);
        }
        if ($count > $limit) {
            throw ValidationException::withMessages(['subject' => __('This subject has more than :limit scripts, the most one run measures.', ['limit' => $limit])]);
        }

        $versions = app(MarkingVersions::class);
        $profile = SubjectProfiles::key($subject);
        $engine = app(MarkingEngine::class);
        $settings = AgreementMetrics::settings();
        $metrics = new AgreementMetrics($settings);
        $fingerprint = hash_init('sha256');
        $questions = $papers->map(fn (MarkingEvaluationPaper $paper) => collect($paper->questions)->keyBy('number')->all())->all();

        MarkingEvaluationScript::query()->whereIn('marking_evaluation_paper_id', $papers->keys())
            ->chunkById(self::CHUNK, function ($scripts) use ($papers, $questions, $engine, $profile, $metrics, $fingerprint) {
                foreach ($scripts as $script) {
                    $paper = $papers[$script->marking_evaluation_paper_id];
                    hash_update($fingerprint, $script->id.':'.$script->reference_hash.':'.$paper->fingerprint.':'.hash('sha256', json_encode($script->questions, JSON_THROW_ON_ERROR))."\n");
                    $engineTotal = 0;
                    foreach ($script->questions as $answered) {
                        $question = $questions[$paper->id][$answered['number']];
                        $result = $engine->mark($question, LearnerAnswer::fromText((string) $answered['answer']), $profile);
                        $engineTotal += (int) $result['awarded_units'];
                        foreach ($result['points'] as $i => $row) {
                            $point = $question['points'][$i];
                            $metrics->point($paper->id.':'.$question['number'].':'.($i + 1), [
                                'paper' => (string) $paper->code, 'question' => (string) $question['number'], 'index' => $i + 1, 'earns' => (string) $point['earns'],
                                'type' => (string) $point['type'], 'rule' => (string) $row['rule'], 'mark_units' => (int) $point['mark_units'],
                            ], $row, (int) $answered['marks'][$i]);
                        }
                    }
                    $metrics->script($engineTotal, (int) $script->teacher_units, (int) $script->max_units);
                }
            });

        $result = $metrics->result();
        $run = MarkingEvaluationRun::create([
            'subject' => $subject, 'engine_version' => $versions->engine(), 'profile_version' => $versions->profile($subject),
            'script_count' => $result['script_count'], 'point_count' => $result['point_count'], 'passed' => $result['passed'],
            'failures' => $result['failures'], 'metrics' => $result['metrics'], 'point_stats' => $result['point_stats'], 'settings' => $settings,
            'scripts_fingerprint' => hash_final($fingerprint), 'started_by' => $actor->id,
        ]);
        Audit::record('marking_evaluation_run', $run, [], ['subject' => $subject, 'engine_version' => $run->engine_version, 'profile_version' => $run->profile_version,
            'scripts' => $run->script_count, 'passed' => $run->passed, 'failures' => $run->failures, 'changed_by' => $actor->id]);

        return $run;
    }

    /** Plain words for a run's failure codes. */
    public static function failure(string $code, array $settings): string
    {
        return match ($code) {
            'too_few_scripts' => __('Fewer than :count teacher-marked scripts.', ['count' => (string) $settings['min_scripts']]),
            'point_agreement' => __('Mark point agreement is below :percent%.', ['percent' => (string) $settings['point_agreement_percent']]),
            'script_agreement' => __('Script total agreement is below :percent%.', ['percent' => (string) $settings['script_agreement_percent']]),
            'bias' => __('The engine marks systematically higher or lower than teachers by more than :percent%.', ['percent' => (string) $settings['max_bias_percent']]),
            default => $code,
        };
    }
}
