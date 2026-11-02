<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Accepting or declining an invitation to join a school has no input. Only a
 * signed-in, active account may answer; SchoolJoinInvitations checks under
 * lock that it is the invited account and that the invitation still stands.
 */
class AnswerJoinInvitationRequest extends FormRequest
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
