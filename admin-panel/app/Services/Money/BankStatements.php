<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\BankStatementImport;
use App\Models\Money\BankStatementLine;
use App\Models\Money\BankStatementResolution;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeePayment;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

/** Imports are evidence, never postings. Every receipt needs its own reviewed match. */
final class BankStatements
{
    public const PERMISSION = FeePayments::PERMISSION;

    public function previewImport(User $actor, string $csv): array
    {
        return $this->locked($actor, function () use ($actor, $csv): array {
            $rows = app(BankCsv::class)->parse($csv);
            $existing = $this->checkRows($rows);
            $payload = base64_encode(json_encode($rows, JSON_THROW_ON_ERROR));

            return ['rows' => $rows, 'payload' => $payload, 'existing_count' => $existing,
                'fingerprint' => $this->fingerprint($actor, ['import', $rows])];
        });
    }

    public function import(User $actor, array $input): BankStatementImport
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = Validator::make($input, self::importRules())->validate();
        $decoded = base64_decode($data['payload'], true);
        abort_unless($decoded !== false, 422, 'Invalid import review.');
        try {
            $rows = json_decode($decoded, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            abort(422, 'Invalid import review.');
        }
        abort_unless(is_array($rows) && array_is_list($rows) && count($rows) >= 1 && count($rows) <= 500, 422, 'Invalid import review.');
        abort_unless(hash_equals($this->fingerprint($actor, ['import', $rows]), $data['fingerprint']), 409, 'The import review changed. Upload and review the CSV again.');
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'bank_import', $data, function () use ($actor, $rows): array {
            $this->checkRows($rows);
            $digest = self::digest($rows);
            $prior = BankStatementImport::where('digest', $digest)->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless($prior->sealed_at !== null, 409, 'The prior bank import needs reconciliation.');

                return ['id' => (int) $prior->id];
            }
            $import = BankStatementImport::create(['digest' => $digest, 'row_count' => count($rows), 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            foreach ($rows as $row) {
                $line = BankStatementLine::where('source_hash', hash('sha256', $row['transaction_reference']))->lockForUpdate()->first();
                if ($line === null) {
                    $line = BankStatementLine::create(['source_hash' => hash('sha256', $row['transaction_reference']), 'payload_hash' => self::digest($row),
                        'received_on' => $row['received_on'], 'direction' => $row['direction'], 'amount_cents' => $row['amount_cents'], 'currency' => $row['currency'],
                        'details' => ['transaction_reference' => $row['transaction_reference'], 'family_reference' => $row['family_reference'], 'description' => $row['description']], 'created_at' => now('UTC')]);
                }
                DB::table('bank_statement_import_rows')->insert(['school_id' => app(CurrentSchool::class)->id(), 'bank_statement_import_id' => $import->id, 'bank_statement_line_id' => $line->id]);
            }
            $import->update(['sealed_at' => now('UTC')]);
            Audit::record('money.bank_statement_imported', $import, [], ['row_count' => count($rows)]);

            return ['id' => (int) $import->id];
        });

        return BankStatementImport::findOrFail($receipt['id']);
    }

    public function previewMatch(User $actor, int $id, array $input): array
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = $this->selection($input);

        return $this->locked($actor, fn () => $this->matchSnapshot($actor, BankStatementLine::lockForUpdate()->findOrFail($id), $data));
    }

    public function resolve(User $actor, int $id, array $input): BankStatementResolution
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = Validator::make($input, self::resolveRules())->validate();
        $data = [...$this->selection($data), 'operation_id' => $data['operation_id'], 'fingerprint' => $data['fingerprint'], 'confirmed' => $data['confirmed'], 'line_id' => $id];
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'bank_resolution', $data, function () use ($actor, $id, $data): array {
            $line = BankStatementLine::lockForUpdate()->findOrFail($id);
            $snapshot = $this->matchSnapshot($actor, $line, $data);
            abort_unless(hash_equals($snapshot['fingerprint'], $data['fingerprint']), 409, 'The bank match changed. Review the current transaction and holder again.');
            $payment = null;
            if ($data['kind'] === 'matched') {
                $payment = $snapshot['existing_payment_id'];
                if ($payment === null) {
                    $payment = app(FeePayments::class)->record($actor, ['operation_id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'navigateed:bank-receipt:'.app(CurrentSchool::class)->id().':'.$id)->toString(),
                        'fee_account_id' => $data['fee_account_id'], 'holder_user_id' => $data['holder_user_id'], 'pinned_learner_id' => $data['pinned_learner_id'],
                        'method' => 'eft', 'source_reference' => $line->details['transaction_reference'], 'amount' => Money::ofCents($line->amount_cents, $line->currency)->toDecimalString(),
                        'received_on' => $line->received_on->format('Y-m-d'), 'confirmed' => true])->id;
                }
            }
            $resolution = BankStatementResolution::create(['bank_statement_line_id' => $id, 'kind' => $data['kind'], 'payment_id' => $payment, 'reason' => $data['reason'],
                'fingerprint' => $data['fingerprint'], 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            Audit::record('money.bank_statement_resolved', $resolution, [], ['line_id' => $id, 'kind' => $data['kind'], 'payment_id' => $payment]);

            return ['id' => (int) $resolution->id];
        });

        return BankStatementResolution::findOrFail($receipt['id']);
    }

    public function suggestion(BankStatementLine $line): ?FeeAccount
    {
        // Exact family reference only. A description or amount is not a payer identity.
        return $line->details['family_reference'] === '' ? null : FeeAccount::where('reference', $line->details['family_reference'])->first();
    }

    private function matchSnapshot(User $actor, BankStatementLine $line, array $data): array
    {
        abort_if(BankStatementResolution::where('bank_statement_line_id', $line->id)->lockForUpdate()->first() !== null, 409, 'This bank transaction is already resolved.');
        $complete = DB::table('bank_statement_import_rows as r')->join('bank_statement_imports as i', function ($j) {
            $j->on('i.school_id', '=', 'r.school_id')->on('i.id', '=', 'r.bank_statement_import_id');
        })->where('r.school_id', app(CurrentSchool::class)->id())->where('r.bank_statement_line_id', $line->id)->whereNotNull('i.sealed_at')->exists();
        abort_unless($complete, 409, 'This bank import needs reconciliation.');
        $snapshot = ['line_id' => (int) $line->id, 'payload_hash' => $line->payload_hash, ...$data, 'existing_payment_id' => null];
        // Operation/review metadata is not part of the selected bank/payer state.
        unset($snapshot['operation_id'], $snapshot['fingerprint'], $snapshot['confirmed']);
        if ($data['kind'] === 'matched') {
            abort_unless($line->direction === 'credit', 422, 'A bank debit cannot become a fee receipt.');
            $account = FeeAccount::lockForUpdate()->findOrFail($data['fee_account_id']);
            abort_unless($account->currency === $line->currency, 422, 'Bank and family currencies must agree.');
            $holders = app(FeePayments::class)->holders($account);
            abort_unless(array_key_exists($data['holder_user_id'], $holders), 422, 'Choose an explicitly recorded holder of this family account.');
            if ($data['pinned_learner_id'] !== null) {
                abort_unless(in_array($data['pinned_learner_id'], app(FeePayments::class)->eligibleLearners($account, $data['holder_user_id']), true), 422, 'Choose a learner belonging to this holder and account.');
            }
            app(FeeStatements::class)->complete($account);
            $existing = FeePayment::where('method', 'eft')->where('source_reference', $line->details['transaction_reference'])->lockForUpdate()->first();
            if ($existing !== null) {
                abort_unless($existing->amount_cents === $line->amount_cents && $existing->currency === $line->currency && $existing->received_on->isSameDay($line->received_on)
                    && $existing->fee_account_id === (int) $account->id && $existing->holder_user_id === $data['holder_user_id'] && $existing->pinned_learner_id === $data['pinned_learner_id'], 409, 'An EFT receipt already uses this bank reference with different details. Resolve the discrepancy before matching.');
                abort_if(BankStatementResolution::where('payment_id', $existing->id)->exists(), 409, 'This receipt is already linked to a bank transaction.');
                $snapshot['existing_payment_id'] = (int) $existing->id;
            }
            $snapshot['account_name'] = $account->name;
            $snapshot['account_revision'] = $account->revision;
            $snapshot['holder_name'] = $holders[$data['holder_user_id']];
            $snapshot['learner_name'] = null;
            if ($data['pinned_learner_id'] !== null) {
                $learner = DB::table('students as s')->join('users as u', 'u.id', '=', 's.user_id')
                    ->where('s.school_id', app(CurrentSchool::class)->id())->where('s.id', $data['pinned_learner_id'])->first(['u.first_name', 'u.last_name']);
                abort_unless($learner !== null, 422, 'The pinned learner record is unavailable.');
                $snapshot['learner_name'] = trim($learner->first_name.' '.$learner->last_name);
            }
        }
        $snapshot['fingerprint'] = $this->fingerprint($actor, ['match', $snapshot]);

        return $snapshot;
    }

    private function checkRows(array $rows): int
    {
        $hashes = array_map(fn ($r) => hash('sha256', $r['transaction_reference']), $rows);
        $prior = BankStatementLine::whereIn('source_hash', $hashes)->lockForUpdate()->get()->keyBy('source_hash');
        $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        foreach ($rows as $row) {
            abort_unless($row['received_on'] <= $today, 422, 'Future-dated bank rows cannot be imported as received funds.');
            $line = $prior->get(hash('sha256', $row['transaction_reference']));
            abort_if($line !== null && ! hash_equals($line->payload_hash, self::digest($row)), 409, 'An imported transaction reference has different bank details. Nothing was imported.');
        }

        return $prior->count();
    }

    private function locked(User $actor, callable $read): array
    {
        return DB::transaction(function () use ($actor, $read): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(MoneyPolicy::class)->authorize($actor, self::PERMISSION, true);

            return $read();
        }, 3);
    }

    private function fingerprint(User $actor, array $data): string
    {
        return hash_hmac('sha256', json_encode([app(CurrentSchool::class)->id(), (int) $actor->id, $data], JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    private static function digest(array $data): string
    {
        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function selection(array $input): array
    {
        if (is_string($input['reason'] ?? null)) {
            $input['reason'] = trim($input['reason']);
        }
        $data = Validator::make($input, self::selectionRules())->validate();

        return ['kind' => $data['kind'], 'fee_account_id' => $data['kind'] === 'matched' ? (int) $data['fee_account_id'] : null,
            'holder_user_id' => $data['kind'] === 'matched' ? (int) $data['holder_user_id'] : null,
            'pinned_learner_id' => $data['kind'] === 'matched' && ! empty($data['pinned_learner_id']) ? (int) $data['pinned_learner_id'] : null, 'reason' => trim($data['reason'])];
    }

    public static function selectionRules(): array
    {
        return ['kind' => ['required', 'in:matched,excluded'], 'fee_account_id' => ['nullable', 'required_if:kind,matched', 'integer', 'min:1'],
            'holder_user_id' => ['nullable', 'required_if:kind,matched', 'integer', 'min:1'], 'pinned_learner_id' => ['nullable', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:10', 'max:1000', 'not_regex:/\A\s*\z/u']];
    }

    public static function importRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'payload' => ['required', 'string', 'max:600000'], 'fingerprint' => ['required', 'regex:/\A[a-f0-9]{64}\z/D'], 'confirmed' => ['required', 'accepted']];
    }

    public static function resolveRules(): array
    {
        return self::selectionRules() + ['operation_id' => ['required', 'uuid'], 'fingerprint' => ['required', 'regex:/\A[a-f0-9]{64}\z/D'], 'confirmed' => ['required', 'accepted']];
    }
}
