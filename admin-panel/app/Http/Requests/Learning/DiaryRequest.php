<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Models\Students;
use App\Services\Learning\Diary\DiaryAccess;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** The learner's own diary: authorized as that learner only, with documented fields only. */
class DiaryRequest extends FormRequest
{
    private ?Students $learner = null;

    public function authorize(): bool
    {
        $this->learner = app(DiaryAccess::class)->learner($this->user(), ! $this->isMethodSafe());

        return true;
    }

    public function learner(): Students
    {
        return $this->learner ??= app(DiaryAccess::class)->learner($this->user(), ! $this->isMethodSafe());
    }

    public function rules(): array
    {
        $method = $this->route()->getActionMethod();
        if ($this->isMethodSafe()) {
            abort_if(array_diff(array_keys($this->query->all()), ['from', 'to']) !== [], 422, 'Use only from and to.');

            return [
                'from' => ['required', 'date_format:Y-m-d'],
                'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            ];
        }
        abort_if($this->query->count() !== 0, 422, 'Diary writes accept body fields only.');
        if ($method === 'destroy') {
            abort_if($this->all() !== [], 422, 'Deleting takes no fields.');

            return [];
        }
        $entry = [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'remind_at' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i'],
            'done' => ['sometimes', 'boolean'],
        ];
        $rules = match ($method) {
            'store' => ['operation_id' => ['required', 'uuid'], 'kind' => ['required', 'in:note,todo']] + $entry,
            'update' => ['revision' => ['required', 'integer', 'min:1']] + $entry,
            'state' => [
                'done' => ['required', 'boolean'],
                'remind_at' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i'],
                'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            ],
            default => [],
        };
        abort_if(array_diff(array_keys($this->all()), array_keys($rules)) !== [], 422, 'Use only the documented diary fields.');

        return $rules;
    }

    public function after(): array
    {
        return [function ($validator) {
            if (! $this->isMethodSafe() || $validator->errors()->isNotEmpty()) {
                return;
            }
            $from = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->query('from'));
            $to = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->query('to'));
            if ($from->diffInDays($to) > 61) {
                $validator->errors()->add('to', 'Choose at most 62 days.');
            }
        }];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the diary inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
