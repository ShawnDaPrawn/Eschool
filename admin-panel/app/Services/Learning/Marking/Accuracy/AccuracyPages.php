<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Accuracy;

use App\Models\Learning\Marking\MarkingEvaluationPaper;
use App\Models\Learning\Marking\MarkingEvaluationRun;
use App\Models\Learning\Marking\MarkingEvaluationScript;
use App\Models\User;
use App\Services\Academics\AcademicNumbers;
use App\Services\Learning\Marking\SubjectProfiles;

/** What the HQ marking accuracy pages show (Action Plan 3.18): evaluation data only, never a school or a learner. */
class AccuracyPages
{
    private const RUNS_SHOWN = 20;

    public const WORST_SHOWN = 25;

    public function index(User $actor): array
    {
        $actor = app(AccuracyAccess::class)->hq($actor, AccuracyAccess::EVALUATE, AccuracyAccess::APPROVE);
        $access = app(AccuracyAccess::class);
        $versions = app(MarkingVersions::class);
        $gate = app(MarkingGate::class);
        $scripts = MarkingEvaluationScript::query()->selectRaw('marking_evaluation_paper_id, count(*) as scripts')->groupBy('marking_evaluation_paper_id')
            ->pluck('scripts', 'marking_evaluation_paper_id')->all();
        $papers = MarkingEvaluationPaper::query()->orderBy('subject')->orderBy('code')->get();
        $subjects = [];
        foreach (AccuracyAccess::subjects() as $subject) {
            $latest = $gate->latestRun($subject);
            $own = $papers->where('subject', $subject);
            $subjects[] = ['key' => $subject, 'label' => SubjectProfiles::key($subject)->label(), 'profile_version' => $versions->profile($subject),
                'papers' => $own->count(), 'scripts' => (int) array_sum(array_map(fn ($id) => (int) ($scripts[$id] ?? 0), $own->pluck('id')->all())),
                'latest' => $latest === null ? null : $this->runSummary($latest), 'gate' => $gate->state($subject)];
        }
        $runs = MarkingEvaluationRun::query()->orderByDesc('id')->limit(self::RUNS_SHOWN)->get()->map(fn ($run) => $this->runSummary($run))->all();

        return ['subjects' => $subjects, 'runs' => $runs, 'engine_version' => $versions->engine(), 'settings' => AgreementMetrics::settings(),
            'papers' => $papers->map(fn (MarkingEvaluationPaper $paper) => ['id' => $paper->id, 'subject' => $paper->subject, 'code' => $paper->code,
                'title' => $paper->title, 'source' => $paper->source, 'questions' => count($paper->questions), 'max_marks' => AcademicNumbers::decimal($paper->max_units),
                'scripts' => (int) ($scripts[$paper->id] ?? 0)])->all(),
            'can_evaluate' => $access->can($actor, AccuracyAccess::EVALUATE), 'can_approve' => $access->can($actor, AccuracyAccess::APPROVE)];
    }

    public function run(User $actor, int $id): array
    {
        app(AccuracyAccess::class)->hq($actor, AccuracyAccess::EVALUATE, AccuracyAccess::APPROVE);
        $run = MarkingEvaluationRun::query()->findOrFail($id);
        $versions = app(MarkingVersions::class);

        return ['run' => $this->runSummary($run), 'metrics' => $run->metrics, 'settings' => $run->settings,
            'failures' => array_map(fn ($code) => AccuracyEvaluation::failure((string) $code, $run->settings), $run->failures),
            'current' => $run->engine_version === $versions->engine() && $run->profile_version === $versions->profile($run->subject),
            'worst' => array_slice(array_values(array_filter($run->point_stats, fn ($stat) => (int) $stat['within'] < (int) $stat['count'])), 0, self::WORST_SHOWN)];
    }

    private function runSummary(MarkingEvaluationRun $run): array
    {
        return ['id' => $run->id, 'subject' => $run->subject, 'label' => SubjectProfiles::key($run->subject)->label(), 'engine_version' => $run->engine_version,
            'profile_version' => $run->profile_version, 'scripts' => $run->script_count, 'points' => $run->point_count, 'passed' => $run->passed,
            'point_agreement' => $run->metrics['points']['within_percent'] ?? null, 'script_agreement' => $run->metrics['scripts']['within_percent'] ?? null,
            'bias_percent' => $run->metrics['scripts']['bias_percent'] ?? null, 'confirmed' => (bool) ($run->settings['owner_confirmed'] ?? false),
            'created_at' => $run->created_at];
    }
}
