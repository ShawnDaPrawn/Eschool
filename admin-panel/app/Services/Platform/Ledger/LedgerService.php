<?php

declare(strict_types=1);

namespace App\Services\Platform\Ledger;

use App\Models\Platform\SchoolMembership;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use DomainException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Internal posting kernel. Domain services must authorize the actor/owner first. */
final class LedgerService
{
    public const MAX_CENTS = 9000000000000000;

    public function openAccount(AccountType $type, string $reference, string $currency, ?int $ownerUserId = null): int
    {
        self::identifier($reference);
        $currency = Money::zero($currency)->currency;
        $school = app(CurrentSchool::class)->id();

        return DB::transaction(function () use ($school, $type, $reference, $currency, $ownerUserId): int {
            $this->lockSchool($school);
            if ($ownerUserId !== null && SchoolMembership::query()->active()->where('school_id', $school)->where('user_id', $ownerUserId)->lockForUpdate()->first() === null) {
                throw new DomainException('The account owner must have an active membership in this school.');
            }
            $existing = $this->accounts($school)->where('reference', $reference)->lockForUpdate()->first();
            if ($existing !== null) {
                if ($existing->type !== $type->value || $existing->currency !== $currency || $existing->owner_user_id != $ownerUserId) {
                    throw new DomainException('Account reference was already used with another definition.');
                }

                return (int) $existing->id;
            }

            return $this->accounts($school)->insertGetId([
                'school_id' => $school, 'type' => $type->value, 'reference' => $reference, 'module' => $type->module(),
                'currency' => $currency, 'normal_direction' => $type->normalDirection(), 'owner_user_id' => $ownerUserId,
                'balance_cents' => 0, 'created_at' => now('UTC')->format('Y-m-d H:i:s.u'),
            ]);
        }, 3);
    }

    /**
     * Durable key AND business source identity; neither expires with the HTTP
     * replay cache. A reversal is a new, authorized, oppositely directed entry.
     *
     * @param  list<Posting>  $postings
     */
    public function post(string $idempotencyKey, string $source, string $description, array $postings): int
    {
        self::identifier($idempotencyKey);
        self::identifier($source);
        if (trim($description) === '' || mb_strlen($description) > 255 || count($postings) < 2 || count($postings) > 100) {
            throw new InvalidArgumentException('An entry requires a description and 2 to 100 postings.');
        }
        $currency = $postings[0]->amount->currency;
        $sum = 0;
        $total = 0;
        $rows = [];
        foreach ($postings as $posting) {
            if ($posting->amount->currency !== $currency || $posting->amount->cents > self::MAX_CENTS - $total) {
                throw new InvalidArgumentException('An entry must use one currency within the cents limit.');
            }
            $total += $posting->amount->cents;
            $sum += $posting->direction === 'debit' ? $posting->amount->cents : -$posting->amount->cents;
            $rows[] = ['account_id' => $posting->accountId, 'cents' => $posting->amount->cents, 'direction' => $posting->direction];
        }
        if ($sum !== 0) {
            throw new InvalidArgumentException('A ledger entry must balance.');
        }
        usort($rows, fn (array $a, array $b): int => [$a['account_id'], $a['direction'], $a['cents']] <=> [$b['account_id'], $b['direction'], $b['cents']]);
        $hash = hash('sha256', json_encode([$source, $description, $currency, $rows], JSON_THROW_ON_ERROR));
        $school = app(CurrentSchool::class)->id();

        return DB::transaction(function () use ($school, $idempotencyKey, $source, $description, $currency, $rows, $hash): int {
            // Same lock order as module/package switches. Independent schools
            // do not block one another; same-school spends serialize here.
            $this->lockSchool($school);
            $existing = DB::table('ledger_entries')->where('school_id', $school)->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
            if ($existing !== null) {
                if ($existing->request_hash !== $hash || $existing->posted_at === null) {
                    throw new DomainException('Ledger key conflict or incomplete posting.');
                }

                return (int) $existing->id;
            }
            if (DB::table('ledger_entries')->where('school_id', $school)->where('source', $source)->lockForUpdate()->first() !== null) {
                throw new DomainException('This business source has already been posted.');
            }
            $ids = array_values(array_unique(array_column($rows, 'account_id')));
            $accounts = $this->accounts($school)->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($accounts->count() !== count($ids)) {
                throw new DomainException('Ledger accounts must belong to the active school.');
            }
            $deltas = [];
            $gate = app(ModuleGate::class);
            $writable = [];
            foreach ($rows as $row) {
                $account = $accounts->get($row['account_id']);
                if ($account->currency !== $currency || in_array($account->type, ['clearing', 'due_to_school'], true)) {
                    throw new DomainException('Account currency or collection phase does not permit this posting.');
                }
                $writable[$account->module] ??= $gate->writable($account->module, locked: true);
                if (! $writable[$account->module]) {
                    throw new DomainException('This financial module is unavailable or read-only.');
                }
                $deltas[$account->id] = ($deltas[$account->id] ?? 0) + ($row['direction'] === $account->normal_direction ? $row['cents'] : -$row['cents']);
            }
            foreach ($accounts as $account) {
                // A stale projection cannot authorize an overspend.
                $actual = $this->postingBalance($school, (int) $account->id, $account->normal_direction);
                if ($actual !== (int) $account->balance_cents) {
                    throw new DomainException('Ledger projection differs from postings; reconcile before posting.');
                }
                $next = $actual + ($deltas[$account->id] ?? 0);
                if (abs($next) > self::MAX_CENTS || ($account->type === 'wallet' && $next < 0)) {
                    throw new DomainException('The posting exceeds the account balance or cents limit.');
                }
            }
            $at = now('UTC')->format('Y-m-d H:i:s.u');
            $id = DB::table('ledger_entries')->insertGetId([
                'school_id' => $school, 'idempotency_key' => $idempotencyKey, 'source' => $source,
                'description' => $description, 'currency' => $currency, 'request_hash' => $hash, 'created_at' => $at,
            ]);
            foreach ($rows as $row) {
                DB::table('ledger_postings')->insert(['school_id' => $school, 'entry_id' => $id, ...$row]);
            }
            foreach ($accounts as $account) {
                $this->accounts($school)->where('id', $account->id)->update(['balance_cents' => (int) $account->balance_cents + ($deltas[$account->id] ?? 0)]);
            }
            // Trigger refuses any unbalanced finalization. All inserts and
            // projections roll back together if it refuses or a write fails.
            DB::table('ledger_entries')->where('school_id', $school)->where('id', $id)->update(['posted_at' => $at]);

            return $id;
        }, 3);
    }

    public function balance(int $accountId): Money
    {
        $school = app(CurrentSchool::class)->id();
        $account = $this->accounts($school)->where('id', $accountId)->first();
        if ($account === null) {
            throw new DomainException('Ledger account does not belong to this school.');
        }

        return Money::ofCents($this->postingBalance($school, $accountId, $account->normal_direction), $account->currency);
    }

    /** @return list<array{account_id: int, cached_cents: int, posting_cents: int}> */
    public function projectionDifferences(bool $rebuild = false): array
    {
        $school = app(CurrentSchool::class)->id();

        return DB::transaction(function () use ($school, $rebuild): array {
            $this->lockSchool($school);
            $differences = [];
            foreach ($this->accounts($school)->orderBy('id')->lockForUpdate()->get() as $account) {
                $actual = $this->postingBalance($school, (int) $account->id, $account->normal_direction);
                if ($actual !== (int) $account->balance_cents) {
                    $differences[] = ['account_id' => (int) $account->id, 'cached_cents' => (int) $account->balance_cents, 'posting_cents' => $actual];
                    if ($rebuild) {
                        $this->accounts($school)->where('id', $account->id)->update(['balance_cents' => $actual]);
                    }
                }
            }

            return $differences;
        }, 3);
    }

    private function postingBalance(int $school, int $account, string $normal): int
    {
        $value = DB::table('ledger_postings as p')->join('ledger_entries as e', function ($join) {
            $join->on('e.id', '=', 'p.entry_id')->on('e.school_id', '=', 'p.school_id');
        })->where('p.school_id', $school)->where('p.account_id', $account)->whereNotNull('e.posted_at')
            ->selectRaw('COALESCE(SUM(CASE WHEN p.direction = ? THEN p.cents ELSE -p.cents END), 0) AS total', [$normal])->lockForUpdate()->value('total');

        return (int) $value;
    }

    private function accounts(int $school): Builder
    {
        return DB::table('ledger_accounts')->where('school_id', $school);
    }

    private function lockSchool(int $school): void
    {
        if (DB::table('schools')->where('id', $school)->lockForUpdate()->first() === null) {
            throw new DomainException('Ledger requires a school.');
        }
    }

    private static function identifier(string $value): void
    {
        if (! preg_match('/\A[A-Za-z0-9][A-Za-z0-9:._-]{0,127}\z/D', $value)) {
            throw new InvalidArgumentException('Ledger identity must be 1 to 128 ASCII identifier characters.');
        }
    }
}
