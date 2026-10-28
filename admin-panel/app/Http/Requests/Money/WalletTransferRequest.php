<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletTransfers;
use Illuminate\Foundation\Http\FormRequest;

class WalletTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(WalletPolicy::class)->actor($this->user(), $this->routeIs('api.v2.wallets.transfer'));
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422);

        return true;
    }

    public function rules(): array
    {
        if ($this->routeIs('api.v2.wallets.transfer')) {
            return WalletTransfers::rules();
        }

        return $this->isMethodSafe() ? ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:50']] : [];
    }
}
