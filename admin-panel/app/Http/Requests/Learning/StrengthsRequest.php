<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Learning\Strengths\StrengthsAccess;
use App\Services\Learning\Strengths\StrengthsMirror;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * The strengths mirror (Action Plan 4.8): the learner's own routes are
 * authorized as that learner only; the shared-with-me routes as a guardian or
 * teacher, whose share then decides what they see. Documented fields only.
 */
class StrengthsRequest extends FormRequest
{
    private const VIEWER = ['sharedWithMe', 'shared'];

    public function authorize(): bool
    {
        $access = app(StrengthsAccess::class);
        if (in_array($this->route()->getActionMethod(), self::VIEWER, true)) {
            $access->viewer($this->user());
        } else {
            $access->learner($this->user(), ! $this->isMethodSafe());
        }

        return true;
    }

    public function rules(): array
    {
        abort_if($this->query->count() !== 0, 422, 'The strengths mirror takes no query.');
        if ($this->route()->getActionMethod() !== 'share') {
            abort_if($this->all() !== [], 422, 'This takes no fields.');

            return [];
        }
        $rules = [
            'operation_id' => ['required', 'uuid'],
            'recipient_user_id' => ['required', 'integer', 'min:1'],
            'sections' => ['required', 'array', 'list', 'min:1', 'max:'.count(StrengthsMirror::SECTIONS)],
            'sections.*' => ['required', 'string', 'distinct', Rule::in(StrengthsMirror::SECTIONS)],
            'days' => ['sometimes', 'integer', 'min:1', 'max:'.(int) config('strengths_mirror.share_max_days', 30)],
        ];
        abort_if(array_diff(array_keys($this->all()), ['operation_id', 'recipient_user_id', 'sections', 'days']) !== [], 422, 'Use only the documented share fields.');

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the share.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
