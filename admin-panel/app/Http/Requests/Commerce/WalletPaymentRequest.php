<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Policies\Money\WalletPolicy;
use App\Services\Commerce\VendorOperatorAccess;
use App\Services\Commerce\VendorPaymentReaders;
use App\Services\Commerce\WalletCounterPayments;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WalletPaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('qr_token')) && str_starts_with($this->input('qr_token'), 'navigateed:wallet-pay:')) {
            $this->merge(['qr_token' => substr($this->input('qr_token'), strlen('navigateed:wallet-pay:'))]);
        }
    }

    public function authorize(): bool
    {
        $write = ! $this->isMethodSafe() && ! $this->routeIs('api.v2.vendor.payments.resolve');
        WalletCounterPayments::available($write);
        abort_if($this->query->count() > 0 && (! $this->isMethodSafe() || ! $this->routeIs('api.v2.vendor.payments.catalogue', 'api.v2.commerce.wallet-payments.index')), 422);
        if ($this->routeIs('api.v2.vendor.payments.*')) {
            abort_unless(app(VendorOperatorAccess::class)->ownsSession($this), 403);
            app(VendorOperatorAccess::class)->counter($this->user(), (int) $this->route('vendor'), $write);
            if ($this->routeIs('api.v2.vendor.payments.card', 'api.v2.vendor.payments.portrait')) {
                app(VendorPaymentReaders::class)->authenticate($this->user(), (int) $this->route('vendor'), $this->header('X-Payment-Reader', ''), $write);
            }
        } else {
            app(WalletPolicy::class)->actor($this->user(), $write);
        }

        return true;
    }

    public function rules(): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];
        $qr = ['qr_token' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D']];

        return match (true) {
            $this->isMethodSafe() => ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']],
            $this->routeIs('api.v2.commerce.wallet-payments.issue') => $operation + ['quote' => ['required', 'string', 'max:32768'], 'confirmed' => ['required', 'accepted']],
            $this->routeIs('api.v2.vendor.payments.lookup') => $qr,
            $this->routeIs('api.v2.vendor.payments.card') => $operation + ['card_id' => ['required', 'uuid'], 'picc' => ['required', 'regex:/\A[a-fA-F0-9]{32}\z/D'], 'mac' => ['required', 'regex:/\A[a-fA-F0-9]{16}\z/D'], 'lines' => ['required', 'array', 'min:1', 'max:20'], 'lines.*.product_id' => ['required', 'integer', 'min:1', 'distinct'], 'lines.*.quantity' => ['required', 'integer', 'between:1,999']],
            $this->routeIs('api.v2.vendor.payments.pay') => $operation + ['intent_id' => ['required', 'uuid'], 'qr_token' => ['nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'portrait_revision' => ['nullable', 'integer', 'min:1'], 'portrait_confirmed' => ['nullable', 'boolean'], 'confirmed' => ['required', 'accepted']],
            default => [],
        };
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->routeIs('api.v2.commerce.wallet-payments.issue', 'api.v2.vendor.payments.card', 'api.v2.vendor.payments.pay') && (! is_string($this->input('operation_id')) || $this->header('Idempotency-Key') !== $this->input('operation_id'))) {
                $validator->errors()->add('operation_id', 'Use the same original UUID in Idempotency-Key and operation_id.');
            }
        });
    }
}
