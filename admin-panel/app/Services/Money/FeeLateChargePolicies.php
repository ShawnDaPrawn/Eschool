<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeLateChargePolicy;
use App\Models\Money\FeeLatePolicyRevision;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use Illuminate\Support\Facades\Validator;

final class FeeLateChargePolicies
{
    public const PERMISSION = 'fee-plans-manage';

    public function save(User $actor, array $input): FeeLateChargePolicy
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $input['private_reason'] = is_string($input['private_reason'] ?? null) ? trim($input['private_reason']) : $input['private_reason'] ?? null;
        $data = Validator::make($input, self::rules())->validate();
        foreach (['session_year_id', 'revision', 'grace_days', 'due_days'] as $key) {
            $data[$key] = isset($data[$key]) ? (int) $data[$key] : null;
        }
        $data['enabled'] = (bool) $data['enabled'];
        $data['flat_cents'] = isset($data['flat_amount']) ? Money::fromDecimalString($data['flat_amount'])->cents : null;
        $data['cap_cents'] = isset($data['maximum_gross']) ? Money::fromDecimalString($data['maximum_gross'])->cents : null;
        $data['rate_bp'] = isset($data['rate_percent']) ? FeeTaxAmounts::rate($data['rate_percent']) : null;
        $data['vat_rate_bp'] = isset($data['vat_rate']) ? FeeTaxAmounts::rate($data['vat_rate']) : null;
        foreach (['flat_cents', 'cap_cents'] as $key) {
            abort_if($data[$key] !== null && ($data[$key] < 1 || $data[$key] > FeePlans::MAX_CENTS), 422, 'Choose positive supported gross cents and cap.');
        }
        if ($data['enabled']) {
            abort_unless(($data['basis'] === 'flat' && $data['flat_cents'] !== null && $data['rate_bp'] === null) || ($data['basis'] === 'rate' && $data['rate_bp'] > 0 && $data['flat_cents'] === null), 422, 'Choose either an explicit gross flat amount or a positive rate.');
            try {
                FeeTaxAmounts::price($data['cap_cents'], $data['tax_treatment'], $data['vat_rate_bp']);
            } catch (\InvalidArgumentException $error) {
                abort(422, $error->getMessage());
            }
        }
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'late_charge_policy', $data, function () use ($actor, $data): array {
            app(FeePlans::class)->year(SessionYear::lockForUpdate()->findOrFail($data['session_year_id']));
            $policy = FeeLateChargePolicy::where('session_year_id', $data['session_year_id'])->where('currency', $data['currency'])->lockForUpdate()->first() ?? new FeeLateChargePolicy;
            abort_unless(($policy->revision ?? 0) === $data['revision'], 409, 'The late-charge rule changed. Review its current revision.');
            $policy->fill(['session_year_id' => $data['session_year_id'], 'currency' => $data['currency'], 'enabled' => $data['enabled'], 'scope' => $data['scope'] ?? null, 'basis' => $data['basis'] ?? null, 'flat_cents' => $data['flat_cents'], 'rate_bp' => $data['rate_bp'], 'cap_cents' => $data['cap_cents'], 'grace_days' => $data['grace_days'], 'due_days' => $data['due_days'], 'tax_treatment' => $data['tax_treatment'] ?? null, 'vat_rate_bp' => $data['vat_rate_bp'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            $policy->refresh();
            $snapshot = $policy->attributesToArray();
            $snapshot['enabled'] = (int) $policy->enabled;
            $snapshot['school_id'] = $policy->getAttribute('school_id');
            FeeLatePolicyRevision::create(['policy_id' => $policy->id, 'revision' => $policy->revision, 'actor_id' => $actor->id, 'snapshot' => $snapshot, 'private_reason' => $data['private_reason'], 'created_at' => now('UTC')]);
            Audit::record('money.late_charge_policy_saved', $policy, [], ['revision' => $policy->revision, 'enabled' => $policy->enabled]);

            return ['id' => (int) $policy->id];
        });

        return FeeLateChargePolicy::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        $required = ['nullable', 'required_if:enabled,1'];

        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'session_year_id' => ['required', 'integer', 'min:1'], 'currency' => ['required', 'string', 'regex:/\A[A-Z]{3}\z/D'], 'enabled' => ['required', 'boolean'], 'scope' => [...$required, 'in:annual_only,all_original_charges'], 'basis' => [...$required, 'in:flat,rate'], 'flat_amount' => ['nullable', 'string', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/D'], 'rate_percent' => ['nullable', 'string', 'regex:/\A(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)\z/D'], 'maximum_gross' => [...$required, 'string', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/D'], 'grace_days' => [...$required, 'integer', 'min:0', 'max:3650'], 'due_days' => [...$required, 'integer', 'min:0', 'max:3650'], 'tax_treatment' => [...$required, 'in:no_vat,vat_exempt,vat_zero,vat_inclusive'], 'vat_rate' => [...$required, 'string', 'regex:/\A(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)\z/D'], 'private_reason' => ['required', 'string', 'min:10', 'max:2000']];
    }
}
