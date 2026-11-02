<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

/** Withdrawing a waiting invitation has no input; SchoolJoinInvitations checks the role it gives. */
class WithdrawJoinInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (int) $user->status === 1 && ($user->can('staff-create') || $user->can('teacher-create'));
    }

    public function rules(): array
    {
        return [];
    }
}
