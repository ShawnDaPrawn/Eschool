<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeePaymentChoice;
use App\Models\Money\FeePlan;
use App\Models\Money\InvoiceLine;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeePaymentChoices
{
    public function save(User $actor, int $accountId, array $input): FeePaymentChoice
    {
        app(MoneyPolicy::class)->authorize($actor, FeeAccounts::PERMISSION);
        $data = Validator::make($input, self::rules())->validate() + ['account_id' => $accountId];
        foreach (['revision', 'learner_id', 'session_year_id', 'fee_plan_id'] as $key) {
            $data[$key] = (int) $data[$key];
        }
        $receipt = app(BillingOperations::class)->once($actor, FeeAccounts::PERMISSION, 'fee_payment_choice', $data, function () use ($actor, $accountId, $data): array {
            $account = FeeAccount::lockForUpdate()->findOrFail($accountId);
            abort_unless($account->active, 422, 'Choose an active family account.');
            FeeAccountLearner::where('fee_account_id', $accountId)->where('session_year_id', $data['session_year_id'])->where('learner_id', $data['learner_id'])->lockForUpdate()->firstOrFail();
            $plan = FeePlan::where('session_year_id', $data['session_year_id'])->where('state', 'active')->lockForUpdate()->findOrFail($data['fee_plan_id']);
            abort_unless($plan->currency === $account->currency, 422, 'Plan and family currencies must agree.');
            $options = app(FeePaymentSchedules::class)->normalize($plan->payment_options, SessionYear::findOrFail($plan->session_year_id));
            abort_unless(in_array($data['option_key'], array_column($options, 'key'), true), 422, 'Choose an available plan payment option.');
            abort_if(InvoiceLine::where('session_year_id', $data['session_year_id'])->where('learner_id', $data['learner_id'])->whereNull('fee_charge_id')->lockForUpdate()->first() !== null, 422, 'Posted tuition keeps its reviewed schedule. Credit or adjust its actual lines.');
            $choice = FeePaymentChoice::where('session_year_id', $data['session_year_id'])->where('learner_id', $data['learner_id'])->lockForUpdate()->first() ?? new FeePaymentChoice;
            abort_unless(($choice->revision ?? 0) === $data['revision'], 409, 'The payment choice changed. Review its current selection.');
            $choice->fill(['session_year_id' => $data['session_year_id'], 'learner_id' => $data['learner_id'], 'fee_plan_id' => $plan->id, 'option_key' => $data['option_key'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('money.fee_payment_option_selected', $choice, [], ['learner_id' => $data['learner_id'], 'fee_plan_id' => $plan->id, 'option_key' => $data['option_key'], 'revision' => $choice->revision]);

            return ['id' => (int) $choice->id];
        });

        return FeePaymentChoice::findOrFail($receipt['id']);
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'session_year_id' => ['required', 'integer', 'min:1'], 'learner_id' => ['required', 'integer', 'min:1'], 'fee_plan_id' => ['required', 'integer', 'min:1'], 'option_key' => ['required', 'string', 'regex:/\A[a-z][a-z0-9_-]{0,31}\z/D']];
    }
}
