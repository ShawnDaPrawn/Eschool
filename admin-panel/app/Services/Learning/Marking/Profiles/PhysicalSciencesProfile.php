<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Profiles;

use App\Services\Learning\Marking\SubjectProfile;

/**
 * Physical Sciences: the unit the memo gives is required, a named direction is
 * required, answers rounded to at least two decimal places are accepted, formula
 * marks come first, and consistent accuracy follows through.
 */
final class PhysicalSciencesProfile extends SubjectProfile
{
    public function key(): string
    {
        return 'physical_sciences';
    }

    public function label(): string
    {
        return __('Physical Sciences');
    }

    public function version(): string
    {
        return 'physical_sciences-v1';
    }

    public function acceptsEquivalentForms(): bool
    {
        return true;
    }

    public function followsThrough(): bool
    {
        return true;
    }

    public function requiresUnits(): bool
    {
        return true;
    }

    public function roundingDecimals(): ?int
    {
        return 2;
    }

    public function requiresDirection(): bool
    {
        return true;
    }

    public function formulaFirst(): bool
    {
        return true;
    }

    public function identifiers(): string
    {
        return 'symbols';
    }

    public function conventions(): array
    {
        return [
            __('A value mark needs the unit when the memo gives one; an equivalent SI unit is converted (2,5 km for 2 500 m).'),
            __('When the mark point names a direction (to the left, upwards, east), the answer must state it.'),
            __('A final answer correctly rounded to at least two decimal places earns the value mark.'),
            __('Formula first: a formula mark needs a calculation, and when a different formula is written the calculation marks go to a teacher.'),
            __('Consistent accuracy marks follow through from the learner\'s own earlier answer.'),
            __('Symbols are quantities: vf, Fnet and Ek are one quantity each.'),
        ];
    }
}
