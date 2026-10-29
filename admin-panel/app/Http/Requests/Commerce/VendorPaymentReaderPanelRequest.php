<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Policies\Commerce\VendorPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Cards\CardAccess;
use App\Services\Platform\Tenancy\CurrentSchool;

class VendorPaymentReaderPanelRequest extends VendorCatalogueRequest
{
    protected function returnRoute(): string
    {
        return 'vendor-payment-readers.index';
    }

    public function authorize(): bool
    {
        app(VendorPolicy::class)->finance($this->user(), $this->routeIs('vendor-payment-readers.register'));
        $actor = app(CardAccess::class)->actor($this->user(), revocation: true);
        abort_unless(app(ProfilePolicy::class)->permission($actor, CardAccess::READERS), 403);
        if (! $this->isMethodSafe()) {
            abort_if($this->query->count() > 0, 422);
            abort_unless((int) $this->input('expected_school_id') === app(CurrentSchool::class)->id() && (int) $this->input('expected_account_id') === (int) $actor->id, 403);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
        }

        return $this->bindingRules() + ($this->routeIs('vendor-payment-readers.register')
            ? ['operation_id' => ['required', 'uuid'], 'vendor_id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:100'], 'days' => ['required', 'integer', 'between:1,30']]
            : []);
    }
}
