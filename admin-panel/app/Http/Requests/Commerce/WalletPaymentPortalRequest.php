<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Services\Commerce\VendorOperatorAccess;
use App\Services\Commerce\WalletCounterPayments;

class WalletPaymentPortalRequest extends VendorPortalRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('qr_token')) && str_starts_with($this->input('qr_token'), 'navigateed:wallet-pay:')) {
            $this->merge(['qr_token' => substr($this->input('qr_token'), 22)]);
        }
    }

    public function authorize(): bool
    {
        parent::authorize();
        $write = ! $this->isMethodSafe() && ! $this->routeIs('vendor.payments.resolve');
        WalletCounterPayments::available($write);
        app(VendorOperatorAccess::class)->counter($this->user(), (int) $this->route('vendor'), $write);

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
        }
        $bind = ['expected_school_id' => ['required', 'integer', 'min:1'], 'expected_account_id' => ['required', 'integer', 'min:1']];
        if ($this->routeIs('vendor.payments.lookup')) {
            return $bind + ['qr_token' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D']];
        }
        $operation = ['operation_id' => ['required', 'uuid']];
        if ($this->routeIs('vendor.payments.resolve')) {
            return $bind + $operation;
        }
        $reader = ['reader_credential' => ['nullable', 'string', 'max:159']];
        if ($this->routeIs('vendor.payments.card')) {
            return $bind + $operation + ['reader_credential' => ['required', 'string', 'max:159'], 'tap_uri' => ['required', 'string', 'max:1024'], 'lines' => ['required', 'array', 'min:1', 'max:30'], 'lines.*.product_id' => ['required', 'integer', 'min:1', 'distinct'], 'lines.*.quantity' => ['required', 'integer', 'between:0,999']];
        }

        return $bind + $operation + $reader + ['intent_id' => ['required', 'uuid'], 'qr_token' => ['nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'portrait_revision' => ['nullable', 'integer', 'min:1'], 'portrait_confirmed' => ['nullable', 'boolean'], 'confirmed' => ['required', 'accepted']];
    }
}
