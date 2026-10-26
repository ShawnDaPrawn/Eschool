<?php

declare(strict_types=1);

namespace App\Services\FamilyForms;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Validation\ValidationException;
use Throwable;

/** School wall clocks must resolve to exactly one instant, never a guessed DST fold. */
class SchoolFormTime
{
    public function utc(string $wall, string $zone): string
    {
        try {
            $anchor = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $wall, 'UTC');
            abort_unless($anchor->format('Y-m-d\TH:i') === $wall, 422);
            $location = new DateTimeZone($zone);
            $transitions = $location->getTransitions($anchor->getTimestamp() - 172800, $anchor->getTimestamp() + 172800);
            $offsets = $transitions === false ? [$location->getOffset($anchor)] : array_column($transitions, 'offset');
            $matches = [];
            foreach (array_unique($offsets) as $offset) {
                $instant = $anchor->subSeconds($offset);
                if ($instant->setTimezone($location)->format('Y-m-d\TH:i') === $wall) {
                    $matches[$instant->getTimestamp()] = $instant->toIso8601String();
                }
            }
            if (count($matches) === 1) {
                return array_values($matches)[0];
            }
        } catch (Throwable) {
            // Only a bounded, static message may reach a private form refusal.
        }
        throw ValidationException::withMessages(['time' => 'Choose a valid, unambiguous school date and time.']);
    }
}
