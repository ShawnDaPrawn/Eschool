<?php

declare(strict_types=1);

namespace App\Services\Platform\Groups;

/**
 * Small-number suppression for figures shown outside a school (k-anonymity
 * style; school-groups.md). k is config('school_groups.min_count').
 *
 *  - A count of people from 1 to k - 1 is hidden ("fewer than k"); 0 and k or
 *    more are shown.
 *  - A share is hidden, with the count behind it, when its base is under k,
 *    or when the people it counts or the people it leaves out number 1 to
 *    k - 1 (so "all but three" is hidden as well as "three").
 *  - A total over several schools is hidden when any school's figure in it is
 *    hidden, so a hidden figure cannot be worked out by subtraction.
 */
final class SmallNumbers
{
    public function __construct(private readonly int $k) {}

    public static function fromConfig(): self
    {
        return new self(max(1, (int) config('school_groups.min_count', 10)));
    }

    public function k(): int
    {
        return $this->k;
    }

    public function small(int $n): bool
    {
        return $n > 0 && $n < $this->k;
    }

    /** @return array{value: ?int, hidden: bool} */
    public function count(int $n, bool $forceHidden = false): array
    {
        $hidden = $forceHidden || $this->small($n);

        return ['value' => $hidden ? null : $n, 'hidden' => $hidden];
    }

    /**
     * A share of people: $count of $of, as whole basis points (37.5 % is 3750).
     *
     * @return array{count: ?int, of: ?int, percent_bp: ?int, hidden: bool}
     */
    public function share(int $count, int $of, bool $forceHidden = false): array
    {
        $hidden = $forceHidden || $of < $this->k || $this->small($count) || $this->small($of - $count);

        return ['count' => $hidden ? null : $count, 'of' => $this->small($of) || $forceHidden ? null : $of,
            'percent_bp' => $hidden || $of === 0 ? null : intdiv($count * 10000, $of), 'hidden' => $hidden];
    }

    /**
     * A rate over a base of people that is not itself a count of people (half
     * days present over recorded days), hidden when the people it covers are
     * under k.
     *
     * @return array{percent_bp: ?int, hidden: bool, available: bool}
     */
    public function rate(int $numerator, int $denominator, int $people, bool $forceHidden = false): array
    {
        $hidden = $forceHidden || $people < $this->k;

        return ['percent_bp' => $hidden || $denominator === 0 ? null : intdiv($numerator * 10000, $denominator), 'hidden' => $hidden, 'available' => $denominator > 0];
    }
}
