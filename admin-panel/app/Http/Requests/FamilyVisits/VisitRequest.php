<?php

declare(strict_types=1);

namespace App\Http\Requests\FamilyVisits;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\FamilyVisits\GatePasses;
use App\Services\FamilyVisits\Meetings;
use App\Services\FamilyVisits\VisitAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class VisitRequest extends FormRequest
{
    public function action(): string
    {
        return Str::after((string) $this->route()->getName(), 'api.v2.family-visits.');
    }

    public function authorize(): bool
    {
        if (! str_starts_with($this->action(), 'device.')) {
            app(VisitAccess::class)->actor($this->user(), revocation: true);
        }

        return true;
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : array_replace($this->getInputSource()->all(), $this->allFiles());
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send private visit fields in the request body.');
        abort_if(array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422);
    }

    public function rules(): array
    {
        return match ($this->action()) {
            'configuration', 'device.lookup' => [],
            'meetings.list', 'passes.list' => ['learner_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,100000']],
            'meetings.create', 'meetings.book', 'meetings.unbook', 'meetings.cancel' => Meetings::rules(Str::after($this->action(), 'meetings.')),
            'passes.request', 'passes.approve', 'passes.cancel' => GatePasses::rules(Str::after($this->action(), 'passes.')),
            'device.collect' => GatePasses::rules('collect'),
            'rules.save' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
                'max_window_minutes' => ['required', 'integer', 'between:15,240'], 'max_future_days' => ['required', 'integer', 'between:1,30'], 'scan_max_age_seconds' => ['required', 'integer', 'between:15,120']],
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the meeting or collection request.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }
}
