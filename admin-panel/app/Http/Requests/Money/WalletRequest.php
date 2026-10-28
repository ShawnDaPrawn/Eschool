<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\WalletPolicy;
use App\Services\Money\Wallets;
use Illuminate\Foundation\Http\FormRequest;

class WalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(WalletPolicy::class)->actor($this->user(), ! $this->isMethodSafe());

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']];
        }

        return $this->routeIs('api.v2.wallets.open') ? Wallets::openRules() : Wallets::limitRules();
    }
}
