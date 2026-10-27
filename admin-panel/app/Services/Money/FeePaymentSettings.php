<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeePaymentPolicy;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeePaymentSettings
{
    public function save(User $actor, array $input): FeePaymentPolicy
    {
        app(MoneyPolicy::class)->authorize($actor, FeePayments::PERMISSION);
        $data = Validator::make($input, self::rules())->validate();
        $receipt = app(BillingOperations::class)->once($actor, FeePayments::PERMISSION, 'payment_policy', $data, function () use ($actor, $data): array {
            $policy = FeePaymentPolicy::lockForUpdate()->first() ?? new FeePaymentPolicy;
            abort_unless((int) ($policy->revision ?? 0) === (int) $data['revision'], 409, 'The allocation policy changed. Review it again.');
            $before = $policy->exists ? $policy->only(['allocation_order', 'revision']) : [];
            $policy->fill(['allocation_order' => $data['allocation_order'], 'revision' => (int) ($policy->revision ?? 0) + 1, 'changed_by' => $actor->id])->save();
            Audit::record('money.payment_policy_changed', $policy, $before, $policy->only(['allocation_order', 'revision']));

            return ['id' => (int) $policy->id];
        });

        return FeePaymentPolicy::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'allocation_order' => ['required', 'in:oldest_first,tuition_first']];
    }
}
