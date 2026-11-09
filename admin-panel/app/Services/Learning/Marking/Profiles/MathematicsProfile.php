<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Profiles;

use App\Services\Learning\Marking\SubjectProfile;

/** Mathematics: equivalent forms count, consistent accuracy (CA) follows through, units are optional unless the memo asks. */
final class MathematicsProfile extends SubjectProfile
{
    public function key(): string
    {
        return 'mathematics';
    }

    public function label(): string
    {
        return __('Mathematics');
    }

    public function version(): string
    {
        return 'mathematics-v1';
    }

    public function acceptsEquivalentForms(): bool
    {
        return true;
    }

    public function followsThrough(): bool
    {
        return true;
    }

    public function asksForForm(string $earns): bool
    {
        return (bool) preg_match('/factori[sz]|simplif|expand|in the form|standard form|surd|vereenvoudig|faktoriseer|brei uit|in die vorm/i', $earns);
    }

    public function conventions(): array
    {
        return [
            __('Equivalent forms earn an expression mark, unless the mark point asks for a particular form (simplified, factorised or expanded): then a teacher decides.'),
            __('Consistent accuracy (CA) marks follow through from the learner\'s own earlier answer when the later step is worked correctly from it.'),
            __('A unit is optional unless the memo has a units rule; a wrong unit loses the mark.'),
            __('In expressions, letters next to each other multiply: xy is x times y.'),
        ];
    }
}
