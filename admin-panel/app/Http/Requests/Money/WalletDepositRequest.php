<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\WalletPolicy;
use App\Services\Money\FeePayments;
use App\Services\Money\WalletDeposits;
use Illuminate\Foundation\Http\FormRequest;

class WalletDepositRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('source_reference'))) {
            $this->merge(['source_reference' => FeePayments::reference($this->input('source_reference'))]);
        }
    }

    public function authorize(): bool
    {
        if ($this->isMethodSafe()) {
            app(WalletPolicy::class)->actor($this->user());
        } else {
            app(WalletDeposits::class)->parent($this->user(), true);
            abort_if($this->query->count() > 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']];
        }

        return $this->routeIs('api.v2.wallets.deposits.cancel') ? WalletDeposits::finishRules('cancelled') : WalletDeposits::claimRules();
    }
}
