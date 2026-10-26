<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Learning\TeachingDraftAccess;
use App\Services\Learning\TeachingDrafts;
use App\Services\Platform\Files\SafeImages;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TeachingDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $action = $this->route()->getActionMethod();
        $module = in_array($action, ['insightConfiguration', 'insightChoices', 'insights'], true) ? 'test_insights' : 'teaching_assistant';
        $access = app(TeachingDraftAccess::class);
        $access->actor($this->user(), $module, ! $this->isMethodSafe());
        if ($this->route('group') !== null) {
            $access->group($this->user(), (int) $this->route('group'), ! $this->isMethodSafe(), $module);
        }
        if ($this->route('draft') !== null) {
            $access->draft($this->user(), (string) $this->route('draft'), ! $this->isMethodSafe());
        }

        return true;
    }

    public function rules(): array
    {
        $action = $this->route()->getActionMethod();
        if ($this->isMethodSafe()) {
            $rules = match ($action) {
                'index' => ['group_id' => ['required', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,1000']],
                'learners' => ['page' => ['sometimes', 'integer', 'between:1,1000']],
                'pdf' => ['include_answers' => ['sometimes', 'in:0,1,true,false']],
                default => [],
            };
            abort_if(array_diff(array_keys($this->query->all()), array_keys($rules)) !== [], 422);

            return $rules;
        }
        abort_if($this->query->count() !== 0, 422, 'Private teaching writes accept body fields only.');
        $rules = match ($action) {
            'generate' => TeachingDrafts::generationRules(),
            'review' => TeachingDrafts::reviewRules(),
            'upload' => SafeImages::rules() + ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required', 'integer', 'min:1'],
                'approved' => ['required', 'accepted'], 'crop_x' => ['required', 'integer', 'between:0,8192'], 'crop_y' => ['required', 'integer', 'between:0,8192'],
                'crop_width' => ['required', 'integer', 'between:1,8192'], 'crop_height' => ['required', 'integer', 'between:1,8192']],
            default => ['operation_id' => ['required', 'uuid']],
        };
        $allowed = array_filter(array_keys($rules), fn ($key) => ! str_contains($key, '.'));
        abort_if(array_diff(array_keys($this->all()), $allowed) !== [], 422, 'Use only the documented teaching fields.');

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the teaching inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
