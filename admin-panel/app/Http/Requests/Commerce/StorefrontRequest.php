<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Policies\Commerce\VendorPolicy;
use App\Services\Commerce\VendorOrders;
use Illuminate\Foundation\Http\FormRequest;

class StorefrontRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(VendorPolicy::class)->actor($this->user(), ! $this->isMethodSafe());
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422);

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
        }

        return match (true) {
            $this->routeIs('api.v2.commerce.quote') => VendorOrders::cartRules(),
            $this->routeIs('api.v2.commerce.purchase') => ['operation_id' => ['required', 'uuid'], 'quote' => ['required', 'string', 'max:32768'], 'confirmed' => ['required', 'accepted']],
            default => ['operation_id' => ['required', 'uuid'], 'private_reason' => ['required', 'string', 'min:3', 'max:2000'], 'confirmed' => ['required', 'accepted']],
        };
    }
}
