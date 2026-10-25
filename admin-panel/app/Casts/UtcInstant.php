<?php

declare(strict_types=1);

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * SQL DATETIME stores no offset. These columns hold UTC, even while a selected
 * school's timezone changes Laravel's default date-cast interpretation.
 *
 * @implements CastsAttributes<CarbonImmutable|null, DateTimeInterface|string|null>
 */
class UtcInstant implements CastsAttributes, SerializesCastableAttributes
{
    private function instant(mixed $value): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->utc();
        }
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('Use a date-time instant.');
        }

        return CarbonImmutable::parse($value, 'UTC')->utc();
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $this->instant($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $this->instant($value)?->format($model->getDateFormat());
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $this->instant($value)?->toISOString();
    }
}
