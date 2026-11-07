<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Policies\Growth\MasteryPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\ReleasedMarks;
use App\Services\Learning\LearningPathService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Fresh learning path authority precedes validation and idempotent replay (Action Plan 3.9). */
class LearningPathRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Private learning path writes accept body fields only.');
    }

    public function authorize(): bool
    {
        $method = $this->route()->getActionMethod();
        if ($method === 'group') {
            app(MasteryPolicy::class)->staff($this->user(), (int) $this->route('group'));
        } elseif ($method === 'family') {
            $fresh = app(ProfilePolicy::class)->actor($this->user());
            abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 404);
            app(ReleasedMarks::class)->learner($fresh, (int) $this->route('learner'));
        } else {
            app(LearningPathService::class)->access($this->user(), (int) $this->route('learner'), (int) $this->route('group'), ! $this->isMethodSafe());
        }

        return true;
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? ['page' => ['sometimes', 'integer', 'between:1,1000']] : [];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the learning path inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
