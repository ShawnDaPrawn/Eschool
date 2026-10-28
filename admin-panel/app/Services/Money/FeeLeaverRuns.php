<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeLeaverRun;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\Validator;

final class FeeLeaverRuns
{
    public function prepare(User $actor, array $input): FeeLeaverRun
    {
        $data = Validator::make($input, self::prepareRules())->validate();
        foreach (['invoice_id', 'learner_id'] as $key) {
            $data[$key] = (int) $data[$key];
        }
        $receipt = app(BillingOperations::class)->once($actor, FeeLeaverPolicies::PERMISSION, 'fee_leaver_prepare', $data, function () use ($actor, $data): array {
            $snapshot = $this->calculate($actor, $data);
            $run = FeeLeaverRun::create(['fee_leaver_policy_id' => $snapshot['fee_leaver_policy_id'], 'fee_account_id' => $snapshot['fee_account_id'], 'invoice_id' => $data['invoice_id'], 'learner_id' => $data['learner_id'], 'session_year_id' => $snapshot['session_year_id'], 'operation_id' => $data['operation_id'], 'state' => 'preview', 'revision' => 1, 'fingerprint' => $this->fingerprint($snapshot), 'currency' => $snapshot['currency'], 'amount_cents' => $snapshot['amount_cents'], 'snapshot' => $snapshot, 'private_explanation' => $data['private_explanation'], 'prepared_by' => $actor->id]);
            Audit::record('money.fee_leaver_prepared', $run, [], ['amount_cents' => $run->amount_cents, 'invoice_id' => $data['invoice_id']]);

            return ['id' => (int) $run->id];
        });

        return FeeLeaverRun::findOrFail($receipt['id']);
    }

    public function approve(User $actor, int $id, array $input): FeeLeaverRun
    {
        $data = Validator::make($input, self::operationRules())->validate() + ['run_id' => $id];
        $data['revision'] = (int) $data['revision'];
        $receipt = app(BillingOperations::class)->once($actor, FeeLeaverPolicies::PERMISSION, 'fee_leaver_approve', $data, function () use ($actor, $id, $data): array {
            $run = FeeLeaverRun::lockForUpdate()->findOrFail($id);
            abort_unless($run->state === 'preview' && $run->revision === $data['revision'], 409, 'Review the current leaver credit before approval.');
            $snapshot = $this->verify($actor, $run);
            abort_unless(! $snapshot['policy']['independent_approval'] || (int) $actor->id !== (int) $run->prepared_by, 403, 'The school requires another Finance approver for this departure credit.');
            $run->update(['state' => 'approved', 'revision' => $run->revision + 1, 'approved_by' => $actor->id]);
            Audit::record('money.fee_leaver_approved', $run, [], ['revision' => $run->revision]);

            return ['id' => $id];
        });

        return FeeLeaverRun::findOrFail($receipt['id']);
    }

    public function post(User $actor, int $id, array $input): FeeLeaverRun
    {
        $data = Validator::make($input, self::operationRules())->validate() + ['run_id' => $id];
        $data['revision'] = (int) $data['revision'];
        $receipt = app(BillingOperations::class)->once($actor, FeeLeaverPolicies::PERMISSION, 'fee_leaver_post', $data, function () use ($actor, $id, $data): array {
            $run = FeeLeaverRun::lockForUpdate()->findOrFail($id);
            abort_unless($run->state === 'approved' && $run->revision === $data['revision'], 409, 'Approve and review the current leaver credit before posting.');
            $snapshot = $this->verify($actor, $run);
            // Current approval authority must survive until money is posted.
            $approver = User::findOrFail($run->approved_by);
            app(MoneyPolicy::class)->authorize($approver, FeeLeaverPolicies::PERMISSION, locked: true);
            $notes = [];
            foreach ($snapshot['credits'] as $credit) {
                $note = app(FeeCreditNotes::class)->post($actor, $credit['input'] + ['fingerprint' => $credit['preview']['fingerprint'], 'confirmed' => true]);
                $notes[] = (int) $note->id;
            }
            $run->update(['state' => 'posted', 'revision' => $run->revision + 1, 'credit_note_ids' => $notes, 'sealed_at' => now('UTC')]);
            app(FeeStatements::class)->complete(FeeAccount::findOrFail($run->fee_account_id));
            Audit::record('money.fee_leaver_posted', $run, [], ['amount_cents' => $run->amount_cents, 'credit_note_ids' => $notes]);

            return ['id' => $id];
        });

        return FeeLeaverRun::findOrFail($receipt['id']);
    }

    private function verify(User $actor, FeeLeaverRun $run): array
    {
        $snapshot = $this->calculate($actor, ['invoice_id' => $run->invoice_id, 'learner_id' => $run->learner_id, 'operation_id' => $run->operation_id]);
        abort_unless(hash_equals($run->fingerprint, $this->fingerprint($snapshot)), 409, 'Departure or financial inputs changed. Prepare a new leaver review.');

        return $snapshot;
    }

    private function calculate(User $actor, array $data): array
    {
        return app(FeeLeaverCalculator::class)->snapshot($actor, $data['invoice_id'], $data['learner_id'], $data['operation_id']);
    }

    private function fingerprint(array $snapshot): string
    {
        return hash_hmac('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public static function prepareRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'invoice_id' => ['required', 'integer', 'min:1'], 'learner_id' => ['required', 'integer', 'min:1'], 'private_explanation' => ['required', 'string', 'min:5', 'max:1200']];
    }

    public static function operationRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']];
    }
}
