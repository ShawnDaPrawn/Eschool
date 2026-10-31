<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Services\People\LuritsOffice;
use Illuminate\Foundation\Http\FormRequest;

class RecordLuritsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(LuritsOffice::PERMISSION) ?? false;
    }

    public function rules(): array
    {
        return ['admission_no' => ['required', 'string', 'max:64'], 'lurits' => ['required', 'string', 'max:32']];
    }
}
