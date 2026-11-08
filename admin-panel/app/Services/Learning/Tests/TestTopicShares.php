<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests;

use App\Models\User;
use App\Services\Learning\PastPaperLibrary;
use Illuminate\Validation\ValidationException;

/**
 * How many of a test's questions each topic gets. "even" splits them
 * evenly; "past_papers" uses the exam coach topic shares of the chosen
 * published past papers (each question's marks split across its topics, as
 * ExamStudyPlans weighs them), so a cycle test looks like the exam.
 */
class TestTopicShares
{
    public const WEIGHTINGS = ['even', 'past_papers'];

    /**
     * @param  list<int>  $topicIds
     * @param  list<string>  $paperVersionIds
     * @return array<int, int> topic id => integer weight
     */
    public function weights(User $actor, int $groupId, array $topicIds, string $weighting, array $paperVersionIds): array
    {
        if ($weighting === 'even') {
            return array_fill_keys($topicIds, 1);
        }
        $weights = array_fill_keys($topicIds, 0);
        foreach ($paperVersionIds as $versionId) {
            $version = app(PastPaperLibrary::class)->version($actor, $groupId, $versionId, true);
            abort_unless($version->state === 'published', 422, __('Choose published past papers.'));
            foreach ($version->definition['questions'] as $question) {
                foreach ($question['topic_ids'] as $topic) {
                    if (array_key_exists((int) $topic, $weights)) {
                        $weights[(int) $topic] += intdiv((int) $question['max_units'] * 2520, count($question['topic_ids']));
                    }
                }
            }
        }
        $weights = array_filter($weights, fn ($weight) => $weight > 0);
        if ($weights === []) {
            throw ValidationException::withMessages(['paper_version_ids' => __('The chosen past papers do not cover these topics. Choose other papers or split the questions evenly.')]);
        }

        return $weights;
    }

    /**
     * Largest-remainder split of $count over the weights, never giving a
     * topic more questions than it has ($available); what a topic cannot
     * take goes to the others in proportion.
     *
     * @param  array<int, int>  $weights
     * @param  array<int, int>  $available
     * @return array<int, int>
     */
    public function allocate(int $count, array $weights, array $available): array
    {
        ksort($weights);
        $shares = array_fill_keys(array_keys($weights), 0);
        $open = array_filter($weights, fn ($weight, $topic) => $weight > 0 && ($available[$topic] ?? 0) > 0, ARRAY_FILTER_USE_BOTH);
        $left = min($count, array_sum(array_intersect_key($available, $open)));
        while ($left > 0 && $open !== []) {
            $sum = array_sum($open);
            $round = [];
            $remainders = [];
            foreach ($open as $topic => $weight) {
                $round[$topic] = intdiv($left * $weight, $sum);
                $remainders[$topic] = ($left * $weight) % $sum;
            }
            $extra = $left - array_sum($round);
            uksort($remainders, fn ($a, $b) => [$remainders[$b], $a] <=> [$remainders[$a], $b]);
            foreach (array_slice(array_keys($remainders), 0, $extra) as $topic) {
                $round[$topic]++;
            }
            foreach ($round as $topic => $take) {
                $take = min($take, $available[$topic] - $shares[$topic]);
                $shares[$topic] += $take;
                $left -= $take;
                if ($shares[$topic] >= $available[$topic]) {
                    unset($open[$topic]);
                }
            }
        }

        return $shares;
    }
}
