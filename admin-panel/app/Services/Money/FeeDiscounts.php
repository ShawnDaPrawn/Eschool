<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeDiscount;
use App\Models\Money\FeeItem;
use App\Models\Money\FeePlan;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

final class FeeDiscounts
{
    public const PERMISSION = 'fee-credits-manage';

    public const KINDS = ['sibling', 'staff', 'early_settlement', 'bursary', 'exemption'];

    public function save(User $actor, array $input, ?int $id = null): FeeDiscount
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = Validator::make($input, self::rules())->validate();
        foreach (['session_year_id', 'revision', 'learner_id', 'fee_plan_id', 'fee_item_id', 'amount_value', 'sibling_position', 'minimum_paid_bp'] as $key) {
            $data[$key] = isset($data[$key]) ? (int) $data[$key] : null;
        }
        $data['staff_roles'] = $data['staff_roles'] ?? null;
        $data['independent_approval'] = (bool) $data['independent_approval'];
        $data['id'] = $id;
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'fee_discount_save', $data, function () use ($actor, $data, $id): array {
            $year = SessionYear::where('default', 1)->lockForUpdate()->findOrFail($data['session_year_id']);
            app(FeePlans::class)->year($year);
            abort_unless($data['starts_on'] >= substr((string) $year->start_date, 0, 10) && $data['ends_on'] <= substr((string) $year->end_date, 0, 10), 422, 'Discount dates must fit the current school year.');
            if ($data['learner_id'] !== null) {
                abort_unless(Enrolment::where('session_year_id', $year->id)->where('learner_id', $data['learner_id'])->where('legacy_dates', false)->lockForUpdate()->exists(), 422, 'Choose a learner with confirmed enrolment in this school year.');
            }
            if ($data['fee_plan_id'] !== null) {
                FeePlan::where('session_year_id', $year->id)->where('currency', $data['currency'])->lockForUpdate()->findOrFail($data['fee_plan_id']);
            }
            if ($data['fee_item_id'] !== null) {
                FeeItem::where('currency', $data['currency'])->lockForUpdate()->findOrFail($data['fee_item_id']);
            }
            abort_unless(($data['source_kind'] === 'annual_tuition' && $data['fee_item_id'] === null) || ($data['source_kind'] === 'fee_item' && $data['fee_item_id'] !== null && $data['fee_plan_id'] === null), 422, 'Select an explicit tuition or item source.');
            abort_unless(! in_array($data['kind'], ['bursary', 'exemption'], true) || $data['learner_id'] !== null, 422, 'Individual bursaries and exemptions need a named learner.');
            abort_unless($data['amount_kind'] !== 'percent' || $data['amount_value'] <= 10000, 422, 'Percentage discounts use at most 10000 basis points.');
            abort_unless($data['kind'] !== 'exemption' || ($data['amount_kind'] === 'percent' && $data['amount_value'] === 10000), 422, 'An exemption is an explicit full reduction.');
            abort_unless(($data['kind'] === 'sibling') === ($data['sibling_position'] !== null), 422, 'Only sibling discounts use an explicit qualifying child position.');
            abort_unless(($data['kind'] === 'early_settlement' && $data['minimum_paid_bp'] !== null && isset($data['payment_deadline'])) || ($data['kind'] !== 'early_settlement' && $data['minimum_paid_bp'] === null && ! isset($data['payment_deadline'])), 422, 'Only early settlement uses an explicit paid threshold and deadline.');
            abort_unless(($data['kind'] === 'staff' && is_array($data['staff_roles']) && count($data['staff_roles']) > 0) || ($data['kind'] !== 'staff' && $data['staff_roles'] === null), 422, 'Choose explicit staff roles for staff discounts only.');
            if ($data['staff_roles'] !== null) {
                $roles = Role::where('school_id', app(CurrentSchool::class)->id())->whereIn('name', $data['staff_roles'])->whereNotIn('name', ['Student', 'Parent', 'Vendor'])->lockForUpdate()->pluck('name')->all();
                sort($roles);
                sort($data['staff_roles']);
                abort_unless($roles === $data['staff_roles'], 422, 'Choose distinct current school staff roles.');
            }
            $rule = $id === null ? new FeeDiscount : FeeDiscount::lockForUpdate()->findOrFail($id);
            abort_unless((int) ($rule->revision ?? 0) === $data['revision'], 409, 'The discount rule changed. Reopen it before saving.');
            abort_unless(! $rule->exists || (int) $rule->session_year_id === $data['session_year_id'], 422, 'Create a new rule for another school year.');
            $fields = array_intersect_key($data, array_flip(['name', 'kind', 'source_kind', 'session_year_id', 'learner_id', 'fee_plan_id', 'fee_item_id', 'currency', 'starts_on', 'ends_on', 'amount_kind', 'amount_value', 'sibling_position', 'minimum_paid_bp', 'payment_deadline', 'overlap', 'independent_approval', 'staff_roles']));
            $fields['payment_deadline'] = $fields['payment_deadline'] ?? null;
            $rule->fill($fields + ['state' => 'draft', 'revision' => $data['revision'] + 1, 'prepared_by' => $actor->id, 'approved_by' => null])->save();
            Audit::record('money.fee_discount_rule_saved', $rule, [], ['revision' => $rule->revision, 'state' => 'draft']);

            return ['id' => (int) $rule->id];
        });

        return FeeDiscount::findOrFail($receipt['id']);
    }

    public function transition(User $actor, int $id, array $input): FeeDiscount
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'action' => ['required', 'in:approve,archive'], 'confirmed' => ['required', 'accepted']])->validate() + ['rule_id' => $id];
        $data['revision'] = (int) $data['revision'];
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'fee_discount_transition', $data, function () use ($actor, $id, $data): array {
            $rule = FeeDiscount::lockForUpdate()->findOrFail($id);
            $this->currentYear((int) $rule->session_year_id);
            abort_unless($rule->revision === $data['revision'], 409, 'This discount rule changed.');
            if ($data['action'] === 'approve') {
                abort_unless($rule->state === 'draft', 409, 'Only a draft rule can be approved.');
                abort_unless(! $rule->independent_approval || (int) $actor->id !== (int) $rule->prepared_by, 403, 'The school requires another Finance approver.');
                $rule->update(['state' => 'active', 'revision' => $rule->revision + 1, 'approved_by' => $actor->id]);
            } else {
                abort_unless($rule->state !== 'archived', 409);
                $rule->update(['state' => 'archived', 'revision' => $rule->revision + 1]);
            }
            Audit::record('money.fee_discount_rule_'.$data['action'], $rule, [], ['revision' => $rule->revision]);

            return ['id' => $id];
        });

        return FeeDiscount::findOrFail($receipt['id']);
    }

    public function currentYear(int $year): SessionYear
    {
        $row = SessionYear::where('default', 1)->lockForUpdate()->findOrFail($year);
        app(FeePlans::class)->year($row);

        return $row;
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'session_year_id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:160'], 'kind' => ['required', 'in:'.implode(',', self::KINDS)], 'source_kind' => ['required', 'in:annual_tuition,fee_item'], 'learner_id' => ['nullable', 'integer', 'min:1'], 'fee_plan_id' => ['nullable', 'integer', 'min:1'], 'fee_item_id' => ['nullable', 'integer', 'min:1'], 'currency' => ['required', 'regex:/\A[A-Z]{3}\z/D'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'amount_kind' => ['required', 'in:percent,fixed'], 'amount_value' => ['required', 'integer', 'between:1,100000000000'], 'sibling_position' => ['nullable', 'integer', 'between:2,100'], 'minimum_paid_bp' => ['nullable', 'integer', 'between:1,10000'], 'payment_deadline' => ['nullable', 'date_format:Y-m-d'], 'overlap' => ['required', 'in:exclusive,remaining'], 'independent_approval' => ['required', 'boolean'], 'staff_roles' => ['nullable', 'array', 'min:1', 'max:20'], 'staff_roles.*' => ['required', 'string', 'max:80', 'distinct']];
    }
}
