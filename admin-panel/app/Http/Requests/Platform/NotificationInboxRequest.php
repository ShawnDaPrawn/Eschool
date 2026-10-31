<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

/** Reads only: the signed-in user's own inbox and one of their notices. */
class NotificationInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'between:1,1000']];
    }
}
