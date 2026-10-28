<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletWithdrawals;
use Illuminate\Foundation\Http\FormRequest;

class WalletWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethodSafe()) {
            app(WalletPolicy::class)->actor($this->user());
        } else {
            app(WalletWithdrawals::class)->adult($this->user(), true);
            abort_if($this->query->count() > 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']];
        }

        return $this->routeIs('api.v2.wallets.withdrawals.cancel') ? WalletWithdrawals::reasonRules() : WalletWithdrawals::requestRules();
    }
}
