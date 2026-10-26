<?php

declare(strict_types=1);

namespace App\Http\Requests\Ai;

use App\Http\Controllers\Panel\Ai\AiReviewPanelController;
use App\Services\Ai\Quarantine\AiReviewPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AiReviewPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(AiReviewPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? [] : ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'],
            'decision' => ['required', 'in:reviewed,escalated'], 'reason' => ['required', 'string', 'max:2000']];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(app(AiReviewPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
