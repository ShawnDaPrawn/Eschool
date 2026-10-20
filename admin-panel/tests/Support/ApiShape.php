<?php

namespace Tests\Support;

/**
 * The shape of a decoded JSON response: every object's keys and every value's
 * type, with the items of a list merged into one shape. Values themselves are
 * left out, so dates, IDs and timestamps don't make it change from run to run,
 * but a renamed or missing key, or a value changing type, does.
 */
class ApiShape
{
    public static function of(mixed $value): mixed
    {
        if (is_array($value)) {
            if ($value === []) {
                return [];
            }
            if (array_is_list($value)) {
                return [array_reduce(array_map(self::of(...), $value), self::merge(...), self::NONE)];
            }
            $shape = [];
            foreach ($value as $key => $item) {
                $shape[$key] = self::of($item);
            }
            ksort($shape, SORT_STRING);

            return $shape;
        }

        return match (true) {
            $value === null => 'null',
            is_bool($value) => 'bool',
            // JSON doesn't keep 5.0 apart from 5.
            is_int($value), is_float($value) => 'number',
            default => 'string',
        };
    }

    private const NONE = '#none';

    private static function merge(mixed $a, mixed $b): mixed
    {
        if ($a === self::NONE || $a === $b) {
            return $b;
        }
        // Two objects: merge key by key.
        if (is_array($a) && is_array($b) && ! array_is_list($a) && ! array_is_list($b)) {
            foreach ($b as $key => $shape) {
                $a[$key] = array_key_exists($key, $a) ? self::merge($a[$key], $shape) : $shape;
            }
            ksort($a, SORT_STRING);

            return $a;
        }
        // An object or list that is sometimes null keeps its shape.
        if ($a === 'null' || $b === 'null') {
            $other = $a === 'null' ? $b : $a;
            if (is_array($other)) {
                if (array_is_list($other)) {
                    return $other;
                }
                $other['(or null)'] = true;
                ksort($other, SORT_STRING);

                return $other;
            }
        }
        // Two lists: an empty one adds nothing.
        if (is_array($a) && is_array($b) && array_is_list($a) && array_is_list($b)) {
            return $a === [] ? $b : ($b === [] ? $a : [self::merge($a[0], $b[0])]);
        }

        // Anything else: a union of the types, e.g. "null|string".
        $types = array_unique(array_merge(self::types($a), self::types($b)));
        sort($types);

        return implode('|', $types);
    }

    /** @return list<string> */
    private static function types(mixed $shape): array
    {
        if (is_array($shape)) {
            return [array_is_list($shape) ? 'list' : 'object'];
        }

        return explode('|', $shape);
    }
}
