<?php

declare(strict_types=1);

namespace App\Http\Requests\Pathways;

use App\Services\Pathways\CareerGuidance;
use Illuminate\Foundation\Http\FormRequest;

/** Read-only: the "what would it take" view takes its choices from the query. */
class CareerGuidanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(CareerGuidance::class)->actor($this->user());

        return true;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:80'], 'learner_id' => ['nullable', 'integer', 'min:1'], 'profile_id' => ['nullable', 'uuid']];
    }
}
