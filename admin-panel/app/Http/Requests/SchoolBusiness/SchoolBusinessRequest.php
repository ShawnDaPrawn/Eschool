<?php

declare(strict_types=1);

namespace App\Http\Requests\SchoolBusiness;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\SchoolBusiness\Bursaries;
use App\Services\SchoolBusiness\BursaryDocuments;
use App\Services\SchoolBusiness\Giving;
use App\Services\SchoolBusiness\ReenrolmentContracts;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

/**
 * Shape checks for the school business API (Action Plan 5.10). Every
 * service rechecks school, role, module and owner under its own lock;
 * this request refuses unknown fields and query strings on writes.
 */
class SchoolBusinessRequest extends FormRequest
{
    public function action(): string
    {
        return Str::after((string) $this->route()->getName(), 'api.v2.');
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : array_replace($this->getInputSource()->all(), $this->allFiles());
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send private fields in the request body.');
        abort_if(array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422, 'Remove fields this request does not use.');
    }

    public function rules(): array
    {
        $top = fn (array $rules): array => array_filter($rules, fn (string $key) => ! str_contains($key, '.'), ARRAY_FILTER_USE_KEY);

        return match ($this->action()) {
            'reenrolment.publish' => ReenrolmentContracts::rules('publish'),
            'reenrolment.withdraw' => ReenrolmentContracts::rules('withdraw'),
            'reenrolment.remind' => ReenrolmentContracts::rules('remind'),
            'reenrolment.accept' => ReenrolmentContracts::rules('accept'),
            'reenrolment.outstanding' => ReenrolmentContracts::rules('outstanding'),
            'bursaries.create' => $top(Bursaries::rules('create')),
            'bursaries.update' => $top(Bursaries::rules('update')),
            'bursaries.submit' => Bursaries::rules('submit'),
            'bursaries.withdraw' => Bursaries::rules('withdraw'),
            'bursaries.upload' => BursaryDocuments::rules(),
            'bursaries.queue' => Bursaries::rules('queue'),
            'bursaries.note' => Bursaries::rules('note'),
            'bursaries.decide' => Bursaries::rules('decide'),
            'bursaries.discount' => Bursaries::rules('discount'),
            'giving.create' => Giving::rules('campaign'),
            'giving.close' => Giving::rules('close'),
            'giving.pledge' => Giving::rules('pledge'),
            'giving.donate' => Giving::rules('donation'),
            default => [],
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the form.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }
}
