<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;

/**
 * The single, narrow path by which a school's request may write library rows
 * (Action Plan 5.4): App\Services\Learning\Library\SharedLibrary opens it
 * while it copies one scanned, confirmed contribution into the library as a
 * version waiting for review, or marks a contributed item withdrawn. Inside
 * it only a source, an item or a version may be saved; every other library
 * row, and every delete, is still refused (PlatformLibraryRow).
 */
final class ContributionGate
{
    private const MODELS = [LibrarySource::class, LibraryItem::class, LibraryItemVersion::class];

    private static int $open = 0;

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        self::$open++;
        try {
            return $callback();
        } finally {
            self::$open--;
        }
    }

    public static function allows(Model $row): bool
    {
        return self::$open > 0 && in_array($row::class, self::MODELS, true);
    }
}
