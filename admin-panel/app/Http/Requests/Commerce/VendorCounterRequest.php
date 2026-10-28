<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Services\Commerce\VendorCounterOrders;
use App\Services\Commerce\VendorOperatorAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorCounterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('order_number'))) {
            $this->merge(['order_number' => strtoupper(trim($this->input('order_number')))]);
        }
        if (is_string($this->input('qr_token')) && str_starts_with($this->input('qr_token'), 'navigateed:order:')) {
            $this->merge(['qr_token' => substr($this->input('qr_token'), 17)]);
        }
    }

    public function authorize(): bool
    {
        abort_unless(app(VendorOperatorAccess::class)->ownsSession($this), 403);
        app(VendorOperatorAccess::class)->counter($this->user(), (int) $this->route('vendor'), $this->routeIs('api.v2.vendor.collect'));
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422);

        return true;
    }

    public function rules(): array
    {
        return match (true) {
            $this->isMethodSafe() => ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'status' => ['sometimes', Rule::in(['ready', 'collected', 'cancelled'])]],
            $this->routeIs('api.v2.vendor.resolve') => [],
            $this->routeIs('api.v2.vendor.lookup') => VendorCounterOrders::lookupRules(),
            default => VendorCounterOrders::lookupRules() + ['operation_id' => ['required', 'uuid'], 'confirmed' => ['required', 'accepted']],
        };
    }
}
