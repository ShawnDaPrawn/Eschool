<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

class PanelCurriculumRequest extends CurriculumRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('definition')) {
            try {
                $row = json_decode((string) $this->input('definition'), true, 32, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $row = null;
            }
            // The normal request whitelist still validates every nested value.
            if (is_array($row) && ! array_is_list($row)) {
                $this->merge($row);
            }
        }
    }
}
