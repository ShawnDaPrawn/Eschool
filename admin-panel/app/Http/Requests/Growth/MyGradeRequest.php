<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Growth\MyGrade;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class MyGradeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $allowed = $this->route('grade') === null ? [] : ['on_date', 'page', 'discipline_page'];
        abort_if(array_diff(array_keys($this->query->all()), $allowed) !== [], 422, 'Use the current grade context and documented date/page fields.');
    }

    public function authorize(): bool
    {
        $access = app(MyGrade::class)->access($this->user());
        if ($this->route('grade') !== null) {
            abort_unless(in_array((int) $this->route('grade'), array_column($access['grades'], 'id'), true), 403);
        }

        return true;
    }

    public function rules(): array
    {
        return ['on_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'], 'page' => ['sometimes', 'integer', 'between:1,1000'],
            'discipline_page' => ['sometimes', 'integer', 'between:1,1000']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the selected working date and page.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
