<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\WalletPolicy;
use App\Services\Money\FeePayments;
use App\Services\Money\WalletFundingService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WalletFundingPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return WalletPolicy::FINANCE;
    }

    protected function returnRoute(): string
    {
        return 'wallet-funding.index';
    }

    public function authorize(): bool
    {
        $actor = app(WalletPolicy::class)->finance($this->user(), ! $this->isMethodSafe());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $actor->id && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_unless($this->query->count() === 0, 422);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $reference = $this->input('source_reference');
        if (is_string($reference)) {
            $this->merge(['source_reference' => FeePayments::reference($reference)]);
        }
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? $this->readRules() : $this->bindingRules() + ($this->routeIs('wallet-funding.policy') ? WalletFundingService::policyRules() : WalletFundingService::receiptRules());
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('School wallet funding');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route($this->returnRoute());
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
