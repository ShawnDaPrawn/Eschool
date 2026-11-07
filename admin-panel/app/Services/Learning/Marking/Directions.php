<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

/**
 * Directions of vector answers (Action Plan 3.16), in English and Afrikaans:
 * "12 N to the left", "5 m·s⁻¹ noord", "upwards".
 */
final class Directions
{
    private const WORDS = [
        'up' => 'up', 'upward' => 'up', 'upwards' => 'up', 'opwaarts' => 'up', 'boontoe' => 'up',
        'down' => 'down', 'downward' => 'down', 'downwards' => 'down', 'afwaarts' => 'down', 'ondertoe' => 'down',
        'left' => 'left', 'links' => 'left', 'right' => 'right', 'regs' => 'right',
        'north' => 'north', 'noord' => 'north', 'noorde' => 'north', 'south' => 'south', 'suid' => 'south', 'suide' => 'south',
        'east' => 'east', 'oos' => 'east', 'ooste' => 'east', 'west' => 'west', 'wes' => 'west', 'weste' => 'west',
        'forward' => 'forward', 'forwards' => 'forward', 'vorentoe' => 'forward', 'backward' => 'backward', 'backwards' => 'backward', 'agtertoe' => 'backward',
    ];

    /** The directions named in the text, without repeats. @return list<string> */
    public static function in(string $text): array
    {
        $found = [];
        foreach (explode(' ', AnswerText::words($text)) as $word) {
            if (isset(self::WORDS[$word]) && ! in_array(self::WORDS[$word], $found, true)) {
                $found[] = self::WORDS[$word];
            }
        }

        return $found;
    }
}
