<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\FeePlanPolicy;
use App\Services\Money\FeePlanRules;
use App\Services\Money\FeePlans;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FeePlanPanelRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->isMethodSafe()) {
            $actor = app(FeePlanPolicy::class)->authorize($this->user());
            abort_unless((int) $this->input('expected_account_id') === (int) $actor->id && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_unless($this->query->count() === 0, 422);
        }
        if ($this->exists('rule_rows')) {
            $rows = $this->input('rule_rows');
            abort_unless(is_array($rows) && count($rows) <= 20, 422);
            $rules = [];
            foreach ($rows as $row) {
                abort_unless(is_array($row), 422);
                foreach (['field', 'values'] as $key) {
                    $row[$key] = $row[$key] ?? '';
                    abort_unless(is_string($row[$key]), 422);
                }
                if ($row['field'] === '') {
                    abort_unless(trim($row['values']) === '', 422, 'Choose a field for each rule.');

                    continue;
                }
                $values = preg_split('/\\R/u', trim($row['values']));
                abort_unless(is_array($values) && count($values) <= 30, 422);
                if ($row['field'] === 'phase') {
                    $rules[] = ['field' => 'phase', 'values' => $values];

                    continue;
                }
                abort_unless(preg_match('/\\Aprofile:([1-9][0-9]{0,17})\\z/D', $row['field'], $m) === 1, 422);
                $field = app(FeePlanRules::class)->fields()->findOrFail((int) $m[1]);
                if ($field->type === 'boolean') {
                    $values = array_map(function ($v) {
                        $lower = mb_strtolower($v);
                        if (in_array($lower, ['yes', 'true', mb_strtolower(__('Yes'))], true)) {
                            return true;
                        }if (in_array($lower, ['no', 'false', mb_strtolower(__('No'))], true)) {
                            return false;
                        }$this->refusal(__('Use Yes or No for a boolean profile rule.'));
                    }, $values);
                }
                $rules[] = ['field' => 'profile', 'definition_id' => (int) $field->id, 'values' => $values];
            }
            $this->merge(['applies_to' => $rules]);
        }
        if ($this->exists('option_rows')) {
            $rows = $this->input('option_rows');
            abort_unless(is_array($rows) && count($rows) <= 6, 422);
            $options = [];
            $default = null;
            foreach ($rows as $i => $row) {
                abort_unless(is_array($row), 422);
                foreach (['key', 'label', 'schedule'] as $key) {
                    $row[$key] = $row[$key] ?? '';
                    abort_unless(is_string($row[$key]), 422);
                }
                abort_unless(strlen($row['schedule']) <= 8192, 422);
                if ($row['key'] === '' && $row['label'] === '' && trim($row['schedule']) === '') {
                    continue;
                }
                $parts = [];
                if (trim($row['schedule']) !== '') {
                    $lines = preg_split('/\\R/u', trim($row['schedule']));
                    abort_unless(is_array($lines) && count($lines) <= 24, 422);
                    foreach ($lines as $line) {
                        if (preg_match('/\\A(\\d{2})-(\\d{2})[ \\t]+(\\d{1,3})(?:\\.(\\d{1,2}))?\\z/D', trim($line), $m) !== 1) {
                            $this->refusal(__('Use one MM-DD percentage per line, for example 01-15 25.00.'));
                        }
                        $parts[] = ['month' => (int) $m[1], 'day' => (int) $m[2], 'weight_bp' => (int) $m[3] * 100 + (int) str_pad($m[4] ?? '', 2, '0')];
                    }
                }
                $options[] = ['key' => $row['key'], 'label' => $row['label'], 'installments' => $parts];
                if ((string) $this->input('payment_default_index') === (string) $i) {
                    $default = $row['key'];
                }
            }
            abort_unless($default !== null, 422, 'Choose an available default payment option.');
            $this->merge(['payment_options' => $options, 'default_payment_option' => $default]);
        }
        if ($this->has('scope_choice')) {
            $choice = $this->input('scope_choice');
            abort_unless(is_string($choice) && preg_match('/\A(all:0|(?:grade|class):[1-9]\d{0,17})\z/D', $choice), 422);
            [$scope, $id] = explode(':', $choice);
            $this->merge(['scope' => $scope, 'target_id' => $scope === 'all' ? null : (int) $id]);
        }
    }

    private function refusal(string $message): never
    {
        $title = __('Fee plans');
        $draft = [];
        $returnUrl = route('fee-plans.index');
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }

    public function authorize(): bool
    {
        $actor = app(FeePlanPolicy::class)->authorize($this->user());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $actor->id
                && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_unless($this->query->count() === 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['year_id' => ['sometimes', 'integer', 'min:1'], 'date' => [$this->routeIs('fee-plans.preview') ? 'required' : 'sometimes', 'date_format:Y-m-d'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
        }

        return ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']]
            + ($this->route('operation') === 'copy' ? FeePlans::copyRules() : FeePlans::saveRules());
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Fee plans');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route('fee-plans.index');
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
