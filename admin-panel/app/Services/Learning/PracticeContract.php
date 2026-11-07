<?php

declare(strict_types=1);

namespace App\Services\Learning;

use Illuminate\Http\Request;

/**
 * Which daily practice set contract a client reads (Action Plan 3.11).
 *
 * A client opts in to the answer-free contract with the request header
 * `X-Practice-Contract: 2`: sets carry `question` only, every question kind,
 * and the answers and explanation arrive in `review.solution` once the answer
 * is recorded. A client that sends no header (the installed apps today) gets
 * the legacy contract exactly as before 3.11: `definition` with its answers
 * and explanation, `choice` and `number` questions only. The legacy contract
 * keeps the old offline answer loophole for those clients only; switch it off
 * with `practice.legacy_set_contract` (PRACTICE_LEGACY_SET_CONTRACT=false)
 * once the apps are updated, and legacy requests are then refused with 426.
 * engineering/specs/learning/question-types.md.
 */
class PracticeContract
{
    public const HEADER = 'X-Practice-Contract';

    public const LEGACY = 1;

    public const ANSWER_FREE = 2;

    public function fromRequest(Request $request): int
    {
        $value = $request->header(self::HEADER);
        abort_unless($value === null || in_array($value, ['1', '2'], true), 422, 'Send X-Practice-Contract as 1 or 2.');

        return $value === '2' ? self::ANSWER_FREE : self::LEGACY;
    }

    /** Refuse the legacy contract once it is switched off. */
    public function allow(int $contract): void
    {
        abort_if($contract === self::LEGACY && ! $this->legacyEnabled(), 426, 'Update the app to keep practising.');
    }

    public function legacyEnabled(): bool
    {
        return (bool) config('practice.legacy_set_contract', true);
    }

    /** @return list<string> the question kinds a client on this contract can read and answer */
    public function kinds(int $contract): array
    {
        return $contract === self::ANSWER_FREE ? PracticeAnswers::KINDS : PracticeAnswers::SINGLE;
    }
}
