<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

/**
 * Keeps a seed model's float money columns and their *_cents twins in step
 * until the floats are dropped (with the ledger work in a later phase). The model lists the pairs in
 * moneyColumns(), float column => cents column.
 *
 * - Cents are the truth. Reading the float attribute (or serialising the
 *   model for the v1 API) gives the cents as a float in currency units,
 *   the value and type the float column gave; it falls back to the float
 *   only for a row whose cents are still empty.
 * - Writing either attribute writes both: a float, int or numeric string to
 *   the float attribute is converted to cents (half up); Money or whole
 *   cents to the cents attribute set the float.
 * - Saving fills cents the row is still missing from its float.
 * - The cents columns are cast to Money and hidden, so v1 payloads keep
 *   exactly their fields.
 * - money('amount') returns the amount as Money.
 *
 * Query-builder writes (insert, update) bypass all this: write both columns
 * there (LegacyAmount::toCents()).
 */
trait HasMoneyColumns
{
    /** @return array<string, string> float column => cents column */
    abstract public function moneyColumns(): array;

    public static function bootHasMoneyColumns(): void
    {
        static::saving(function (self $model) {
            foreach ($model->moneyColumns() as $float => $cents) {
                if (($model->attributes[$cents] ?? null) === null && ($model->attributes[$float] ?? null) !== null) {
                    $model->attributes[$cents] = LegacyAmount::toCents($model->attributes[$float]);
                }
            }
        });
    }

    public function initializeHasMoneyColumns(): void
    {
        $cents = array_values($this->moneyColumns());
        $this->mergeCasts(array_fill_keys($cents, MoneyCast::class));
        $this->hidden = array_values(array_unique(array_merge($this->hidden, $cents)));
    }

    /** The amount in a money column (named by its float column) as Money. */
    public function money(string $column): ?Money
    {
        $centsColumn = $this->moneyColumns()[$column] ?? null;
        if ($centsColumn === null) {
            throw new \InvalidArgumentException(static::class." has no money column {$column}");
        }
        $cents = $this->attributes[$centsColumn] ?? null;
        if ($cents === null) {
            $cents = LegacyAmount::toCents($this->attributes[$column] ?? null);
        }

        return $cents === null ? null : Money::ofCents((int) $cents, method_exists($this, 'moneyCurrency') ? $this->moneyCurrency() : null);
    }

    public function getAttributeValue($key)
    {
        $centsColumn = $this->moneyColumns()[$key] ?? null;
        if ($centsColumn !== null && array_key_exists($key, $this->attributes)) {
            return $this->legacyAmount($key, $centsColumn);
        }

        return parent::getAttributeValue($key);
    }

    public function setAttribute($key, $value)
    {
        $columns = $this->moneyColumns();
        if (isset($columns[$key])) {
            parent::setAttribute($key, $value);
            $this->attributes[$columns[$key]] = LegacyAmount::toCents($value instanceof Money ? $value : $this->attributes[$key]);
            if ($value instanceof Money) {
                $this->attributes[$key] = LegacyAmount::fromCents($value->cents);
            }

            return $this;
        }

        $float = array_search($key, $columns, true);
        if ($float !== false) {
            parent::setAttribute($key, $value);
            $this->attributes[$float] = LegacyAmount::fromCents($this->attributes[$key] ?? null);

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        foreach ($this->moneyColumns() as $float => $cents) {
            if (array_key_exists($float, $attributes)) {
                $attributes[$float] = $this->legacyAmount($float, $cents);
            }
        }

        return $attributes;
    }

    private function legacyAmount(string $float, string $centsColumn): ?float
    {
        $cents = $this->attributes[$centsColumn] ?? null;
        if ($cents !== null) {
            return LegacyAmount::fromCents($cents);
        }
        $value = $this->attributes[$float] ?? null;

        return $value === null ? null : (float) $value;
    }
}
