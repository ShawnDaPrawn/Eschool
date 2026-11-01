<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\People\ProfilePolicy;
use Illuminate\Foundation\Http\FormRequest;

class CalendarFeedRequest extends FormRequest
{
    /** The service checks the learner; here only that the actor is current at this school. */
    public function authorize(): bool
    {
        app(ProfilePolicy::class)->actor($this->user());

        return true;
    }

    public function rules(): array
    {
        return $this->isMethod('POST') ? ['learner_id' => ['required', 'integer', 'min:1'], 'include_lessons' => ['sometimes', 'boolean']] : [];
    }
}
