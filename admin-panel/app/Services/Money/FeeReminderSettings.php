<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeReminderPolicy;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeeReminderSettings
{
    public function save(User $actor, array $input): FeeReminderPolicy
    {
        app(MoneyPolicy::class)->authorize($actor, FeePayments::PERMISSION);
        $data = Validator::make($input, self::rules())->validate();
        foreach (['revision', 'min_days_overdue', 'cadence_days', 'local_hour'] as $key) {
            $data[$key] = (int) $data[$key];
        }
        $data['enabled'] = (bool) $data['enabled'];
        $receipt = app(BillingOperations::class)->once($actor, FeePayments::PERMISSION, 'fee_reminder_policy', $data, function () use ($actor, $data): array {
            $policy = FeeReminderPolicy::lockForUpdate()->first() ?? new FeeReminderPolicy;
            abort_unless((int) ($policy->revision ?? 0) === $data['revision'], 409, 'The fee reminder schedule changed. Review its current settings.');
            $policy->fill(['enabled' => $data['enabled'], 'min_days_overdue' => $data['min_days_overdue'], 'cadence_days' => $data['cadence_days'], 'local_hour' => $data['local_hour'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('money.fee_reminder_policy_saved', $policy, [], $policy->only(['enabled', 'min_days_overdue', 'cadence_days', 'local_hour', 'revision']));

            return ['id' => (int) $policy->id];
        });

        return FeeReminderPolicy::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'enabled' => ['required', 'boolean'],
            'min_days_overdue' => ['required', 'integer', 'min:1', 'max:365'], 'cadence_days' => ['required', 'integer', 'min:1', 'max:365'], 'local_hour' => ['required', 'integer', 'min:0', 'max:23']];
    }
}
