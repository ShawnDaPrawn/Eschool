<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\BillingOperation;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeItemConsent;
use App\Models\Money\FeeItemEligibilityRule;
use App\Models\Platform\GuardianLink;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class FeeItemConsents
{
    public function page(User $actor, int $accountId, int $page = 1, int $perPage = 20): array
    {
        abort_unless($page >= 1 && $page <= 1000000 && $perPage >= 1 && $perPage <= 50, 422);

        return DB::transaction(function () use ($actor, $accountId, $page, $perPage): array {
            $scope = app(ParentFeeItemScope::class)->account($actor, $accountId);
            $year = (int) $scope['year']->id;
            $latestIds = FeeItemConsent::where('actor_id', $actor->id)->where('fee_account_id', $accountId)->where('session_year_id', $year)->selectRaw('MAX(id) AS id')->groupBy('fee_item_id', 'scope', 'target_id')->pluck('id');
            $latest = FeeItemConsent::whereIn('id', $latestIds)->lockForUpdate()->get();
            $byTarget = [];
            foreach ($latest as $event) {
                $byTarget[$event->fee_item_id.':'.$event->scope.':'.$event->target_id] = $event;
            }
            $selectedIds = $latest->where('active', true)->pluck('fee_item_id')->all();
            $rules = FeeItemEligibilityRule::where('mode', 'opt_in')->lockForUpdate()->get()->keyBy('fee_item_id');
            $items = FeeItem::where(fn ($q) => $q->where(fn ($q) => $q->where('state', 'active')->whereIn('id', $rules->keys()))->orWhereIn('id', $selectedIds))->orderBy('id')->limit(201)->lockForUpdate()->get();
            abort_unless($items->count() <= 200, 422, 'This optional item catalogue supports up to 200 offered items per account.');
            $pairs = [];
            foreach ($items as $item) {
                if ($item->scope === 'family' && count($scope['visible']) !== count($scope['ids'])) {
                    continue;
                }
                $ids = $item->scope === 'family' ? $scope['ids'] : $scope['visible'];
                $base = $rules->has($item->id) && $item->state === 'active' ? app(FeeItemEligibility::class)->evaluate($item, $ids, $year, $scope['date']) : null;
                foreach ($item->scope === 'family' ? [null] : $scope['visible'] as $learner) {
                    $target = $learner ?? $accountId;
                    $prior = $byTarget[$item->id.':'.$item->scope.':'.$target] ?? null;
                    $eligible = $base !== null && ($learner === null ? ($base['issues'] === [] && $base['matched_learner_ids'] !== []) : in_array($learner, $base['matched_learner_ids'], true));
                    if (! $eligible && ! $prior?->active) {
                        continue;
                    }
                    if ($item->currency !== $scope['account']->currency && ! $prior?->active) {
                        continue;
                    }
                    $pairs[] = [$item, $learner, $prior];
                    abort_unless(count($pairs) <= 20000, 422, 'This account has too many optional item targets to display.');
                }
            }
            $rows = [];
            foreach (array_slice($pairs, ($page - 1) * $perPage, $perPage) as [$item,$learner,$prior]) {
                $rows[] = $this->quote($actor, $scope, $item, $learner, $prior);
            }

            return ['school_id' => app(CurrentSchool::class)->id(), 'holder_user_id' => (int) $actor->id, 'fee_account_id' => $accountId, 'session_year_id' => $year, 'writable' => app(ModuleGate::class)->writable('fees'), 'items' => $rows, 'current_page' => $page, 'per_page' => $perPage, 'total' => count($pairs)];
        });
    }

    /** Fresh authority is checked before both permanent replay and a new event. */
    public function change(User $actor, int $accountId, int $itemId, array $input): FeeItemConsent
    {
        $data = Validator::make($input, self::rules())->validate();
        foreach (['session_year_id', 'revision'] as $key) {
            $data[$key] = (int) $data[$key];
        }
        $data['learner_id'] = isset($data['learner_id']) ? (int) $data['learner_id'] : null;
        $data['active'] = (bool) $data['active'];
        $data['fingerprint'] = $data['fingerprint'] ?? null;
        $data['fee_account_id'] = $accountId;
        $data['fee_item_id'] = $itemId;

        return DB::transaction(function () use ($actor, $accountId, $itemId, $data): FeeItemConsent {
            $scope = app(ParentFeeItemScope::class)->mutation($actor, $accountId, $itemId, $data);
            $hash = hash_hmac('sha256', json_encode(['parent_fee_item_consent', (int) $actor->id, $data], JSON_THROW_ON_ERROR), (string) config('app.key'));
            $operation = BillingOperation::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($operation !== null) {
                abort_unless((int) $operation->actor_id === (int) $actor->id && hash_equals($operation->request_hash, $hash), 409, 'This operation reference has a different change.');

                return FeeItemConsent::where('actor_id', $actor->id)->findOrFail($operation->receipt['id']);
            }
            $item = $scope['item'];
            $target = $scope['target'];
            $prior = $this->latest($item, $scope['account'], (int) $scope['year']->id, $target['target_id'], (int) $actor->id);
            abort_unless((int) ($prior->revision ?? 0) === $data['revision'], 409, 'Your approval changed. Refresh the optional item before continuing.');
            if ($data['active']) {
                $quote = $this->quote($actor, $scope, $item, $data['learner_id'], $prior);
                abort_unless($quote['available'] && $quote['fingerprint'] !== null && is_string($data['fingerprint']) && hash_equals($quote['fingerprint'], $data['fingerprint']), 409, 'The optional item changed. Review its current price and allocation.');
                $terms = $this->terms($item, $scope['account'], (int) $scope['year']->id, $target['ids'], $scope['date']);
                $own = collect($terms['holders'])->firstWhere('user_id', (int) $actor->id);
                abort_unless($own !== null, 404);
                $snapshot = ['item_name' => $item->name, 'scope' => $item->scope, 'billing' => $terms['billing'], 'learner_ids' => $target['ids'], 'currency' => $item->currency, 'price_cents' => $terms['tax']['gross_cents'], 'share_cents' => $own['amount_cents'], 'vat_cents' => $own['vat_cents'], 'item_revision' => $item->revision, 'account_revision' => $scope['account']->revision, 'rule_revision' => $terms['rule_revision']];
                $termsHash = $terms['hash'];
            } else {
                abort_unless($prior !== null && $prior->active, 404);
                $snapshot = $prior->snapshot;
                $termsHash = $prior->terms_hash;
            }
            $event = FeeItemConsent::create(['fee_item_id' => $itemId, 'fee_account_id' => $accountId, 'session_year_id' => (int) $scope['year']->id, 'scope' => $item->scope, 'learner_id' => $data['learner_id'], 'target_id' => $target['target_id'], 'actor_id' => $actor->id, 'revision' => $data['revision'] + 1, 'active' => $data['active'], 'operation_id' => $data['operation_id'], 'terms_hash' => $termsHash, 'snapshot' => $snapshot, 'created_at' => now('UTC')]);
            app(ParentFeeItemScope::class)->mutation($actor, $accountId, $itemId, $data);
            BillingOperation::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'receipt' => ['id' => (int) $event->id, 'revision' => $event->revision]]);
            Audit::record('money.fee_item_consent_recorded', $event, [], ['active' => $event->active, 'revision' => $event->revision, 'fee_item_id' => $itemId]);

            return $event;
        }, 3);
    }

    public function authorizes(FeeItem $item, FeeAccount $account, int $year, array $ids, string $date): array
    {
        $terms = $this->terms($item, $account, $year, $ids, $date);
        $target = $item->scope === 'family' ? (int) $account->id : $ids[0];
        $events = [];
        foreach ($terms['holders'] as $holder) {
            $actor = $holder['user_id'];
            $event = $this->latest($item, $account, $year, $target, $actor);
            $approved = GuardianLink::where('guardian_user_id', $actor)->whereIn('learner_id', $ids)->where('status', 'active')->where('can_see_fees', true)->where('can_approve', true)->lockForUpdate()->get()->pluck('learner_id')->unique()->count() === count($ids);
            abort_unless($approved && $event !== null && $event->active && hash_equals($event->terms_hash, $terms['hash']), 422, 'Every current payer must approve the current optional item terms before charging.');
            $events[] = ['id' => (int) $event->id, 'revision' => $event->revision, 'actor_id' => $actor, 'terms_hash' => $event->terms_hash];
        }
        if ($terms['billing'] === 'once_per_year') {
            abort_if(FeeCharge::where('fee_item_id', $item->id)->where('session_year_id', $year)->where('scope', $item->scope)->where('target_id', $target)->lockForUpdate()->exists(), 409, 'This optional item has already been charged for this school year.');
        }

        return $events;
    }

    private function quote(User $actor, array $scope, FeeItem $item, ?int $learner, ?FeeItemConsent $prior): array
    {
        $target = app(ParentFeeItemScope::class)->target($scope, $item, $learner);
        $terms = null;
        try {
            $terms = $this->terms($item, $scope['account'], (int) $scope['year']->id, $target['ids'], $scope['date']);
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() !== 422) {
                throw $e;
            }
        }
        $own = $terms === null ? null : collect($terms['holders'])->firstWhere('user_id', (int) $actor->id);
        $revision = (int) ($prior->revision ?? 0);
        $current = $prior !== null && $prior->active && $terms !== null && hash_equals($prior->terms_hash, $terms['hash']);
        $fingerprint = $terms === null ? null : hash_hmac('sha256', json_encode([(int) $actor->id, (int) $scope['account']->id, $learner, $terms['hash'], $revision, $prior->active ?? false, $target['can_approve'], $scope['date']], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return ['fee_item_id' => (int) $item->id, 'name' => $item->name, 'scope' => $item->scope, 'billing' => $terms['billing'] ?? ($prior->snapshot['billing'] ?? FeeItemEligibilityRule::where('fee_item_id', $item->id)->value('opt_in_billing')), 'learner_id' => $learner, 'learner_name' => $learner === null ? null : ($target['learners'][0]['name'] ?? null), 'currency' => $item->currency, 'available' => $terms !== null, 'can_approve' => $target['can_approve'], 'price_cents' => $terms['tax']['gross_cents'] ?? null, 'share_cents' => $own['amount_cents'] ?? null, 'vat_cents' => $own['vat_cents'] ?? null, 'selected' => $prior->active ?? false, 'current_terms_approved' => $current, 'revision' => $revision, 'fingerprint' => $fingerprint];
    }

    private function terms(FeeItem $item, FeeAccount $account, int $year, array $ids, string $date): array
    {
        $rule = FeeItemEligibilityRule::where('fee_item_id', $item->id)->lockForUpdate()->first();
        abort_unless($item->state === 'active' && $item->currency === $account->currency && $rule !== null && $rule->mode === 'opt_in', 422, 'This optional item is not currently available.');
        $base = app(FeeItemEligibility::class)->evaluate($item, $ids, $year, $date);
        abort_unless($base !== null && $base['issues'] === [] && $base['matched_learner_ids'] !== [], 422, 'This optional item is not currently eligible.');
        $rows = FeeAccountHolder::where('fee_account_id', $account->id)->where('session_year_id', $year)->orderBy('user_id')->lockForUpdate()->get();
        $holders = app(FeeAccounts::class)->holders($rows->map(fn ($h) => ['user_id' => (int) $h->user_id, 'share_bp' => (int) $h->share_bp])->all(), $ids);
        $tax = app(FeeTaxes::class)->snapshot($item);
        $shares = BillingPreview::shares($tax['gross_cents'], $holders);
        $vat = FeeTaxAmounts::split($tax['vat_cents'], array_column($shares, 'amount_cents', 'user_id'));
        $terms = [];
        foreach ($shares as $share) {
            $terms[] = ['user_id' => $share['user_id'], 'share_bp' => $share['share_bp'], 'amount_cents' => $share['amount_cents'], 'vat_cents' => $vat[$share['user_id']]];
        }
        $data = ['item_id' => (int) $item->id, 'item_revision' => $item->revision, 'rule_revision' => $rule->revision, 'account_id' => (int) $account->id, 'account_revision' => $account->revision, 'year' => $year, 'scope' => $item->scope, 'billing' => $rule->opt_in_billing, 'learner_ids' => $ids, 'tax' => $tax, 'holders' => $terms];
        $material = $data;
        unset($material['item_revision'],$material['rule_revision'],$material['account_revision']);
        if ($material['tax']['tax_details'] !== null) {
            unset($material['tax']['tax_details']['revision']);
        }
        $conditions = $rule->applies_to;
        foreach ($conditions as &$condition) {
            usort($condition['values'], fn ($a, $b) => json_encode($a) <=> json_encode($b));
        }unset($condition);
        usort($conditions, fn ($a, $b) => json_encode($a) <=> json_encode($b));
        $material['item_name'] = $item->name;
        $material['rule'] = ['target_scope' => $rule->target_scope, 'target_id' => $rule->target_id, 'applies_to' => $conditions];

        return $data + ['hash' => hash('sha256', json_encode($material, JSON_THROW_ON_ERROR))];
    }

    private function latest(FeeItem $item, FeeAccount $account, int $year, int $target, int $actor): ?FeeItemConsent
    {
        return FeeItemConsent::where('fee_item_id', $item->id)->where('fee_account_id', $account->id)->where('session_year_id', $year)->where('scope', $item->scope)->where('target_id', $target)->where('actor_id', $actor)->orderByDesc('revision')->lockForUpdate()->first();
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'session_year_id' => ['required', 'integer', 'min:1'], 'learner_id' => ['nullable', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:0'], 'active' => ['required', 'boolean'], 'fingerprint' => ['nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'confirmed' => ['required', 'accepted']];
    }
}
