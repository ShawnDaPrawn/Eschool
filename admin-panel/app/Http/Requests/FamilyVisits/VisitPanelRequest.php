<?php

declare(strict_types=1);

namespace App\Http\Requests\FamilyVisits;

use App\Http\Controllers\Panel\FamilyVisits\VisitPanelController;
use App\Services\FamilyVisits\GatePasses;
use App\Services\FamilyVisits\Meetings;
use App\Services\FamilyVisits\VisitPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VisitPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(VisitPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'between:1,100000'], 'pass_page' => ['sometimes', 'integer', 'between:1,100000']];
        }
        $rules = match ($this->route('operation')) {
            'meeting-create' => Meetings::rules('create'),
            'meeting-cancel' => Meetings::rules('cancel') + ['source_id' => ['required', 'uuid']],
            'pass-approve', 'pass-cancel' => GatePasses::rules('approve') + ['source_id' => ['required', 'uuid']],
            'rules' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
                'max_window_minutes' => ['required', 'integer', 'between:15,240'], 'max_future_days' => ['required', 'integer', 'between:1,30'], 'scan_max_age_seconds' => ['required', 'integer', 'between:15,120']],
        };

        return $rules + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(VisitPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
