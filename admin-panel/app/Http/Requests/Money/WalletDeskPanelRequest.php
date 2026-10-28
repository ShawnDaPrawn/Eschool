<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletDeskTopups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WalletDeskPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return WalletPolicy::FINANCE;
    }

    protected function returnRoute(): string
    {
        return 'wallet-desk.index';
    }

    public function authorize(): bool
    {
        $actor = $this->routeIs('wallet-desk.policy') ? app(WalletPolicy::class)->deskPolicy($this->user(), true) : app(WalletPolicy::class)->desk($this->user(), ! $this->isMethodSafe());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $actor->id && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_unless($this->query->count() === 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules();
        }
        $rules = match (true) {
            $this->routeIs('wallet-desk.policy') => WalletDeskTopups::policyRules(),$this->routeIs('wallet-desk.grant') => WalletDeskTopups::grantRules(),default => WalletDeskTopups::actionRules($this->routeIs('wallet-desk.cancel') ? 'cancelled' : 'released')
        };

        return $this->bindingRules() + $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Desk wallet top-ups');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route($this->returnRoute());
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
