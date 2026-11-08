<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

/**
 * How one mark point was decided (Action Plan 3.16): awarded, not awarded, or
 * left for a teacher; who decided it (a memo rule, follow-through from the
 * learner's own earlier answer, or a teacher); a plain reason that never quotes
 * the memo's expected answer; a confidence from 0 to 1; and the answer line it
 * rests on (1-based in output).
 */
final class Outcome
{
    public const AWARDED = 'awarded';

    public const NOT_AWARDED = 'not_awarded';

    public const NEEDS_TEACHER = 'needs_teacher';

    public function __construct(
        public readonly string $status,
        public readonly string $decidedBy,
        public readonly string $reason,
        public readonly float $confidence,
        public readonly ?int $line = null,
    ) {}

    public static function awarded(string $reason, float $confidence, ?int $line = null, string $decidedBy = 'rule'): self
    {
        return new self(self::AWARDED, $decidedBy, $reason, $confidence, $line);
    }

    public static function notAwarded(string $reason, float $confidence, ?int $line = null): self
    {
        return new self(self::NOT_AWARDED, 'rule', $reason, $confidence, $line);
    }

    public static function needsTeacher(string $reason, float $confidence = 0.2, ?int $line = null): self
    {
        return new self(self::NEEDS_TEACHER, 'teacher', $reason, min($confidence, 0.4), $line);
    }

    /** Ordering when several readings of an answer decide a point differently: an award first. */
    public function rank(): int
    {
        return match ($this->status) {
            self::AWARDED => 3,
            self::NEEDS_TEACHER => 2,
            default => 1,
        } * 10 + (int) round($this->confidence * 9);
    }

    /** @param list<?self> $outcomes */
    public static function best(array $outcomes): ?self
    {
        $best = null;
        foreach ($outcomes as $outcome) {
            if ($outcome !== null && ($best === null || $outcome->rank() > $best->rank())) {
                $best = $outcome;
            }
        }

        return $best;
    }
}
