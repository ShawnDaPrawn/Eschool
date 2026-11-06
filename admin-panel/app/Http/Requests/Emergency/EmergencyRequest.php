<?php

declare(strict_types=1);

namespace App\Http\Requests\Emergency;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Emergency\EmergencyAlerts;
use App\Services\Emergency\EmergencyReunification;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

/** The emergency API (v2). The services check who may act; this only shapes the input. */
class EmergencyRequest extends FormRequest
{
    public function action(): string
    {
        return Str::after((string) $this->route()->getName(), 'api.v2.emergency.');
    }

    public function authorize(): bool
    {
        return true;
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send emergency fields in the request body.');
        abort_if(array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422, 'Send only the emergency fields this call takes.');
    }

    public function rules(): array
    {
        return match ($this->action()) {
            'status' => EmergencyReunification::rules('status'),
            'alerts.prepare' => EmergencyAlerts::rules('prepare'),
            'alerts.confirm' => EmergencyAlerts::rules('confirm'),
            'alerts.all-clear' => EmergencyAlerts::rules('all_clear'),
            'roll-call.show' => EmergencyReunification::rules('roll_call'),
            'roll-call.mark' => EmergencyReunification::rules('mark'),
            'releases.create' => EmergencyReunification::rules('release'),
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the emergency request.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }
}
