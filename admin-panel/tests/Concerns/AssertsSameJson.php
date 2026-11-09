<?php

declare(strict_types=1);

namespace Tests\Concerns;

trait AssertsSameJson
{
    /** JSON object order differs between MySQL and MariaDB; list order and types matter. */
    private function assertSameJson(array $expected, array $actual): void
    {
        $canonical = function (array $value) use (&$canonical): array {
            if (! array_is_list($value)) {
                ksort($value);
            }
            foreach ($value as &$item) {
                if (is_array($item)) {
                    $item = $canonical($item);
                }
            }

            return $value;
        };
        $this->assertSame($canonical($expected), $canonical($actual));
    }
}
