<?php

declare(strict_types=1);

namespace App\Http\Requests\Recognition;

use App\Http\Controllers\Panel\Recognition\RecognitionPanelController;
use App\Services\Recognition\RecognitionPanelScope;
use App\Services\Recognition\RecognitionQueries;
use App\Services\Recognition\RecognitionRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RecognitionPanelRequest extends RecognitionRequest
{
    public function authorize(): bool
    {
        return app(RecognitionPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        $common = ['learner_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'session_year_id' => ['sometimes', 'integer', 'min:1'], 'before' => ['sometimes', 'integer', 'min:1'], 'after' => ['sometimes', 'integer', 'min:0'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'assembly' => ['sometimes', 'boolean']];
        if ($this->isMethodSafe()) {
            return $common;
        }
        $op = (string) $this->route('operation');
        if ($op === 'catalogue') {
            $op = RecognitionQueries::CATALOGUES[$this->route('kind')] ?? abort(404);
        }

        return RecognitionRules::for($op, $this->route('record') !== null) + $common + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(RecognitionPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
