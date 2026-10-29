<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Services\Commerce\VendorCounterOrders;
use App\Services\Commerce\VendorOperatorAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class VendorPortalRequest extends FormRequest
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
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422);
        if ($this->routeIs('vendor.sign-in')) {
            return true;
        }
        abort_unless(app(VendorOperatorAccess::class)->ownsSession($this), 403);
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $this->user()->id, 403);
            if (! $this->routeIs('vendor.sign-out')) {
                abort_unless((int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            }
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->routeIs('vendor.sign-in')) {
            return (new VendorSignInRequest)->rules();
        }
        if ($this->isMethodSafe()) {
            return ['status' => ['sometimes', Rule::in(['ready', 'collected', 'cancelled'])], 'vendor_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
        }
        $bind = ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
        if ($this->routeIs('vendor.sign-out')) {
            return $bind;
        }
        if ($this->routeIs('vendor.lookup')) {
            return $bind + VendorCounterOrders::lookupRules();
        }
        if ($this->routeIs('vendor.resolve')) {
            return $bind;
        }

        return $bind + VendorCounterOrders::lookupRules() + ['operation_id' => ['required', 'uuid'], 'confirmed' => ['required', 'accepted']];
    }

    protected function failedValidation(Validator $validator): void
    {
        $messages = $validator->errors()->all();
        throw new HttpResponseException(response()->view('vendor.error', compact('messages'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
