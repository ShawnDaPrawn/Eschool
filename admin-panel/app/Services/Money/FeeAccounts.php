<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeePayment;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class FeeAccounts
{
    public const PERMISSION = 'fee-accounts-manage';

    public function save(User $actor, array $input, ?int $id = null): FeeAccount
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = Validator::make($input, self::rules($id !== null))->validate();
        $data['name'] = trim($data['name']);
        $data['session_year_id'] = (int) $data['session_year_id'];
        $data['learner_ids'] = array_map('intval', $data['learner_ids']);
        sort($data['learner_ids']);
        $data['holders'] = array_map(fn ($h) => ['user_id' => (int) $h['user_id'], 'share_bp' => $this->basisPoints($h['share_percent'])], $data['holders']);
        usort($data['holders'], fn ($a, $b) => $a['user_id'] <=> $b['user_id']);
        abort_unless(array_sum(array_column($data['holders'], 'share_bp')) === 10000 && min(array_column($data['holders'], 'share_bp')) > 0, 422, 'Account holder shares must total exactly 100%.');
        $data['account_id'] = $id;
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'account_save', $data, function () use ($data, $id): array {
            $year = SessionYear::lockForUpdate()->findOrFail($data['session_year_id']);
            app(FeePlans::class)->year($year);
            $account = $id === null ? new FeeAccount : FeeAccount::lockForUpdate()->findOrFail($id);
            abort_unless((int) ($account->revision ?? 0) === (int) $data['revision'], 409, 'This family account changed. Review it before saving again.');
            abort_if(($account->ledger_account_id !== null || FeePayment::where('fee_account_id', $account->id ?? 0)->lockForUpdate()->first() !== null) && $account->currency !== $data['currency'], 422, 'An account with financial history keeps its currency.');
            $learners = Students::whereIn('id', $data['learner_ids'])->lockForUpdate()->get();
            abort_unless($learners->count() === count($data['learner_ids']), 404);
            $enrolled = Enrolment::where('session_year_id', $year->id)->whereIn('learner_id', $data['learner_ids'])->lockForUpdate()->get()->pluck('learner_id')->unique();
            abort_unless($enrolled->count() === count($data['learner_ids']), 422, 'Each learner needs an enrolment in the selected school year.');
            $assigned = FeeAccountLearner::where('session_year_id', $year->id)->whereIn('learner_id', $data['learner_ids'])->lockForUpdate()->get();
            abort_if($assigned->contains(fn ($r) => (int) $r->fee_account_id !== (int) ($account->id ?? 0)), 409, 'A learner already has a payer account for this school year.');
            $removed = FeeAccountLearner::where('session_year_id', $year->id)->where('fee_account_id', $account->id ?? 0)->whereNotIn('learner_id', $data['learner_ids'])->lockForUpdate()->pluck('learner_id');
            abort_if(InvoiceLine::where('session_year_id', $year->id)->whereIn('learner_id', $removed)->lockForUpdate()->first() !== null, 422, 'Posted charges need a separate credit or payer-transfer workflow.');
            $removedHolders = FeeAccountHolder::where('session_year_id', $year->id)->where('fee_account_id', $account->id ?? 0)
                ->whereNotIn('user_id', array_column($data['holders'], 'user_id'))->lockForUpdate()->pluck('user_id');
            $payments = FeePayment::where('fee_account_id', $account->id ?? 0)->lockForUpdate()->get();
            foreach ($payments as $payment) {
                if ($data['learner_ids'] === [] || $removedHolders->contains($payment->holder_user_id)
                    || ($payment->pinned_learner_id !== null && $removed->contains($payment->pinned_learner_id))) {
                    abort_if(app(FeePayments::class)->available($payment) > 0, 422, 'Held credits need a separate refund or payer-transfer workflow before removing a learner or holder assignment.');
                }
            }
            $this->holders($data['holders'], $data['learner_ids']);
            $before = $account->exists ? $account->only(['name', 'currency', 'active', 'revision']) : [];
            if (! $account->exists) {
                $account->reference = 'NEF'.strtoupper(Str::random(20));
            }
            $account->fill(['name' => $data['name'], 'currency' => $data['currency'], 'active' => true, 'revision' => (int) ($account->revision ?? 0) + 1]);
            $account->save();
            FeeAccountHolder::where('fee_account_id', $account->id)->where('session_year_id', $year->id)->delete();
            foreach ($data['holders'] as $holder) {
                FeeAccountHolder::create(['fee_account_id' => $account->id, 'session_year_id' => $year->id, ...$holder]);
            }
            FeeAccountLearner::where('fee_account_id', $account->id)->where('session_year_id', $year->id)->delete();
            foreach ($data['learner_ids'] as $learner) {
                FeeAccountLearner::create(['fee_account_id' => $account->id, 'session_year_id' => $year->id, 'learner_id' => $learner]);
            }
            Audit::record('money.family_account_saved', $account, $before, ['revision' => $account->revision, 'year_id' => $year->id, 'learner_count' => count($data['learner_ids']), 'holder_count' => count($data['holders'])]);

            return ['id' => (int) $account->id, 'revision' => $account->revision];
        });

        return FeeAccount::findOrFail($receipt['id']);
    }

    public function archive(User $actor, int $id, array $input): FeeAccount
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = Validator::make($input, self::operationRules())->validate() + ['account_id' => $id];
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'account_archive', $data, function () use ($id, $data): array {
            $account = FeeAccount::lockForUpdate()->findOrFail($id);
            abort_unless($account->revision === (int) $data['revision'], 409, 'This family account changed.');
            $account->update(['active' => false, 'revision' => $account->revision + 1]);
            Audit::record('money.family_account_archived', $account, [], ['revision' => $account->revision]);

            return ['id' => $id, 'revision' => $account->revision];
        });

        return FeeAccount::findOrFail($receipt['id']);
    }

    /** Validate current school, guardian permission and each declared share. */
    public function holders(array $holders, array $learners): array
    {
        $set = app(FamilyHolders::class)->snapshots([0 => ['holders' => $holders, 'learner_ids' => $learners]])[0];
        abort_unless($set['issues'] === [], 422, implode(' ', $set['issues']));

        return $set['holders'];
    }

    public static function operationRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0']];
    }

    public static function rules(bool $allowEmpty = false): array
    {
        return self::operationRules() + ['name' => ['required', 'string', 'max:160'], 'currency' => ['required', 'string', 'regex:/\A[A-Z]{3}\z/D'],
            'session_year_id' => ['required', 'integer', 'min:1'], 'learner_ids' => [$allowEmpty ? 'present' : 'required', 'array', 'min:'.($allowEmpty ? 0 : 1), 'max:1200'], 'learner_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'holders' => ['required', 'array', 'min:1', 'max:20'], 'holders.*.user_id' => ['required', 'integer', 'min:1', 'distinct'],
            'holders.*.share_percent' => ['required', 'string', 'regex:/\A(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)\z/D']];
    }

    private function basisPoints(string $percent): int
    {
        [$whole, $fraction] = array_pad(explode('.', $percent), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }
}
