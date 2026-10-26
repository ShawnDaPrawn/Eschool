<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\ReleasedMarks;
use App\Services\Learning\TutorAccess;
use App\Services\Platform\Files\SafeImages;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $access = app(TutorAccess::class);
        if ($this->route()->getActionMethod() === 'summary') {
            $fresh = $access->actor($this->user());
            abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 404);
            app(ReleasedMarks::class)->learner($fresh, (int) $this->route('learner'));
        } elseif ($this->route('session') !== null) {
            $access->session($this->user(), (string) $this->route('session'), ! $this->isMethodSafe());
        } else {
            $access->learner($this->user(), ! $this->isMethodSafe());
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            $allowed = in_array($this->route()->getActionMethod(), ['index', 'show'], true) ? ['page'] : [];
            abort_if(array_diff(array_keys($this->query->all()), $allowed) !== [], 422);

            return ['page' => ['sometimes', 'integer', 'between:1,1000']];
        }
        abort_if($this->query->count() !== 0, 422, 'Private Tutor writes accept body fields only.');
        $operation = ['operation_id' => ['required', 'uuid']];
        $rules = match ($this->route()->getActionMethod()) {
            'create' => $operation + ['group_id' => ['required', 'integer', 'min:1'], 'source_version_ids' => ['required', 'array', 'min:1', 'max:3'],
                'source_version_ids.*' => ['required', 'integer', 'min:1', 'distinct']],
            'send' => $operation + ['question' => ['required', 'string', 'max:1000'], 'working' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'photo_ids' => ['sometimes', 'array', 'max:3'], 'photo_ids.*' => ['required', 'uuid', 'distinct']],
            'upload' => $operation + SafeImages::rules() + ['approved' => ['required', 'accepted'], 'crop_x' => ['required', 'integer', 'min:0', 'max:8192'],
                'crop_y' => ['required', 'integer', 'min:0', 'max:8192'], 'crop_width' => ['required', 'integer', 'between:1,8192'], 'crop_height' => ['required', 'integer', 'between:1,8192']],
            default => $operation,
        };
        $allowed = array_filter(array_keys($rules), fn ($key) => ! str_contains($key, '.'));
        abort_if(array_diff(array_keys($this->all()), $allowed) !== [], 422, 'Use only the documented Tutor fields.');

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the Tutor inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
