<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Services\People\Leavers\LeaverState;
use Illuminate\Foundation\Http\FormRequest;

/** End a leaver's read-only access today (Action Plan 1.21). */
class EndLeaverAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(LeaverState::EDIT) ?? false;
    }

    public function rules(): array
    {
        return ['confirm' => ['required', 'accepted']];
    }
}
