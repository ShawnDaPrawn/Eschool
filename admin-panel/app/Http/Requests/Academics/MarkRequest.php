<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Services\Academics\MarkModeration;
use App\Services\Academics\MarkRecording;
use App\Services\Academics\MarkReleases;
use Illuminate\Foundation\Http\FormRequest;

class MarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    } // Fresh context policy middleware precedes idempotency and validation.

    public function rules(): array
    {
        $name = (string) $this->route()->getName();
        if (str_ends_with($name, '.periods')) {
            return [];
        }
        if (str_ends_with($name, '.family')) {
            return ['session_year_id' => ['required', 'integer', 'min:1'], 'term_id' => ['sometimes', 'nullable', 'integer', 'min:1']];
        }
        if (str_ends_with($name, '.matrix')) {
            return ['term_id' => ['required', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'between:1,200']];
        }
        if (str_ends_with($name, '.moderate')) {
            return MarkModeration::rules();
        }
        if (str_contains($name, '.release')) {
            $rules = MarkReleases::rules();
            if (str_ends_with($name, '.release')) {
                $rules['fingerprint'][0] = 'required';
            }

            return $rules;
        }

        return MarkRecording::rules();
    }
}
