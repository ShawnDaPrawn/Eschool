<?php

declare(strict_types=1);

namespace App\Services\Learning;

/**
 * The seam for AI-assisted marking of short written practice answers
 * (Action Plan 3.11). It is switched OFF: no AI provider is qualified yet
 * (Action Plan 3.1), so this makes no provider call and suggests nothing.
 *
 * Today a short answer that matches an accepted answer (ignoring case,
 * spacing and punctuation) is marked at once, and every other answer waits
 * for the learner's teacher in the practice marking queue.
 *
 * After qualification, suggest() may ask the AI gateway (never a provider
 * directly) for a suggested mark and reason, which the queue shows to the
 * teacher. A suggestion never marks an answer by itself: the teacher's
 * decision is the mark, exactly as now.
 */
class ShortTextAssist
{
    public function enabled(): bool
    {
        return false;
    }

    /**
     * @return array{correct: bool, reason: string}|null always null while switched off
     */
    public function suggest(array $approvedDefinition, string $answer): ?array
    {
        return null;
    }
}
