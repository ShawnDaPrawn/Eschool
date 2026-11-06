<?php

declare(strict_types=1);

namespace App\Services\SchoolBusiness;

use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\Notification;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\SchoolBusiness\ReenrolmentAcceptance;
use App\Models\SchoolBusiness\ReenrolmentConfirmation;
use App\Models\SchoolBusiness\ReenrolmentContract;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\Notifier;
use Illuminate\Support\Facades\Validator;

/**
 * Re-enrolment e-contracts (Action Plan 5.10).
 *
 * The school publishes a versioned contract for next school year. A guardian
 * who may act for the child accepts the exact wording; the acceptance keeps
 * who, when, the version, the text hash, the attested name and the IP
 * address. The school's acceptance rule decides when the child is
 * re-enrolment confirmed:
 *
 * - all_fee_payers: every fee-responsible guardian has accepted;
 * - fee_payer: one fee-responsible guardian has accepted;
 * - any_guardian: any approving guardian has accepted.
 *
 * Fee-responsible guardians are the payers on the child's family fee
 * account for the contract year, else for the current year; with no family
 * account, the guardians whose link carries fee visibility. Under a fee rule
 * another guardian may add their acceptance only after a fee-responsible
 * guardian has accepted: they cannot accept alone. A new version needs new
 * acceptances. The year-end rollover reads the result (statuses()); this
 * class never changes promotions.
 */
class ReenrolmentContracts
{
    public const MODULE = 'reenrolment';

    public const PERMISSION = 'reenrolment-manage';

    public const NOTICE = 'reenrolment.requested';

    public const LINK = '/reenrolment';

    public const RULES = ['all_fee_payers', 'fee_payer', 'any_guardian'];

    public static function rules(string $action): array
    {
        return match ($action) {
            'publish' => ['operation_id' => ['required', 'uuid'], 'session_year_id' => ['required', 'integer', 'min:1'], 'title' => ['required', 'string', 'max:191'],
                'body' => ['required', 'string', 'min:20', 'max:60000'], 'acceptance_rule' => ['required', 'in:'.implode(',', self::RULES)], 'respond_by' => ['required', 'date_format:Y-m-d']],
            'withdraw' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']],
            'remind' => ['operation_id' => ['required', 'uuid']],
            'accept' => ['operation_id' => ['required', 'uuid'], 'contract_id' => ['required', 'integer', 'min:1'], 'version' => ['required', 'integer', 'min:1'],
                'text_hash' => ['required', 'string', 'size:64'], 'signed_name' => ['required', 'string', 'max:191'], 'acknowledged' => ['required', 'accepted']],
            'outstanding' => ['page' => ['sometimes', 'integer', 'between:1,100000']],
            default => throw new \InvalidArgumentException('Unknown action.'),
        };
    }

    public static function textHash(string $title, string $body): string
    {
        return hash('sha256', $title."\n\n".$body);
    }

    private function auth(): BusinessAuthority
    {
        return app(BusinessAuthority::class);
    }

    private function currentYear(): SessionYear
    {
        return SessionYear::findOrFail((int) schoolSetting('session_year'));
    }

    /** Publishing a new version for the same year closes the previous one. */
    public function publish(User $actor, array $input): array
    {
        $this->auth()->staff($actor, self::MODULE, self::PERMISSION, true);
        $data = Validator::make($input, self::rules('publish'))->validate();
        $data['session_year_id'] = (int) $data['session_year_id'];

        return app(BusinessOperations::class)->once($actor, 'reenrolment.publish', $data, fn () => $this->auth()->staff($actor, self::MODULE, self::PERMISSION, true), function () use ($actor, $data): array {
            $year = SessionYear::lockForUpdate()->findOrFail($data['session_year_id']);
            $current = $this->currentYear();
            abort_unless(is_string($year->getRawOriginal('start_date')) && $year->getRawOriginal('start_date') > (string) $current->getRawOriginal('end_date'), 422, 'Publish the contract for a school year that starts after this one ends.');
            abort_unless($data['respond_by'] >= app(AcademicAuthority::class)->today() && $data['respond_by'] < (string) $year->getRawOriginal('start_date'), 422, 'Choose a reply date before next year starts.');
            $previous = ReenrolmentContract::where('session_year_id', $year->id)->where('state', 'open')->lockForUpdate()->first();
            $version = (int) ReenrolmentContract::where('session_year_id', $year->id)->lockForUpdate()->max('version') + 1;
            $at = now('UTC');
            $previous?->update(['state' => 'superseded', 'closed_at' => $at, 'revision' => $previous->revision + 1]);
            $contract = ReenrolmentContract::create(['session_year_id' => $year->id, 'version' => $version, 'title' => $data['title'], 'body' => $data['body'],
                'text_hash' => self::textHash($data['title'], $data['body']), 'acceptance_rule' => $data['acceptance_rule'], 'respond_by' => $data['respond_by'],
                'state' => 'open', 'revision' => 1, 'published_by' => $actor->id, 'published_at' => $at]);
            Audit::record('reenrolment.contract_published', $contract, [], ['version' => $version, 'text_hash' => $contract->text_hash, 'rule' => $contract->acceptance_rule, 'superseded' => $previous?->id]);

            return ['id' => (int) $contract->id, 'version' => $version, 'text_hash' => $contract->text_hash, 'revision' => 1];
        });
    }

    public function withdraw(User $actor, int $id, array $input): array
    {
        $this->auth()->staff($actor, self::MODULE, self::PERMISSION, true);
        $data = Validator::make($input, self::rules('withdraw'))->validate() + ['contract_id' => $id];

        return app(BusinessOperations::class)->once($actor, 'reenrolment.withdraw', $data, fn () => $this->auth()->staff($actor, self::MODULE, self::PERMISSION, true), function () use ($id, $data): array {
            $contract = ReenrolmentContract::lockForUpdate()->findOrFail($id);
            abort_unless($contract->state === 'open' && $contract->revision === (int) $data['revision'], 409, 'This contract changed. Refresh before withdrawing it.');
            $contract->update(['state' => 'withdrawn', 'closed_at' => now('UTC'), 'revision' => $contract->revision + 1]);
            Audit::record('reenrolment.contract_withdrawn', $contract, [], ['version' => $contract->version]);

            return ['id' => $id, 'state' => 'withdrawn', 'revision' => $contract->revision];
        });
    }

    /** @return list<int> learners enrolled in the current school year today */
    private function audience(): array
    {
        return Enrolment::where('session_year_id', $this->currentYear()->id)->onDate(app(AcademicAuthority::class)->today())
            ->whereIn('learner_id', Students::whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'))->select('id'))
            ->distinct()->orderBy('learner_id')->pluck('learner_id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return list<int> the fee-responsible guardians' user ids for this child and contract */
    public function feeResponsible(int $learnerId, ReenrolmentContract $contract, bool $lock = false): array
    {
        foreach ([(int) $contract->session_year_id, (int) $this->currentYear()->id] as $year) {
            $accounts = FeeAccountLearner::where('learner_id', $learnerId)->where('session_year_id', $year);
            $accounts = ($lock ? $accounts->lockForUpdate() : $accounts)->pluck('fee_account_id');
            if ($accounts->isEmpty()) {
                continue;
            }
            $holders = FeeAccountHolder::whereIn('fee_account_id', $accounts)->where('session_year_id', $year);
            $ids = ($lock ? $holders->lockForUpdate() : $holders)->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
            if ($ids !== []) {
                return $ids;
            }
        }
        $links = GuardianLink::where('learner_id', $learnerId)->where('status', 'active')->where('can_see_fees', true);

        return ($lock ? $links->lockForUpdate() : $links)->pluck('guardian_user_id')->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
    }

    /** @param list<int> $accepted */
    private function satisfied(ReenrolmentContract $contract, array $accepted, array $payers): bool
    {
        return match ($contract->acceptance_rule) {
            'any_guardian' => $accepted !== [],
            'fee_payer' => array_intersect($accepted, $payers) !== [],
            'all_fee_payers' => $payers !== [] && array_diff($payers, $accepted) === [],
            default => false,
        };
    }

    /** The open contracts this guardian can see for the child, with where acceptance stands. */
    public function forGuardian(User $actor, int $learnerId): array
    {
        $fresh = $this->auth()->guardian($actor, self::MODULE, $learnerId);
        $items = [];
        foreach (ReenrolmentContract::where('state', 'open')->orderBy('session_year_id')->get() as $contract) {
            $payers = $this->feeResponsible($learnerId, $contract);
            $accepted = ReenrolmentAcceptance::where('contract_id', $contract->id)->where('learner_id', $learnerId)->pluck('guardian_user_id')->map(fn ($id) => (int) $id)->all();
            $mine = ReenrolmentAcceptance::where('contract_id', $contract->id)->where('learner_id', $learnerId)->where('guardian_user_id', $fresh->id)->first();
            $feeResponsible = in_array((int) $fresh->id, $payers, true);
            $waiting = $contract->acceptance_rule !== 'any_guardian' && ! $feeResponsible && array_intersect($accepted, $payers) === [];
            $items[] = ['id' => (int) $contract->id, 'session_year' => (string) SessionYear::whereKey($contract->session_year_id)->value('name'), 'version' => $contract->version,
                'title' => $contract->title, 'body' => $contract->body, 'text_hash' => $contract->text_hash, 'acceptance_rule' => $contract->acceptance_rule,
                'respond_by' => $contract->respond_by->format('Y-m-d'),
                'status' => ReenrolmentConfirmation::where('contract_id', $contract->id)->where('learner_id', $learnerId)->exists() ? 'confirmed' : 'awaiting',
                'you_are_fee_responsible' => $feeResponsible, 'you_accepted_at' => $mine?->accepted_at?->toIso8601String(),
                'can_accept' => $mine === null && ! $waiting, 'waiting_for_fee_payer' => $mine === null && $waiting];
        }
        $this->auth()->guardian($actor, self::MODULE, $learnerId);

        return ['learner_id' => $learnerId, 'contracts' => $items];
    }

    public function accept(User $actor, int $learnerId, array $input, string $ip): array
    {
        $this->auth()->guardian($actor, self::MODULE, $learnerId, true);
        $data = Validator::make($input, self::rules('accept'))->validate();
        $data['contract_id'] = (int) $data['contract_id'];
        $data['version'] = (int) $data['version'];
        abort_unless(filter_var($ip, FILTER_VALIDATE_IP) !== false, 422);
        $bound = ['learner_id' => $learnerId] + $data;

        return app(BusinessOperations::class)->once($actor, 'reenrolment.accept', $bound, fn () => $this->auth()->guardian($actor, self::MODULE, $learnerId, true), function () use ($actor, $learnerId, $data, $ip): array {
            $fresh = $this->auth()->guardian($actor, self::MODULE, $learnerId, true);
            $contract = ReenrolmentContract::lockForUpdate()->findOrFail($data['contract_id']);
            abort_unless($contract->state === 'open', 409, 'This contract is no longer open. Read the current contract.');
            abort_unless($contract->version === $data['version'] && hash_equals($contract->text_hash, $data['text_hash'])
                && hash_equals($contract->text_hash, self::textHash($contract->title, $contract->body)), 409, 'Read the exact current contract before accepting.');
            abort_unless(mb_strtolower(trim($fresh->full_name)) === mb_strtolower(trim($data['signed_name'])), 422, 'Sign using the name on your guardian account.');
            $existing = ReenrolmentAcceptance::where('contract_id', $contract->id)->where('learner_id', $learnerId)->where('guardian_user_id', $fresh->id)->lockForUpdate()->first();
            $payers = $this->feeResponsible($learnerId, $contract, true);
            $accepted = ReenrolmentAcceptance::where('contract_id', $contract->id)->where('learner_id', $learnerId)->lockForUpdate()->pluck('guardian_user_id')->map(fn ($id) => (int) $id)->all();
            $feeResponsible = in_array((int) $fresh->id, $payers, true);
            if ($existing === null) {
                abort_if($contract->acceptance_rule !== 'any_guardian' && ! $feeResponsible && array_intersect($accepted, $payers) === [], 409,
                    'The guardian responsible for school fees needs to accept first.');
                $existing = ReenrolmentAcceptance::create(['contract_id' => $contract->id, 'learner_id' => $learnerId, 'guardian_user_id' => $fresh->id,
                    'version' => $contract->version, 'text_hash' => $contract->text_hash, 'fee_responsible' => $feeResponsible,
                    'signed_name' => trim($data['signed_name']), 'ip_address' => $ip, 'accepted_at' => now('UTC')]);
                $accepted[] = (int) $fresh->id;
                Audit::record('reenrolment.accepted', $existing, [], ['contract_id' => $contract->id, 'version' => $contract->version, 'fee_responsible' => $feeResponsible]);
            }
            $confirmed = ReenrolmentConfirmation::where('contract_id', $contract->id)->where('learner_id', $learnerId)->lockForUpdate()->exists();
            if (! $confirmed && $this->satisfied($contract, $accepted, $payers)) {
                $confirmation = ReenrolmentConfirmation::create(['contract_id' => $contract->id, 'learner_id' => $learnerId, 'session_year_id' => $contract->session_year_id, 'confirmed_at' => now('UTC')]);
                Audit::record('reenrolment.confirmed', $confirmation, [], ['contract_id' => $contract->id, 'learner_id' => $learnerId]);
                $confirmed = true;
            }

            return ['id' => (int) $existing->id, 'contract_id' => (int) $contract->id, 'learner_id' => $learnerId, 'version' => $contract->version,
                'accepted_at' => $existing->accepted_at->toIso8601String(), 'status' => $confirmed ? 'confirmed' : 'awaiting'];
        });
    }

    /** Staff view: who has not yet re-enrolled for this contract, and whom the school waits for. */
    public function outstanding(User $actor, int $id, int $page = 1): array
    {
        $this->auth()->staff($actor, self::MODULE, self::PERMISSION);
        $contract = ReenrolmentContract::findOrFail($id);
        $confirmed = ReenrolmentConfirmation::where('contract_id', $contract->id)->pluck('learner_id')->map(fn ($v) => (int) $v)->flip();
        $audience = $this->audience();
        $waiting = array_values(array_filter($audience, fn (int $learner) => ! $confirmed->has($learner)));
        $slice = array_slice($waiting, ($page - 1) * 100, 100);
        $items = [];
        foreach ($slice as $learnerId) {
            $payers = $this->feeResponsible($learnerId, $contract);
            $accepted = ReenrolmentAcceptance::where('contract_id', $contract->id)->where('learner_id', $learnerId)->pluck('guardian_user_id')->map(fn ($v) => (int) $v)->all();
            $awaited = $contract->acceptance_rule === 'any_guardian' && $payers === [] ? [] : array_values(array_diff($payers, $accepted));
            $items[] = ['learner_id' => $learnerId, 'name' => BusinessAuthority::learnerName($learnerId),
                'accepted_count' => count($accepted), 'fee_responsible_count' => count($payers),
                'awaiting' => User::whereIn('id', $awaited)->orderBy('id')->get(['id', 'first_name', 'last_name'])->map(fn (User $u) => ['user_id' => (int) $u->id, 'name' => $u->full_name])->values()->all()];
        }
        $this->auth()->staff($actor, self::MODULE, self::PERMISSION);

        return ['contract_id' => (int) $contract->id, 'version' => $contract->version, 'state' => $contract->state, 'revision' => $contract->revision,
            'audience' => count($audience), 'confirmed' => count($audience) - count($waiting), 'outstanding' => count($waiting), 'page' => $page, 'items' => $items];
    }

    /**
     * Reminders through the school's notifications. Fee-responsible
     * guardians are asked first; other approving guardians are asked only
     * under the any_guardian rule when no fee-responsible guardian can act.
     * One generic notice per guardian, contract and day: no child's name.
     *
     * Only outstanding learners are considered, and a request sends at most
     * reminder_batch notices. Guardians already reminded today are skipped,
     * so the next request moves on to the learners after this batch; `more`
     * says whether anyone is still waiting for today's reminder.
     */
    public function remind(User $actor, int $id, array $input): array
    {
        $this->auth()->staff($actor, self::MODULE, self::PERMISSION, true);
        $data = Validator::make($input, self::rules('remind'))->validate() + ['contract_id' => $id];

        return app(BusinessOperations::class)->once($actor, 'reenrolment.remind', $data, fn () => $this->auth()->staff($actor, self::MODULE, self::PERMISSION, true), function () use ($id): array {
            $contract = ReenrolmentContract::lockForUpdate()->findOrFail($id);
            abort_unless($contract->state === 'open', 409, 'Only an open contract can be sent as a reminder.');
            $confirmed = ReenrolmentConfirmation::where('contract_id', $contract->id)->pluck('learner_id')->map(fn ($v) => (int) $v)->flip();
            $outstanding = array_values(array_filter($this->audience(), fn (int $learner) => ! $confirmed->has($learner)));
            $cap = max(1, (int) config('school_business.reminder_batch', 500));
            $day = app(AcademicAuthority::class)->today();
            $done = [];
            $sent = 0;
            $learners = 0;
            $more = false;
            foreach ($outstanding as $learnerId) {
                $due = [];
                foreach ($this->asked($contract, $learnerId) as $userId) {
                    if (! isset($done[$userId]) && ! Notification::withTrashed()->where('source_key', $this->noticeKey($contract, $userId, $day))->exists()) {
                        $due[] = $userId;
                    }
                }
                $notified = false;
                foreach ($due as $userId) {
                    if ($sent >= $cap) {
                        $more = true;
                        break 2;
                    }
                    $notice = app(Notifier::class)->makeNotice(self::NOTICE, __('Re-enrolment for next year'), __('Open Re-enrolment to read and accept the contract for next year.'), self::LINK);
                    $notice->source_key = $this->noticeKey($contract, $userId, $day);
                    $notice->save();
                    app(Notifier::class)->assign($notice, [$userId]);
                    $done[$userId] = true;
                    $notified = true;
                    $sent++;
                }
                $learners += $notified ? 1 : 0;
            }
            Audit::record('reenrolment.reminded', $contract, [], ['notices' => $sent, 'learners' => $learners, 'more' => $more]);

            return ['contract_id' => (int) $contract->id, 'learners' => $learners, 'notices' => $sent, 'more' => $more];
        });
    }

    private function noticeKey(ReenrolmentContract $contract, int $userId, string $day): string
    {
        return 'reenrolment:'.$contract->id.':'.$userId.':'.$day;
    }

    /** @return list<int> the guardians a reminder for this child goes to */
    private function asked(ReenrolmentContract $contract, int $learnerId): array
    {
        $learner = Students::findOrFail($learnerId);
        $accepted = ReenrolmentAcceptance::where('contract_id', $contract->id)->where('learner_id', $learnerId)->pluck('guardian_user_id')->map(fn ($v) => (int) $v)->all();
        $asked = [];
        foreach (array_diff($this->feeResponsible($learnerId, $contract), $accepted) as $userId) {
            $person = User::find($userId);
            if ($person !== null && $this->auth()->approving($person, $learner)) {
                $asked[] = (int) $userId;
            }
        }
        if ($asked === [] && $contract->acceptance_rule === 'any_guardian') {
            foreach (app(GuardianService::class)->guardianUserIds($learner) as $userId) {
                $person = User::find($userId);
                if ($person !== null && ! in_array((int) $userId, $accepted, true) && $this->auth()->approving($person, $learner)) {
                    $asked[] = (int) $userId;
                }
            }
        }

        return $asked;
    }

    /**
     * The read the year-end rollover uses: learner id => 'confirmed' or
     * 'awaiting' for the open contract into $to; empty when none is open or
     * the module is off. It never writes and never changes a promotion.
     *
     * @param  list<int>  $learnerIds
     * @return array<int, string>
     */
    public function statuses(SessionYear $to, array $learnerIds): array
    {
        if ($learnerIds === [] || ! app(ModuleGate::class)->allows(self::MODULE)) {
            return [];
        }
        $contract = ReenrolmentContract::where('session_year_id', $to->id)->where('state', 'open')->first();
        if ($contract === null) {
            return [];
        }
        $confirmed = ReenrolmentConfirmation::where('contract_id', $contract->id)->whereIn('learner_id', $learnerIds)->pluck('learner_id')->map(fn ($v) => (int) $v)->flip();
        $result = [];
        foreach ($learnerIds as $id) {
            $result[(int) $id] = $confirmed->has((int) $id) ? 'confirmed' : 'awaiting';
        }

        return $result;
    }

    /** Staff list of contracts with their counts. */
    public function index(User $actor): array
    {
        $this->auth()->staff($actor, self::MODULE, self::PERMISSION);
        $years = SessionYear::pluck('name', 'id');

        return ['items' => ReenrolmentContract::orderByDesc('session_year_id')->orderByDesc('version')->limit(100)->get()->map(fn (ReenrolmentContract $c) => [
            'id' => (int) $c->id, 'session_year_id' => $c->session_year_id, 'session_year' => (string) ($years[$c->session_year_id] ?? ''), 'version' => $c->version,
            'title' => $c->title, 'text_hash' => $c->text_hash, 'acceptance_rule' => $c->acceptance_rule, 'respond_by' => $c->respond_by->format('Y-m-d'),
            'state' => $c->state, 'revision' => $c->revision, 'published_at' => $c->published_at->toIso8601String(),
            'confirmed' => ReenrolmentConfirmation::where('contract_id', $c->id)->count(),
        ])->values()->all()];
    }
}
