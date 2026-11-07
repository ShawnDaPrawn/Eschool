<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\Badges;

use InvalidArgumentException;
use stdClass;

/**
 * The JSON Canonicalization Scheme (RFC 8785) that the eddsa-jcs-2022
 * cryptosuite signs: object members sorted by their UTF-16 code units, no
 * whitespace, minimal string escaping. Objects are stdClass or associative
 * arrays; a JSON document read for verification is decoded with objects, so
 * an empty object and an empty list stay apart. Numbers other than integers
 * are refused: NavigateEd credentials never contain them.
 */
final class Jcs
{
    public static function encode(mixed $value): string
    {
        if ($value instanceof stdClass) {
            return self::object(get_object_vars($value));
        }
        if (is_array($value)) {
            return array_is_list($value) ? '['.implode(',', array_map(self::encode(...), $value)).']' : self::object($value);
        }
        if (is_string($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS | JSON_THROW_ON_ERROR);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return 'null';
        }
        if (is_int($value) && abs($value) <= 9007199254740991) {
            return (string) $value;
        }
        if (is_float($value) && is_finite($value) && floor($value) === $value && abs($value) <= 9007199254740991) {
            return (string) (int) $value;
        }
        throw new InvalidArgumentException('This value cannot be canonicalised.');
    }

    /** @param array<array-key, mixed> $members */
    private static function object(array $members): string
    {
        $keys = array_map(strval(...), array_keys($members));
        usort($keys, fn (string $a, string $b): int => strcmp(self::utf16($a), self::utf16($b)));
        $parts = [];
        foreach ($keys as $key) {
            $parts[] = self::encode($key).':'.self::encode($members[$key]);
        }

        return '{'.implode(',', $parts).'}';
    }

    private static function utf16(string $key): string
    {
        if (! mb_check_encoding($key, 'UTF-8')) {
            throw new InvalidArgumentException('This value cannot be canonicalised.');
        }

        return (string) mb_convert_encoding($key, 'UTF-16BE', 'UTF-8');
    }
}
