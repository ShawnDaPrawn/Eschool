<?php

declare(strict_types=1);

namespace App\Http\Requests\Passport;

use App\Services\Passport\PassportExports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A learner's own passport download: only the format is chosen. */
class PassportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // PassportExports decides, before anything is read.
    }

    public function rules(): array
    {
        return ['format' => ['sometimes', 'string', Rule::in(PassportExports::FORMATS)]];
    }
}
