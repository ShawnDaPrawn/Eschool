<?php

declare(strict_types=1);

namespace App\Http\Requests\Emergency;

use App\Http\Controllers\Panel\Emergency\EmergencyPanelController;
use App\Services\Emergency\EmergencyAccess;
use App\Services\Emergency\EmergencyAlerts;
use App\Services\Emergency\EmergencyDrills;
use App\Services\Emergency\EmergencyReunification;
use App\Services\Emergency\EmergencyVisitors;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** The emergency panel page. A write names the account and school it was opened for. */
class EmergencyPanelRequest extends FormRequest
{
    public const OPERATIONS = ['alert-prepare', 'alert-confirm', 'alert-all-clear', 'alert-cancel', 'roll-call-mark', 'release',
        'visitor-sign-in', 'visitor-sign-out', 'drill-plan', 'drill-record', 'drill-cancel', 'retention'];

    public function authorize(): bool
    {
        $fresh = app(EmergencyAccess::class)->staff($this->user(), ! $this->isMethodSafe());
        if (! $this->isMethodSafe()) {
            $account = $this->input('expected_account_id');
            $school = $this->input('expected_school_id');
            abort_unless((is_string($account) || is_int($account)) && (is_string($school) || is_int($school))
                && (string) $account === (string) $fresh->id && (string) $school === (string) app(CurrentSchool::class)->id(), 403);
            abort_if($this->query->count() > 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return [];
        }
        $source = ['source_id' => ['required', 'uuid']];
        $rules = match ($this->route('operation')) {
            'alert-prepare' => EmergencyAlerts::rules('prepare'),
            'alert-confirm' => EmergencyAlerts::rules('confirm') + $source,
            'alert-all-clear' => EmergencyAlerts::rules('all_clear') + $source,
            'alert-cancel' => EmergencyAlerts::rules('cancel') + $source,
            'roll-call-mark' => EmergencyReunification::rules('mark') + $source,
            'release' => EmergencyReunification::rules('release') + $source,
            'visitor-sign-in' => EmergencyVisitors::rules('sign_in'),
            'visitor-sign-out' => EmergencyVisitors::rules('sign_out') + $source,
            'drill-plan' => EmergencyDrills::rules('plan'),
            'drill-record' => EmergencyDrills::rules('record') + $source,
            'drill-cancel' => EmergencyDrills::rules('cancel') + $source,
            'retention' => EmergencyVisitors::rules('retention'),
        };

        return $rules + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
    }

    /** The service input: the form's fields without the page's own. */
    public function serviceInput(): array
    {
        return array_diff_key($this->validated(), ['expected_account_id' => true, 'expected_school_id' => true, 'source_id' => true]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(EmergencyPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
