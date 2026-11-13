<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

use App\Services\Community\EventReadiness;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EventReadinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless($this->user(), 401);
        app(EventReadiness::class)->authorize($this->user(), true);
        abort_unless((string) $this->input('expected_account_id') === (string) $this->user()->id
            && (string) $this->input('expected_school_id') === (string) app(CurrentSchool::class)->id(), 403);

        return true;
    }

    public function rules(): array
    {
        return EventReadiness::rules() + ['expected_account_id' => 'required|integer|min:1', 'expected_school_id' => 'required|integer|min:1'];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Transport notes and private association identifiers never become shared old input.
        throw new HttpResponseException(response()->json(['message' => __('Review the highlighted fields.'), 'errors' => $validator->errors()], 422));
    }
}
