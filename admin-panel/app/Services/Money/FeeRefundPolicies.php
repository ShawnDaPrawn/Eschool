<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeRefundPolicy;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeeRefundPolicies
{
    public const PERMISSION = 'fee-payments-manage';

    public function save(User $actor, array $input): FeeRefundPolicy
    {
        $data = Validator::make($input, self::rules())->validate();
        foreach (['cash_allowed', 'eft_allowed', 'independent_approval'] as $key) {
            $data[$key] = (bool) $data[$key];
        }$data['revision'] = (int) $data['revision'];
        abort_unless($data['cash_allowed'] || $data['eft_allowed'], 422, 'Choose at least one permitted manual refund method.');
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'fee_refund_policy', $data, function () use ($actor, $data): array {
            $policy = FeeRefundPolicy::lockForUpdate()->first() ?? new FeeRefundPolicy;
            abort_unless((int) ($policy->revision ?? 0) === $data['revision'], 409, 'The refund policy changed. Review it again.');
            $policy->fill(['cash_allowed' => $data['cash_allowed'], 'eft_allowed' => $data['eft_allowed'], 'independent_approval' => $data['independent_approval'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('money.fee_refund_policy_saved', $policy, [], ['revision' => $policy->revision]);

            return ['id' => (int) $policy->id];
        });

        return FeeRefundPolicy::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'cash_allowed' => ['required', 'boolean'], 'eft_allowed' => ['required', 'boolean'], 'independent_approval' => ['required', 'boolean']];
    }
}
