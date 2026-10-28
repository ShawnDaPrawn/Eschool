<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Http\Requests\Money\MoneyPanelRequest;
use App\Policies\Commerce\VendorPolicy;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VendorCatalogueRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return VendorPolicy::MANAGE;
    }

    protected function returnRoute(): string
    {
        return 'vendor-catalogues.index';
    }

    protected function prepareForValidation(): void
    {
        foreach (['source_reference', 'sku'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => strtoupper(preg_replace('/ +/', ' ', trim($this->input($key))) ?? '')]);
            }
        }
    }

    public function authorize(): bool
    {
        $actor = app(VendorPolicy::class)->finance($this->user(), ! $this->isMethodSafe());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $actor->id && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($this->query->count() > 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['products_page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'stock_page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'vendor_id' => ['sometimes', 'integer', 'min:1'], 'product_id' => ['sometimes', 'integer', 'min:1'], 'category_id' => ['sometimes', 'integer', 'min:1']];
        }

        return $this->bindingRules() + VendorCatalogues::rules((string) $this->route('kind'));
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Vendors and stock');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route($this->returnRoute());
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
