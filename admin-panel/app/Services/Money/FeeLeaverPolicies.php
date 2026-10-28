<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeLeaverPolicy;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeeLeaverPolicies
{
    public const PERMISSION = 'fee-credits-manage';

    public function save(User $actor, array $input): FeeLeaverPolicy
    {
        $data = Validator::make($input, self::rules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['independent_approval'] = (bool) $data['independent_approval'];
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'fee_leaver_policy', $data, function () use ($actor, $data): array {
            $policy = FeeLeaverPolicy::lockForUpdate()->first() ?? new FeeLeaverPolicy;
            abort_unless((int) ($policy->revision ?? 0) === $data['revision'], 409, 'The school leaver credit rule changed. Review it again.');
            $policy->fill(['prior_policyless_basis' => $data['prior_policyless_basis'], 'prior_reductions' => $data['prior_reductions'], 'independent_approval' => $data['independent_approval'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('money.fee_leaver_policy_saved', $policy, [], ['revision' => $policy->revision]);

            return ['id' => (int) $policy->id];
        });

        return FeeLeaverPolicy::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'prior_policyless_basis' => ['required', 'in:daily,calendar_month,no_credit'], 'prior_reductions' => ['required', 'in:retain_full,proportion_reductions'], 'independent_approval' => ['required', 'boolean']];
    }
}
