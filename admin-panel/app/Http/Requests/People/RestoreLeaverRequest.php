<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Services\People\Leavers\LeaverState;
use Illuminate\Foundation\Http\FormRequest;

/** Bring a leaver back as a returning learner (Action Plan 1.21). */
class RestoreLeaverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(LeaverState::EDIT) ?? false;
    }

    public function rules(): array
    {
        return ['homeroom_id' => ['required', 'integer', 'min:1']];
    }
}
