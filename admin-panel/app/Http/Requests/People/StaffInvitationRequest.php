<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

/** Sending a staff invitation has no input; StaffInvitations checks who may send it. */
class StaffInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    public function rules(): array
    {
        return [];
    }
}
