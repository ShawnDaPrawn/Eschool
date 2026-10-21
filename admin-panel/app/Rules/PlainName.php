<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * A name as people write it: "Grade 1", "Grade R-A", "Morning Shift",
 * "Sixième (B)". Letters and digits in any script, spaces and ordinary
 * punctuation; no markup characters.
 */
class PlainName implements Rule
{
    public const PATTERN = "/^[\\pL\\pN][\\pL\\pN\\pM .,'&()\\/+#_-]*$/u";

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return is_string($value) && mb_strlen($value) <= 191 && preg_match(self::PATTERN, $value) === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('name_has_invalid_characters');
    }
}
