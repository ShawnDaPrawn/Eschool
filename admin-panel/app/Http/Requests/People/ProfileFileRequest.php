<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Services\People\Profiles\ProfileFiles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    public function rules(): array
    {
        return ['profile_type' => ['required', Rule::in(config('profile_fields.types'))], ...ProfileFiles::rules()];
    }
}
