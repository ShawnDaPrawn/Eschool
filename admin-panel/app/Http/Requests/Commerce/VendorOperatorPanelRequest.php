<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Policies\Commerce\VendorPolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class VendorOperatorPanelRequest extends VendorCatalogueRequest
{
    protected function returnRoute(): string
    {
        return 'vendor-operators.index';
    }

    public function authorize(): bool
    {
        $revoke = $this->routeIs('vendor-operators.bind') && $this->input('status') === 'revoked';
        app(VendorPolicy::class)->finance($this->user(), ! $this->isMethodSafe() && ! $revoke);
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $this->user()->id && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($this->query->count() > 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
        }
        $rules = $this->bindingRules() + ['operation_id' => ['required', 'uuid'], 'confirmed_operator_identity' => ['required', 'accepted']];
        if ($this->routeIs('vendor-operators.provision')) {
            return $rules + ['first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email:rfc', 'max:191'], 'initial_password' => ['required', 'string', Password::defaults(), 'max:72']];
        }

        return $rules + ['vendor_id' => ['required', 'integer', 'min:1'], 'user_id' => ['required', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:0'], 'status' => ['required', Rule::in(['active', 'revoked'])], 'private_reason' => ['required', 'string', 'min:3', 'max:2000']];
    }
}
