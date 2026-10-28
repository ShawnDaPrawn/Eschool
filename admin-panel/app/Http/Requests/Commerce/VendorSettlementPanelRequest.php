<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Services\Commerce\VendorSettlements;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VendorSettlementPanelRequest extends VendorCatalogueRequest
{
    protected function returnRoute(): string
    {
        return 'vendor-settlements.index';
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['vendor_id' => ['sometimes', 'integer', 'min:1'], 'period_from' => ['sometimes', 'date_format:Y-m-d'], 'period_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:period_from'], 'page' => ['sometimes', 'integer', 'between:1,1000000']];
        }

        return $this->bindingRules() + match (true) {
            $this->routeIs('vendor-settlements.prepare') => VendorSettlements::prepareRules(),$this->routeIs('vendor-settlements.approve') => VendorSettlements::confirmRules(),default => VendorSettlements::finishRules()
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Vendor settlements');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route($this->returnRoute());
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422));
    }
}
