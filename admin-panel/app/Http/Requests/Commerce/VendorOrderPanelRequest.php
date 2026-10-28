<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Http\Requests\Money\MoneyPanelRequest;
use App\Policies\Commerce\VendorPolicy;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VendorOrderPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return VendorPolicy::MANAGE;
    }

    protected function returnRoute(): string
    {
        return 'vendor-orders.index';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('order_number'))) {
            $this->merge(['order_number' => strtoupper(trim($this->input('order_number')))]);
        }
        if (is_string($this->input('qr_token')) && str_starts_with($this->input('qr_token'), 'navigateed:order:')) {
            $this->merge(['qr_token' => substr($this->input('qr_token'), strlen('navigateed:order:'))]);
        }
    }

    public function authorize(): bool
    {
        app(VendorPolicy::class)->finance($this->user(), ! $this->isMethodSafe());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $this->user()->id && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($this->query->count() > 0, 422);
            if ($this->routeIs('vendor-orders.cancel')) {
                app(WalletPolicy::class)->finance($this->user(), true);
            }
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['vendor_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
        }
        if ($this->routeIs('vendor-orders.cancel')) {
            return $this->bindingRules() + ['operation_id' => ['required', 'uuid'], 'private_reason' => ['required', 'string', 'min:3', 'max:2000'], 'confirmed' => ['required', 'accepted'], 'verified_uncollected' => ['required', 'accepted']];
        }

        return $this->bindingRules() + ['operation_id' => ['required', 'uuid'], 'order_number' => ['nullable', 'string', 'regex:/\AVO-\d{6,20}\z/D', 'required_without:qr_token'], 'qr_token' => ['nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/D', 'required_without:order_number'], 'confirmed' => ['required', 'accepted']];
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Vendor orders');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route($this->returnRoute());
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
