<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

/**
 * A subject's marking conventions (Action Plan 3.16). The memo's rules decide
 * each mark; a profile says how they are read in the subject: whether
 * equivalent forms count, whether consistent-accuracy marks follow through,
 * whether a unit the memo gives is required, how rounding and directions are
 * treated, and how a run of letters reads in an expression.
 *
 * Learner marking for a subject stays off until the subject passes the
 * accuracy gate (Action Plan 3.18): config('marking.learner_marking').
 * engineering/specs/learning/marking-engine.md.
 */
abstract class SubjectProfile
{
    abstract public function key(): string;

    abstract public function label(): string;

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

    /** Learner marking is off until the subject passes the accuracy gate; only a literal true switches it on. */
    public function learnerMarkingEnabled(): bool
    {
        return config('marking.learner_marking.'.$this->key()) === true;
    }
}
