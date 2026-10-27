<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\FeePlanPolicy;
use App\Services\Money\FeePlans;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FeePlanPanelRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('scope_choice')) {
            $choice = $this->input('scope_choice');
            abort_unless(is_string($choice) && preg_match('/\A(all:0|(?:grade|class):[1-9]\d{0,17})\z/D', $choice), 422);
            [$scope, $id] = explode(':', $choice);
            $this->merge(['scope' => $scope, 'target_id' => $scope === 'all' ? null : (int) $id]);
        }
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
