<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeItemBatch;
use App\Models\Money\FeeItemEligibilityRule;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class FeeItemBatches
{
    public function preview(User $actor, array $input): array
    {
        app(MoneyPolicy::class)->authorize($actor, FeeItems::PERMISSION, true);
        $data = $this->validated($input);

        return DB::transaction(function () use ($actor, $data): array {
            DB::table('schools')->where('id', app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(MoneyPolicy::class)->authorize($actor, FeeItems::PERMISSION, true);
            $snapshot = $this->snapshot($actor, $data);

            return $snapshot + ['fingerprint' => $this->fingerprint($snapshot)];
        });
    }

    public function post(User $actor, array $input): FeeItemBatch
    {
        app(MoneyPolicy::class)->authorize($actor, FeeItems::PERMISSION, true);
        $data = $this->validated($input, true);
        $receipt = app(BillingOperations::class)->once($actor, FeeItems::PERMISSION, 'fee_item_batch', $data, function () use ($actor, $data): array {
            $snapshot = $this->snapshot($actor, $data);
            abort_unless(hash_equals($this->fingerprint($snapshot), $data['fingerprint']), 409, 'Item eligibility or payer details changed. Review a new batch.');
            abort_unless($snapshot['exceptions'] === [] || ($data['exclude_exceptions'] ?? false), 422, 'Confirm that the listed exceptions are excluded from this batch.');
            abort_unless($snapshot['charges'] !== [], 422, 'No new eligible item charges remain to post.');
            $ids = [];
            foreach ($snapshot['charges'] as $charge) {
                $posted = app(FeeCharges::class)->post($actor, $charge['input'] + ['fingerprint' => $charge['preview']['fingerprint'], 'confirmed' => true]);
                $ids[] = (int) $posted->id;
            }
            $retained = $snapshot;
            foreach ($retained['charges'] as &$row) {
                unset($row['input']);
            }unset($row);
            $batch = FeeItemBatch::create(['fee_item_id' => $snapshot['fee_item_id'], 'session_year_id' => $snapshot['session_year_id'], 'operation_id' => $data['operation_id'], 'fingerprint' => $data['fingerprint'], 'currency' => $snapshot['currency'], 'amount_cents' => $snapshot['amount_cents'], 'snapshot' => $retained, 'charge_ids' => $ids, 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            Audit::record('money.fee_item_batch_posted', $batch, [], ['charge_count' => count($ids), 'amount_cents' => $batch->amount_cents, 'excluded' => count($snapshot['exceptions'])]);

            return ['id' => (int) $batch->id];
        });

        return FeeItemBatch::findOrFail($receipt['id']);
    }

    private function snapshot(User $actor, array $data): array
    {
        $item = FeeItem::lockForUpdate()->findOrFail($data['fee_item_id']);
        $rule = FeeItemEligibilityRule::where('fee_item_id', $item->id)->lockForUpdate()->first();
        abort_unless($rule !== null && $rule->mode !== 'manual', 422, 'Configure a compulsory or membership rule before preparing an automatic batch.');
        $year = SessionYear::lockForUpdate()->findOrFail($data['session_year_id']);
        app(FeePlans::class)->year($year);
        $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        abort_unless((int) $year->getAttribute('default') === 1 && $data['charged_on'] >= substr((string) $year->getRawOriginal('start_date'), 0, 10) && $data['charged_on'] <= substr((string) $year->getRawOriginal('end_date'), 0, 10) && $data['charged_on'] <= $today, 422, 'Choose a completed charge date in the current school year.');
        abort_unless($item->state === 'active', 422, 'Choose an active fee item.');
        $learners = Enrolment::where('session_year_id', $year->id)->onDate($data['charged_on'])->orderBy('learner_id')->lockForUpdate()->distinct()->limit(1501)->pluck('learner_id')->map(fn ($id) => (int) $id)->all();
        abort_unless(count($learners) <= 1500, 422, 'A batch supports up to 1,500 learners.');
        $eligibility = app(FeeItemEligibility::class)->evaluate($item, $learners, (int) $year->id, $data['charged_on']);
        $eligible = $eligibility['matched_learner_ids'] ?? [];
        $assignments = FeeAccountLearner::where('session_year_id', $year->id)->whereIn('learner_id', $eligible)->orderBy('learner_id')->lockForUpdate()->get()->groupBy('learner_id');
        $exceptions = $eligibility['issues'] ?? [];
        $skipped = [];
        $targets = [];
        $charges = [];
        $total = 0;
        foreach ($eligible as $learner) {
            $links = $assignments->get($learner, collect());
            if ($links->count() !== 1) {
                $exceptions[] = ['learner_id' => $learner, 'message' => $links->isEmpty() ? 'No family account assigned' : 'More than one family account assigned'];

                continue;
            }
            $account = (int) $links->first()->fee_account_id;
            $target = $item->scope === 'family' ? $account : $learner;
            if (isset($targets[$target])) {
                continue;
            }$targets[$target] = true;
            $prior = FeeCharge::where('fee_item_id', $item->id)->where('session_year_id', $year->id)->where('scope', $item->scope)->where('target_id', $target)->where('reference_hash', hash('sha256', 'fee-occurrence:'.$data['source_reference']))->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless($prior->sealed_at !== null, 409, 'An incomplete item occurrence needs reconciliation.');
                $skipped[] = ['target_id' => $target, 'charge_id' => (int) $prior->id];

                continue;
            }
            $input = ['operation_id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'fee-item-batch:'.app(CurrentSchool::class)->id().':'.$data['operation_id'].':'.$target), 'fee_item_id' => (int) $item->id, 'fee_account_id' => $account, 'session_year_id' => (int) $year->id, 'learner_id' => $item->scope === 'family' ? null : $learner, 'charged_on' => $data['charged_on'], 'due_on' => $data['due_on'], 'source_reference' => $data['source_reference']];
            try {
                $preview = app(FeeCharges::class)->preview($actor, $input);
            } catch (ValidationException $e) {
                $exceptions[] = ['learner_id' => $learner, 'message' => implode(' ', array_merge(...array_values($e->errors())))];

                continue;
            } catch (HttpExceptionInterface $e) {
                if (! in_array($e->getStatusCode(), [409, 422], true)) {
                    throw $e;
                }$exceptions[] = ['learner_id' => $learner, 'message' => $e->getMessage()];

                continue;
            }
            abort_unless($preview['amount_cents'] <= LedgerService::MAX_CENTS - $total, 422, 'This batch exceeds the supported cents total.');
            $total += $preview['amount_cents'];
            $charges[] = ['input' => $input, 'preview' => $preview];
        }

        return ['operation_id' => $data['operation_id'], 'fee_item_id' => (int) $item->id, 'item_revision' => $item->revision, 'item_name' => $item->name, 'scope' => $item->scope, 'session_year_id' => (int) $year->id, 'charged_on' => $data['charged_on'], 'due_on' => $data['due_on'], 'reference_hash' => hash('sha256', 'fee-occurrence:'.$data['source_reference']), 'currency' => $item->currency, 'amount_cents' => $total, 'eligibility' => $eligibility, 'candidate_count' => count($learners), 'excluded_by_rule' => count($learners) - count($eligible) - count($eligibility['issues'] ?? []), 'exceptions' => $exceptions, 'skipped' => $skipped, 'charges' => $charges];
    }

    private function fingerprint(array $snapshot): string
    {
        return hash_hmac('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    private function validated(array $input, bool $posting = false): array
    {
        if (is_string($input['source_reference'] ?? null)) {
            $input['source_reference'] = mb_strtoupper(trim($input['source_reference']));
        }
        $data = Validator::make($input, self::rules($posting))->validate();
        foreach (['fee_item_id', 'session_year_id'] as $key) {
            $data[$key] = (int) $data[$key];
        }

        return $data;
    }

    public static function rules(bool $posting = false): array
    {
        return array_diff_key(FeeCharges::rules(), array_flip(['fee_account_id', 'learner_id'])) + ($posting ? ['fingerprint' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'confirmed' => ['required', 'accepted'], 'exclude_exceptions' => ['sometimes', 'boolean']] : []);
    }
}
