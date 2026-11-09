<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

use App\Services\Learning\Marking\Accuracy\MarkingGate;

/**
 * A subject's marking conventions (Action Plan 3.16). The memo's rules decide
 * each mark; a profile says how they are read in the subject: whether
 * equivalent forms count, whether consistent-accuracy marks follow through,
 * whether a unit the memo gives is required, how rounding and directions are
 * treated, and how a run of letters reads in an expression.
 *
 * Learner marking for a subject stays off until the subject passes the
 * accuracy gate (Action Plan 3.18) on this profile's version() and the
 * engine's version, and an HQ approver switches it on (MarkingGate).
 * engineering/specs/learning/marking-engine.md,
 * engineering/specs/learning/marking-accuracy-gate.md.
 */
abstract class SubjectProfile
{
    abstract public function key(): string;

    abstract public function label(): string;

    /**
     * The version of this profile's conventions. Change it whenever the profile
     * marks differently: a passing accuracy run counts only for the version it
     * measured, so learner marking in the subject stops until a new run passes.
     * MarkingVersionsTest pins each version to the code it was measured on.
     */
    abstract public function version(): string;

    /** Whether an equivalent expression or rearranged equation earns an expression rule. */
    public function acceptsEquivalentForms(): bool
    {
        return false;
    }

    /** Whether a consistent-accuracy mark may follow through from the learner's own wrong earlier answer. */
    public function followsThrough(): bool
    {
        return false;
    }

    /** Whether an answer with no unit loses a value mark when the memo gives one. */
    public function requiresUnits(): bool
    {
        return false;
    }

    /** Fewest decimal places at which a correctly rounded answer still earns an exact value; null: only the memo's tolerance. */
    public function roundingDecimals(): ?int
    {
        return null;
    }

    /** Whether a value mark whose memo point names a direction needs that direction. */
    public function requiresDirection(): bool
    {
        return false;
    }

    /** Whether formula marks need a calculation and a different formula sends the calculation to a teacher. */
    public function formulaFirst(): bool
    {
        return false;
    }

    /** "letters" (xy is x times y) or "symbols" (vf, Fnet are one quantity each). */
    public function identifiers(): string
    {
        return 'letters';
    }

    /** Whether the mark point asks for a particular form, so an equivalent but different form needs a teacher. */
    public function asksForForm(string $earns): bool
    {
        return false;
    }

    /** Plain descriptions of the conventions, for the teacher preview. @return list<string> */
    abstract public function conventions(): array;

    /** Learner marking is off until the subject passes the accuracy gate and an HQ approver switches it on (Action Plan 3.18). */
    public function learnerMarkingEnabled(): bool
    {
        return app(MarkingGate::class)->open($this->key());
    }
}
