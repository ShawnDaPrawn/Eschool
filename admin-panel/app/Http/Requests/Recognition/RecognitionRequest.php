<?php

declare(strict_types=1);

namespace App\Http\Requests\Recognition;

use App\Services\Recognition\RecognitionQueries;
use App\Services\Recognition\RecognitionRules;
use Illuminate\Foundation\Http\FormRequest;

class RecognitionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Submit recognition changes in the request body.');
        if (! $this->isMethodSafe() && $this->route('kind') !== null) {
            $operation = RecognitionQueries::CATALOGUES[$this->route('kind')] ?? abort(404);
            $this->merge(RecognitionRules::normalise($this->all(), $operation));
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['session_year_id' => ['sometimes', 'integer', 'min:1'], 'grade_level_id' => ['sometimes', 'integer', 'min:1'], 'before' => ['sometimes', 'integer', 'min:1'], 'after' => ['sometimes', 'integer', 'min:0'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'audience' => ['sometimes', 'in:staff,family']];
        }
        $method = $this->route()->getActionMethod();
        $op = match ($method) {
            'catalogueWrite' => RecognitionQueries::CATALOGUES[$this->route('kind')] ?? abort(404),'configure' => 'configure',default => $method
        };

        return RecognitionRules::for($op, $this->route('record') !== null);
    }
}
