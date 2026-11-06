<?php

declare(strict_types=1);

namespace App\Http\Requests\Reading;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Models\Students;
use App\Services\Reading\ReadingAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** The learner's reading library API (Action Plan 4.4): authorized as that learner only, with documented fields only. */
class ReadingApiRequest extends FormRequest
{
    private ?Students $learner = null;

    public function authorize(): bool
    {
        $this->learner = app(ReadingAccess::class)->learner($this->user(), ! $this->isMethodSafe());

        return true;
    }

    public function learner(): Students
    {
        return $this->learner ??= app(ReadingAccess::class)->learner($this->user(), ! $this->isMethodSafe());
    }

    public function rules(): array
    {
        abort_if($this->query->count() !== 0, 422, 'The reading library takes no query.');
        if ($this->isMethodSafe()) {
            return [];
        }
        $rules = match ($this->route()->getActionMethod()) {
            'progress' => ['page' => ['required', 'integer', 'min:1', 'max:100000'], 'seconds' => ['required', 'integer', 'min:0', 'max:86400']],
            'answer' => ['choice' => ['required', 'integer', 'min:0', 'max:4']],
            default => [],
        };
        abort_if(array_diff(array_keys($this->all()), array_keys($rules)) !== [], 422, 'Use only the documented reading fields.');

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the reading inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
