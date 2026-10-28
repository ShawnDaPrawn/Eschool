<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeLifecyclePolicy;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeeLifecyclePolicies
{
    public const PERMISSION = 'fee-plans-manage';

    public function save(User $actor, array $input): FeeLifecyclePolicy
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = Validator::make($input, self::rules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['session_year_id'] = (int) $data['session_year_id'];
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'fee_lifecycle_policy', $data, function () use ($actor, $data): array {
            $year = SessionYear::where('default', 1)->lockForUpdate()->findOrFail($data['session_year_id']);
            app(FeePlans::class)->year($year);
            $policy = FeeLifecyclePolicy::where('session_year_id', $year->id)->lockForUpdate()->first() ?? new FeeLifecyclePolicy;
            abort_unless((int) ($policy->revision ?? 0) === $data['revision'], 409, 'This school fee policy changed. Reopen it before saving.');
            $policy->fill(['session_year_id' => $year->id, 'join_basis' => $data['join_basis'], 'leaver_basis' => $data['leaver_basis'], 'installments' => $data['installments'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('money.fee_lifecycle_policy_saved', $policy, [], ['revision' => $policy->revision, 'session_year_id' => $year->id]);

            return ['id' => (int) $policy->id];
        });

        return FeeLifecyclePolicy::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'session_year_id' => ['required', 'integer', 'min:1'], 'join_basis' => ['required', 'in:daily,calendar_month,full_year'], 'leaver_basis' => ['required', 'in:daily,calendar_month,no_credit'], 'installments' => ['required', 'in:preserve_dates,remaining_dates']];
    }
}
