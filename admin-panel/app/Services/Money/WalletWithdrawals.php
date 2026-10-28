<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletSetting;
use App\Models\Money\WalletWithdrawal;
use App\Models\Money\WalletWithdrawalAction;
use App\Models\Money\WalletWithdrawalPolicy;
use App\Models\Money\WalletWithdrawalSlot;
use App\Models\Money\WalletWithdrawalSlotClosure;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Reserve original adult funds before approval; recording payment never sends it. */
final class WalletWithdrawals
{
    public function adult(User $actor, bool $write = false): User
    {
        $policy = app(WalletPolicy::class);
        $fresh = $policy->actor($actor, $write);
        $roles = $policy->roles($fresh);
        abort_unless(! in_array('Student', $roles, true) && array_intersect(['Parent', 'Teacher', 'Facilitator'], $roles) !== [], 403, 'Only parents, teachers and facilitators may withdraw their own wallet funds.');
        abort_if(in_array('Parent', $roles, true) && $fresh->email_verified_at === null, 403, 'Verify the parent account before requesting a withdrawal.');

        return $fresh;
    }

    public function policy(User $actor, array $input): WalletWithdrawalPolicy
    {
        $data = Validator::make($input, self::policyRules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['cash_threshold_cents'] = $this->cents($data['cash_threshold_amount']);
        unset($data['cash_threshold_amount']);
        $data['cash_at_boundary'] = (bool) $data['cash_at_boundary'];
        $data['accepting_requests'] = (bool) $data['accepting_requests'];
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_withdrawal_policy', $data, fn () => app(WalletPolicy::class)->finance($actor, true), function () use ($actor, $data): array {
            $currency = WalletSetting::lockForUpdate()->firstOrFail()->currency;
            abort_unless($currency !== 'ZAR' || $data['cash_threshold_cents'] === 25000, 422, 'The rand cash-or-bank boundary is R250. Choose explicitly which method includes exactly R250.');
            $row = WalletWithdrawalPolicy::lockForUpdate()->first() ?? new WalletWithdrawalPolicy;
            abort_unless((int) ($row->revision ?? 0) === $data['revision'], 409, 'Withdrawal policy changed. Review the current choices.');
            $row->fill(['currency' => $currency, 'cash_threshold_cents' => $data['cash_threshold_cents'], 'cash_at_boundary' => $data['cash_at_boundary'], 'accepting_requests' => $data['accepting_requests'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('wallet.withdrawal_policy_saved', $row, [], $row->only(['cash_threshold_cents', 'cash_at_boundary', 'accepting_requests', 'revision']));

            return ['id' => (int) $row->id];
        });

        return WalletWithdrawalPolicy::findOrFail($receipt['id']);
    }

    public function publishSlot(User $actor, array $input): WalletWithdrawalSlot
    {
        $data = Validator::make($input, self::slotRules())->validate();
        $data['capacity'] = (int) $data['capacity'];
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_withdrawal_slot', $data, fn () => app(WalletPolicy::class)->finance($actor, true), function () use ($actor, $data): array {
            $timezone = app(CurrentSchool::class)->school()->timezone ?? 'UTC';
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['starts_at'], $timezone);
            $end = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['ends_at'], $timezone);
            abort_unless($start !== null && $end !== null && $start->isFuture() && $end->greaterThan($start) && $start->diffInHours($end) <= 24, 422, 'Choose a future cash pickup slot ending after its start within one day.');
            $row = WalletWithdrawalSlot::create(['operation_id' => $data['operation_id'], 'starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'capacity' => $data['capacity'], 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            Audit::record('wallet.withdrawal_slot_published', $row, [], ['capacity' => $row->capacity]);

            return ['id' => (int) $row->id];
        });

        return WalletWithdrawalSlot::findOrFail($receipt['id']);
    }

    public function closeSlot(User $actor, int $slot, array $input): void
    {
        $data = Validator::make($input, self::reasonRules())->validate() + ['slot_id' => $slot];
        app(WalletOperations::class)->once($actor, 'wallet_withdrawal_slot_close', $data, fn () => app(WalletPolicy::class)->finance($actor, true), function () use ($actor, $data, $slot): array {
            WalletWithdrawalSlot::lockForUpdate()->findOrFail($slot);
            abort_if(WalletWithdrawalSlotClosure::where('slot_id', $slot)->exists(), 409, 'This pickup slot is already closed to new bookings.');
            WalletWithdrawalSlotClosure::create(['slot_id' => $slot, 'actor_id' => $actor->id, 'private_reason' => $data['private_reason'], 'created_at' => now('UTC')]);

            return ['id' => $slot];
        });
    }

    public function request(User $actor, array $input, ?int $offboardingHolder = null): WalletWithdrawal
    {
        $data = Validator::make($input, self::requestRules() + ($offboardingHolder !== null ? self::approvalRules() : []))->validate();
        $data['amount_cents'] = $this->cents($data['amount']);
        unset($data['amount']);
        $data['confirmed'] = true;
        $data['slot_id'] = isset($data['slot_id']) ? (int) $data['slot_id'] : null;
        $data['private_bank_instructions'] = $data['private_bank_instructions'] ?? null;
        $data['offboarding_holder'] = $offboardingHolder;
        if ($offboardingHolder !== null) {
            abort_unless(is_string($data['private_reason'] ?? null), 422, 'Explain the original-holder offboarding return.');
            $data['verified_original_holder'] = true;
        }
        $authority = function () use ($actor, $offboardingHolder): void {
            if ($offboardingHolder === null) {
                $this->adult($actor, true);
            } else {
                app(WalletPolicy::class)->finance($actor, true);
                abort_if($offboardingHolder === (int) $actor->id, 403);
                $holder = User::withTrashed()->lockForUpdate()->findOrFail($offboardingHolder);
                $roles = app(WalletPolicy::class)->roles($holder);
                $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
                abort_if(in_array('Student', $roles, true), 403, 'Learners never withdraw wallet money.');
                abort_unless(SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('user_id', $offboardingHolder)->whereIn('role', ['Parent', 'Teacher', 'Facilitator'])->where(fn ($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', $today))->lockForUpdate()->exists(), 404);
                abort_if(DB::table('platform_staff_identities')->where('user_id', $offboardingHolder)->where('active', true)->exists(), 403);
            }
        };
        $receipt = app(WalletOperations::class)->once($actor, 'wallet_withdrawal_request', $data, $authority, function () use ($actor, $data, $offboardingHolder): array {
            $policy = WalletWithdrawalPolicy::lockForUpdate()->first();
            abort_unless($policy !== null && $policy->accepting_requests, 422, 'Finance must explicitly enable withdrawal rules first.');
            $holder = $offboardingHolder ?? (int) $actor->id;
            $wallet = WalletAccount::where('user_id', $holder)->lockForUpdate()->firstOrFail();
            abort_unless($wallet->currency === $policy->currency, 409);
            $method = $this->method($policy, $data['amount_cents']);
            if ($method === 'cash') {
                abort_unless($data['slot_id'] !== null && $data['private_bank_instructions'] === null, 422, 'Choose an available cash pickup slot without bank instructions.');
                $slot = WalletWithdrawalSlot::lockForUpdate()->findOrFail($data['slot_id']);
                abort_unless($slot->starts_at->isFuture() && ! WalletWithdrawalSlotClosure::where('slot_id', $slot->id)->exists() && $this->bookings($slot->id) < $slot->capacity, 409, 'The cash pickup slot is no longer available.');
            } else {
                abort_unless($data['slot_id'] === null && is_string($data['private_bank_instructions']) && mb_strlen(trim($data['private_bank_instructions'])) >= 10, 422, 'Provide private bank instructions for the original wallet holder.');
            }
            abort_unless($this->soundBalance($wallet->ledger_account_id) >= $data['amount_cents'], 422, 'Your available wallet balance does not cover this withdrawal.');
            $ledger = app(LedgerService::class);
            $pending = $ledger->openAccount(AccountType::Wallet, 'wallet:withdrawal:'.$data['operation_id'], $wallet->currency, module: 'wallet');
            abort_unless($this->soundBalance($pending) === 0, 409);
            $source = 'wallet-withdrawal:reserved:'.$data['operation_id'];
            $money = Money::ofCents($data['amount_cents'], $wallet->currency);
            $entry = $ledger->post($source, $source, 'Reserved original adult wallet withdrawal', [Posting::debit($wallet->ledger_account_id, $money), Posting::credit($pending, $money)]);
            $row = WalletWithdrawal::create(['operation_id' => $data['operation_id'], 'actor_id' => $actor->id, 'holder_user_id' => $holder, 'wallet_account_id' => $wallet->id, 'pending_account_id' => $pending, 'reserved_entry_id' => $entry, 'currency' => $wallet->currency, 'amount_cents' => $data['amount_cents'], 'method' => $method, 'policy_revision' => $policy->revision, 'slot_id' => $data['slot_id'], 'private_bank_instructions' => $data['private_bank_instructions'], 'private_reason' => $data['private_reason'] ?? null, 'created_at' => now('UTC')]);
            Audit::record('wallet.withdrawal_requested', $row, [], ['amount_cents' => $row->amount_cents, 'method' => $method, 'offboarding' => $offboardingHolder !== null]);

            return ['id' => (int) $row->id];
        });

        return WalletWithdrawal::findOrFail($receipt['id']);
    }

    public function approve(User $actor, int $id, array $input): WalletWithdrawal
    {
        $data = Validator::make($input, self::approvalRules())->validate() + ['withdrawal_id' => $id];
        $data['verified_original_holder'] = true;
        $authority = function () use ($actor, $id): void {
            app(WalletPolicy::class)->finance($actor, true);
            abort_if(WalletWithdrawal::lockForUpdate()->findOrFail($id)->holder_user_id === (int) $actor->id, 403, 'A different Finance operator must approve your withdrawal.');
        };
        app(WalletOperations::class)->once($actor, 'wallet_withdrawal_approve', $data, $authority, function () use ($actor, $id, $data): array {
            $row = WalletWithdrawal::lockForUpdate()->findOrFail($id);
            abort_unless($this->state($row) === 'requested', 409, 'Only a pending withdrawal can be approved.');
            $this->currentMethod($row);
            abort_unless($this->soundBalance($row->pending_account_id) === $row->amount_cents, 409);
            WalletWithdrawalAction::create(['withdrawal_id' => $id, 'operation_id' => $data['operation_id'], 'status' => 'approved', 'actor_id' => $actor->id, 'created_at' => now('UTC')]);
            Audit::record('wallet.withdrawal_approved', $row, [], []);

            return ['id' => $id];
        });

        return WalletWithdrawal::findOrFail($id);
    }

    public function finish(User $actor, int $id, string $status, array $input): WalletWithdrawal
    {
        abort_unless(in_array($status, ['cancelled', 'rejected', 'paid'], true), 422);
        if (is_string($input['payout_reference'] ?? null)) {
            $input['payout_reference'] = FeePayments::reference($input['payout_reference']);
        }
        $data = Validator::make($input, $status === 'paid' ? self::payoutRules() : self::reasonRules())->validate() + ['withdrawal_id' => $id, 'status' => $status];
        $data[$status === 'paid' ? 'verified_actual_payout' : 'confirmed'] = true;
        if ($status === 'paid') {
            $data['verified_original_holder'] = true;
        }
        $authority = function () use ($actor, $id, $status): void {
            $row = WalletWithdrawal::lockForUpdate()->findOrFail($id);
            if ($status === 'cancelled' && $row->holder_user_id === (int) $actor->id) {
                $this->adult($actor, true);
            } else {
                app(WalletPolicy::class)->finance($actor, true);
                abort_if($status === 'paid' && $row->holder_user_id === (int) $actor->id, 403, 'A different Finance operator must verify your payout.');
            }
        };
        app(WalletOperations::class)->once($actor, 'wallet_withdrawal_'.$status, $data, $authority, function () use ($actor, $id, $status, $data): array {
            $row = WalletWithdrawal::lockForUpdate()->findOrFail($id);
            $state = $this->state($row);
            abort_unless(in_array($state, ['requested', 'approved'], true), 409, 'This withdrawal already has a final outcome.');
            abort_if($status === 'cancelled' && $row->holder_user_id === (int) $actor->id && $state !== 'requested', 409, 'Finance is processing this approved withdrawal. Contact Finance before any cancellation.');
            $ledger = app(LedgerService::class);
            abort_unless($this->soundBalance($row->pending_account_id) === $row->amount_cents, 409, 'Reconcile the withdrawal reservation before continuing.');
            $hash = null;
            $evidence = $data['private_reason'] ?? null;
            $cashOperator = null;
            if ($status === 'paid') {
                abort_unless($state === 'approved', 409, 'Finance must approve the withdrawal before recording actual payment.');
                $this->currentMethod($row);
                $approval = WalletWithdrawalAction::where('withdrawal_id', $id)->where('status', 'approved')->firstOrFail();
                app(WalletPolicy::class)->finance(User::findOrFail($approval->actor_id), true);
                abort_unless($data['paid_on'] <= now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString(), 422, 'Record only an already completed outside payout.');
                $hash = app(ExternalDisbursements::class)->available($row->method, $row->currency, $data['payout_reference']);
                $counter = $ledger->openAccount($row->method === 'cash' ? AccountType::CashDesk : AccountType::WalletBank, 'wallet:'.$row->method.':'.$row->currency, $row->currency, module: 'wallet');
                abort_unless($this->soundBalance($counter) >= $row->amount_cents, 422, 'Reconcile and fund the actual payout asset before recording payment.');
                $evidence = json_encode(['reference' => $data['payout_reference'], 'verification' => $data['private_evidence']], JSON_THROW_ON_ERROR);
                $cashOperator = $row->method === 'cash' ? (int) $actor->id : null;
            } else {
                $wallet = WalletAccount::lockForUpdate()->findOrFail($row->wallet_account_id);
                abort_unless($wallet->user_id === $row->holder_user_id && $wallet->currency === $row->currency, 409);
                $counter = $wallet->ledger_account_id;
                abort_if($row->amount_cents > LedgerService::MAX_CENTS - $this->soundBalance($counter), 422, 'The original wallet would exceed its supported balance.');
            }
            $source = 'wallet-withdrawal:'.$status.':'.$data['operation_id'];
            $money = Money::ofCents($row->amount_cents, $row->currency);
            $entry = $ledger->post($source, $source, $status === 'paid' ? 'Verified original-holder wallet payout' : 'Released original wallet withdrawal reservation', [Posting::debit($row->pending_account_id, $money), Posting::credit($counter, $money)]);
            WalletWithdrawalAction::create(['withdrawal_id' => $id, 'operation_id' => $data['operation_id'], 'status' => $status, 'actor_id' => $actor->id, 'ledger_entry_id' => $entry, 'counter_account_id' => $counter, 'cash_operator_id' => $cashOperator, 'reference_hash' => $hash, 'paid_on' => $status === 'paid' ? $data['paid_on'] : null, 'private_evidence' => $evidence, 'created_at' => now('UTC')]);
            Audit::record('wallet.withdrawal_'.$status, $row, [], ['amount_cents' => $row->amount_cents, 'ledger_entry_id' => $entry]);

            return ['id' => $id];
        });

        return WalletWithdrawal::findOrFail($id);
    }

    public function state(WalletWithdrawal $row): string
    {
        return WalletWithdrawalAction::where('withdrawal_id', $row->id)->orderByDesc('id')->value('status') ?? 'requested';
    }

    private function currentMethod(WalletWithdrawal $row): void
    {
        $policy = WalletWithdrawalPolicy::lockForUpdate()->first();
        abort_unless($policy !== null && $policy->currency === $row->currency && $this->method($policy, $row->amount_cents) === $row->method, 409, 'The cash-or-bank policy changed. Cancel and review this request again.');
    }

    private function method(WalletWithdrawalPolicy $policy, int $cents): string
    {
        return $cents < $policy->cash_threshold_cents || ($cents === $policy->cash_threshold_cents && $policy->cash_at_boundary) ? 'cash' : 'eft';
    }

    private function cents(string $amount): int
    {
        $cents = Money::fromDecimalString($amount)->cents;
        abort_unless($cents > 0 && $cents <= 100000000000, 422, 'Use a positive supported withdrawal amount.');

        return $cents;
    }

    private function soundBalance(int $account): int
    {
        $balance = app(LedgerService::class)->balance($account)->cents;
        $cached = DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('id', $account)->lockForUpdate()->value('balance_cents');
        abort_unless($cached !== null && (int) $cached === $balance, 409, 'Reconcile wallet balances before continuing.');

        return $balance;
    }

    private function bookings(int $slot): int
    {
        return WalletWithdrawal::where('slot_id', $slot)->whereNotExists(fn ($q) => $q->selectRaw('1')->from('wallet_withdrawal_actions as a')->whereColumn('a.school_id', 'wallet_withdrawals.school_id')->whereColumn('a.withdrawal_id', 'wallet_withdrawals.id')->whereIn('a.status', ['cancelled', 'rejected']))->count();
    }

    public function index(User $actor, int $page = 1, int $perPage = 30): array
    {
        abort_unless($page >= 1 && $page <= 1000000 && $perPage >= 1 && $perPage <= 50, 422);

        return DB::transaction(function () use ($actor, $page, $perPage): array {
            $fresh = app(WalletPolicy::class)->actor($actor);
            $roles = app(WalletPolicy::class)->roles($fresh);
            $isAdult = ! in_array('Student', $roles, true) && array_intersect(['Parent', 'Teacher', 'Facilitator'], $roles) !== [] && (! in_array('Parent', $roles, true) || $fresh->email_verified_at !== null);
            $policy = WalletWithdrawalPolicy::first();
            $accepting = $policy !== null && $policy->accepting_requests;
            $readOnly = ! app(ModuleGate::class)->writable('wallet');
            $query = WalletWithdrawal::where('holder_user_id', $actor->id);
            $total = (clone $query)->count();
            $timezone = app(CurrentSchool::class)->school()->timezone ?? 'UTC';
            $slots = [];
            if ($isAdult && ! $readOnly && $accepting) {
                foreach (WalletWithdrawalSlot::where('starts_at', '>', now('UTC'))->orderBy('starts_at')->limit(100)->get() as $slot) {
                    if (WalletWithdrawalSlotClosure::where('slot_id', $slot->id)->exists()) {
                        continue;
                    }$remaining = $slot->capacity - $this->bookings($slot->id);
                    if ($remaining < 1) {
                        continue;
                    }
                    $slots[] = ['id' => (int) $slot->id, 'starts_at' => $slot->starts_at->utc()->toISOString(), 'ends_at' => $slot->ends_at->utc()->toISOString(), 'label' => $slot->starts_at->setTimezone($timezone)->format('Y-m-d H:i').' – '.$slot->ends_at->setTimezone($timezone)->format('Y-m-d H:i').' '.$timezone, 'available_places' => $remaining];
                }
            }

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'currency' => WalletSetting::first()?->currency, 'is_adult' => $isAdult, 'read_only' => $readOnly, 'can_request' => $isAdult && ! $readOnly && $accepting && WalletAccount::where('user_id', $actor->id)->exists(), 'policy_revision' => $policy?->revision, 'cash_threshold_cents' => $policy?->cash_threshold_cents, 'cash_at_boundary' => $policy?->cash_at_boundary, 'timezone' => $timezone, 'slots' => $slots, 'current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'items' => $query->orderByDesc('id')->offset(($page - 1) * $perPage)->limit($perPage)->get()->map(fn ($row) => $this->receipt($row))->all()];
        });
    }

    public function receipt(WalletWithdrawal $row): array
    {
        $last = WalletWithdrawalAction::where('withdrawal_id', $row->id)->orderByDesc('id')->first();
        $slot = $row->slot_id !== null ? WalletWithdrawalSlot::findOrFail($row->slot_id) : null;
        $timezone = app(CurrentSchool::class)->school()->timezone ?? 'UTC';
        $pickupLabel = $slot !== null ? $slot->starts_at->setTimezone($timezone)->format('Y-m-d H:i').' – '.$slot->ends_at->setTimezone($timezone)->format('Y-m-d H:i').' '.$timezone : null;

        return ['id' => (int) $row->id, 'reference' => 'WW-'.$row->id, 'school_id' => app(CurrentSchool::class)->id(), 'holder_user_id' => $row->holder_user_id, 'operation_id' => $row->operation_id, 'wallet_id' => $row->wallet_account_id, 'currency' => $row->currency, 'amount_cents' => $row->amount_cents, 'method' => $row->method, 'status' => $last->status ?? 'requested', 'created_at' => CarbonImmutable::parse($row->created_at)->utc()->toISOString(), 'processed_at' => $last ? CarbonImmutable::parse($last->created_at)->utc()->toISOString() : null, 'pickup_label' => $pickupLabel, 'pickup_starts_at' => $slot?->starts_at->utc()->toISOString(), 'pickup_ends_at' => $slot?->ends_at->utc()->toISOString(), 'paid_on' => $last?->paid_on, 'payout_reference' => $last?->status === 'paid' ? 'WW-PAY-'.$last->id : null];
    }

    public static function policyRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'cash_threshold_amount' => FeePayments::rules()['amount'], 'cash_at_boundary' => ['required', 'boolean'], 'accepting_requests' => ['required', 'boolean']];
    }

    public static function slotRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'starts_at' => ['required', 'date_format:Y-m-d\TH:i'], 'ends_at' => ['required', 'date_format:Y-m-d\TH:i'], 'capacity' => ['required', 'integer', 'min:1', 'max:1000']];
    }

    public static function requestRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'amount' => FeePayments::rules()['amount'], 'slot_id' => ['nullable', 'integer', 'min:1'], 'private_bank_instructions' => ['nullable', 'string', 'min:10', 'max:2000'], 'private_reason' => ['nullable', 'string', 'min:5', 'max:1000'], 'confirmed' => ['required', 'accepted']];
    }

    public static function approvalRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'verified_original_holder' => ['required', 'accepted']];
    }

    public static function reasonRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'private_reason' => ['required', 'string', 'min:5', 'max:1000'], 'confirmed' => ['required', 'accepted']];
    }

    public static function payoutRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'payout_reference' => FeePayments::rules()['source_reference'], 'paid_on' => ['required', 'date_format:Y-m-d'], 'private_evidence' => ['required', 'string', 'min:10', 'max:2000'], 'verified_actual_payout' => ['required', 'accepted'], 'verified_original_holder' => ['required', 'accepted']];
    }
}
