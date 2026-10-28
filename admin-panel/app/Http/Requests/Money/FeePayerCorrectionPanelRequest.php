<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeePayerCorrections;

class FeePayerCorrectionPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeePayerCorrections::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-payer-corrections.index';
    }

    public function authorize(): bool
    {
        parent::authorize();
        app(FeePayerCorrections::class)->authorize($this->user());

        return true;
    }

    public function rules(): array
    {
        return $this->isMethodSafe()
            ? $this->readRules() + ['account_id' => ['sometimes', 'integer', 'min:1'], 'invoice_line_id' => ['sometimes', 'integer', 'min:1'], 'holder_user_id' => ['required_with:invoice_line_id', 'integer', 'min:1']]
            : $this->bindingRules() + FeePayerCorrections::rules($this->routeIs('fee-payer-corrections.post'));
    }
}
