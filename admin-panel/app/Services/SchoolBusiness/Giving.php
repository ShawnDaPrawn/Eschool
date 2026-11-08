<?php

declare(strict_types=1);

namespace App\Services\SchoolBusiness;

use App\Models\Platform\SchoolMembership;
use App\Models\SchoolBusiness\GivingCampaign;
use App\Models\SchoolBusiness\GivingDonation;
use App\Models\SchoolBusiness\GivingPledge;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Money\ExternalReceipts;
use App\Services\Money\FeePayments;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Alumni giving on the money kernel (Action Plan 5.10).
 *
 * A campaign owns a giving income account in the ledger; an EFT donation
 * the school has seen in its bank posts debit giving bank, credit the
 * campaign, in integer cents, with a numbered receipt. The bank reference
 * is registered with every other outside receipt, so one bank line can
 * never be counted twice (as fees and as a gift). Pledges are promises with
 * their own acknowledgement number; they post nothing.
 *
 * Donor names and email addresses are encrypted and readable only with the
 * giving_donors ability (giving-donors-manage). Campaign managers see
 * totals; the school may publish totals to its own members.
 *
 * There is no online collection: donations are provider 'manual',
 * collected by the school. A future gateway adds its own provider through
 * the existing clearing/due-to-school seam, never by reusing this path.
 */
class Giving
{
    public const MODULE = 'giving';

    public const CAMPAIGNS = 'giving-campaigns-manage';

    public const DONORS = 'giving-donors-manage';

    public static function rules(string $action): array
    {
        $donor = ['donor_name' => ['required', 'string', 'min:2', 'max:160'], 'donor_email' => ['nullable', 'email:rfc', 'max:191']];

        return match ($action) {
            'campaign' => ['operation_id' => ['required', 'uuid'], 'name' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:5000'],
                'currency' => ['required', 'regex:/\A[A-Z]{3}\z/D'], 'goal_cents' => ['nullable', 'integer', 'between:1,100000000000'],
                'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'public_totals' => ['required', 'boolean']],
            'close' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']],
            'pledge' => ['operation_id' => ['required', 'uuid'], 'amount_cents' => ['required', 'integer', 'between:1,100000000000'], 'pledged_on' => ['required', 'date_format:Y-m-d']] + $donor,
            'donation' => ['operation_id' => ['required', 'uuid'], 'amount_cents' => ['required', 'integer', 'between:1,100000000000'], 'received_on' => ['required', 'date_format:Y-m-d'],
                'method' => ['required', 'in:eft'], 'source_reference' => ['required', 'string', 'max:96', 'regex:/\A[A-Z0-9][A-Z0-9 ._:\\/\-]{0,95}\z/D'],
                'pledge_id' => ['nullable', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']] + $donor,
            default => throw new \InvalidArgumentException('Unknown action.'),
        };
    }

    private function auth(): BusinessAuthority
    {
        return app(BusinessAuthority::class);
    }

    public function create(User $actor, array $input): array
    {
        $this->auth()->staff($actor, self::MODULE, self::CAMPAIGNS, true);
        $data = Validator::make($input, self::rules('campaign'))->validate();
        $data['goal_cents'] = isset($data['goal_cents']) ? (int) $data['goal_cents'] : null;
        $data['public_totals'] = (bool) $data['public_totals'];

        return app(BusinessOperations::class)->once($actor, 'giving.campaign', $data, fn () => $this->auth()->staff($actor, self::MODULE, self::CAMPAIGNS, true), function () use ($actor, $data): array {
            // The campaign's own income account; totals are its ledger balance.
            $account = app(LedgerService::class)->openAccount(AccountType::GivingIncome, 'giving:campaign:'.Str::uuid(), $data['currency']);
            $campaign = GivingCampaign::create(['name' => $data['name'], 'description' => $data['description'] ?? null, 'currency' => $data['currency'], 'goal_cents' => $data['goal_cents'],
                'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'], 'public_totals' => $data['public_totals'], 'state' => 'open', 'income_account_id' => $account, 'revision' => 1, 'created_by' => $actor->id]);
            Audit::record('giving.campaign_created', $campaign, [], ['currency' => $campaign->currency, 'goal_cents' => $campaign->goal_cents]);

            return ['id' => (int) $campaign->id, 'state' => 'open', 'revision' => $campaign->revision];
        });
    }

    public function close(User $actor, int $id, array $input): array
    {
        $this->auth()->staff($actor, self::MODULE, self::CAMPAIGNS, true);
        $data = Validator::make($input, self::rules('close'))->validate() + ['campaign_id' => $id];

        return app(BusinessOperations::class)->once($actor, 'giving.close', $data, fn () => $this->auth()->staff($actor, self::MODULE, self::CAMPAIGNS, true), function () use ($id, $data): array {
            $campaign = GivingCampaign::lockForUpdate()->findOrFail($id);
            abort_unless($campaign->state === 'open' && $campaign->revision === (int) $data['revision'], 409, 'This campaign changed. Refresh it first.');
            $campaign->update(['state' => 'closed', 'revision' => $campaign->revision + 1]);
            Audit::record('giving.campaign_closed', $campaign, [], ['revision' => $campaign->revision]);

            return ['id' => $id, 'state' => 'closed', 'revision' => $campaign->revision];
        });
    }

    /** Totals from the ledger, never from a cached sum. */
    private function totals(GivingCampaign $campaign): array
    {
        return ['raised_cents' => app(LedgerService::class)->balance($campaign->income_account_id)->cents,
            'pledged_cents' => (int) GivingPledge::where('campaign_id', $campaign->id)->sum('amount_cents'),
            'donations' => GivingDonation::where('campaign_id', $campaign->id)->count()];
    }

    private function describe(GivingCampaign $campaign): array
    {
        return ['id' => (int) $campaign->id, 'name' => $campaign->name, 'description' => $campaign->description, 'currency' => $campaign->currency,
            'goal_cents' => $campaign->goal_cents, 'starts_on' => $campaign->starts_on->format('Y-m-d'), 'ends_on' => $campaign->ends_on->format('Y-m-d'),
            'public_totals' => $campaign->public_totals, 'state' => $campaign->state, 'revision' => $campaign->revision];
    }

    /** Campaign managers or donor recorders: campaigns and totals, no donors. */
    public function index(User $actor): array
    {
        $fresh = $this->auth()->base($actor, self::MODULE);
        abort_unless($this->auth()->holds($fresh, self::CAMPAIGNS) || $this->auth()->holds($fresh, self::DONORS), 403);

        return ['items' => GivingCampaign::orderByDesc('id')->limit(100)->get()->map(fn (GivingCampaign $c) => $this->describe($c) + $this->totals($c))->values()->all()];
    }

    /** What the school chose to share with its own community: totals only. */
    public function summary(User $actor, int $id): array
    {
        $fresh = $this->auth()->base($actor, self::MODULE);
        abort_unless(SchoolMembership::active()->where('school_id', $this->auth()->context())->where('user_id', $fresh->id)->exists(), 403);
        $campaign = GivingCampaign::findOrFail($id);
        $manager = $this->auth()->holds($fresh, self::CAMPAIGNS) || $this->auth()->holds($fresh, self::DONORS);
        abort_unless($campaign->public_totals || $manager, 404);
        $totals = $this->totals($campaign);

        return ['id' => (int) $campaign->id, 'name' => $campaign->name, 'description' => $campaign->description, 'currency' => $campaign->currency,
            'goal_cents' => $campaign->goal_cents, 'state' => $campaign->state, 'raised_cents' => $totals['raised_cents'], 'donations' => $totals['donations']];
    }

    private function nextNumber(string $table, string $column): int
    {
        return (int) DB::table($table)->where('school_id', app(CurrentSchool::class)->id())->lockForUpdate()->max($column) + 1;
    }

    private function openCampaign(int $id): GivingCampaign
    {
        $campaign = GivingCampaign::lockForUpdate()->findOrFail($id);
        abort_unless($campaign->state === 'open', 409, 'This campaign is closed.');

        return $campaign;
    }

    public function pledge(User $actor, int $campaignId, array $input): array
    {
        $this->auth()->staff($actor, self::MODULE, self::DONORS, true);
        $data = Validator::make($input, self::rules('pledge'))->validate() + ['campaign_id' => $campaignId];
        $data['amount_cents'] = (int) $data['amount_cents'];

        return app(BusinessOperations::class)->once($actor, 'giving.pledge', $data, fn () => $this->auth()->staff($actor, self::MODULE, self::DONORS, true), function () use ($actor, $campaignId, $data): array {
            $campaign = $this->openCampaign($campaignId);
            $pledge = GivingPledge::create(['campaign_id' => $campaign->id, 'reference_number' => $this->nextNumber('giving_pledges', 'reference_number'),
                'donor_name' => trim($data['donor_name']), 'donor_email' => $data['donor_email'] ?? null, 'amount_cents' => $data['amount_cents'], 'currency' => $campaign->currency,
                'pledged_on' => $data['pledged_on'], 'recorded_by' => $actor->id, 'created_at' => now('UTC')]);
            Audit::record('giving.pledge_recorded', $pledge, [], ['campaign_id' => $campaign->id, 'amount_cents' => $pledge->amount_cents]);

            return ['id' => (int) $pledge->id, 'reference' => self::reference('P', $pledge->reference_number)];
        });
    }

    /** A confirmed EFT the school has seen in its bank account. */
    public function donate(User $actor, int $campaignId, array $input): array
    {
        $this->auth()->staff($actor, self::MODULE, self::DONORS, true);
        if (is_string($input['source_reference'] ?? null)) {
            $input['source_reference'] = FeePayments::reference($input['source_reference']);
        }
        $data = Validator::make($input, self::rules('donation'))->validate() + ['campaign_id' => $campaignId];
        $data['amount_cents'] = (int) $data['amount_cents'];
        $data['pledge_id'] = isset($data['pledge_id']) ? (int) $data['pledge_id'] : null;

        return app(BusinessOperations::class)->once($actor, 'giving.donation', $data, fn () => $this->auth()->staff($actor, self::MODULE, self::DONORS, true), function () use ($actor, $campaignId, $data): array {
            $campaign = $this->openCampaign($campaignId);
            abort_unless($data['received_on'] <= app(AcademicAuthority::class)->today(), 422, 'Only a bank receipt dated today or earlier may be recorded.');
            if ($data['pledge_id'] !== null) {
                GivingPledge::where('campaign_id', $campaign->id)->lockForUpdate()->findOrFail($data['pledge_id']);
            }
            app(ExternalReceipts::class)->assertAvailable($data['method'], $data['source_reference']);
            $ledger = app(LedgerService::class);
            $bank = $ledger->openAccount(AccountType::GivingBank, 'giving:eft:'.$campaign->currency, $campaign->currency);
            $source = 'giving-receipt:eft:'.hash('sha256', $data['source_reference']);
            $amount = Money::ofCents($data['amount_cents'], $campaign->currency);
            $entry = $ledger->post($source, $source, 'Verified alumni giving receipt', [Posting::debit($bank, $amount), Posting::credit($campaign->income_account_id, $amount)]);
            $donation = GivingDonation::create(['campaign_id' => $campaign->id, 'pledge_id' => $data['pledge_id'], 'receipt_number' => $this->nextNumber('giving_donations', 'receipt_number'),
                'donor_name' => trim($data['donor_name']), 'donor_email' => $data['donor_email'] ?? null, 'method' => 'eft', 'provider' => 'manual', 'collected_by' => 'school',
                'source_reference' => $data['source_reference'], 'amount_cents' => $data['amount_cents'], 'currency' => $campaign->currency, 'received_on' => $data['received_on'],
                'asset_account_id' => $bank, 'income_account_id' => $campaign->income_account_id, 'ledger_entry_id' => $entry, 'recorded_by' => $actor->id, 'created_at' => now('UTC')]);
            Audit::record('giving.donation_recorded', $donation, [], ['campaign_id' => $campaign->id, 'amount_cents' => $donation->amount_cents, 'ledger_entry_id' => $entry]);

            return ['id' => (int) $donation->id, 'receipt' => self::reference('D', $donation->receipt_number), 'ledger_entry_id' => $entry];
        });
    }

    /** Finance only: who gave and pledged. */
    public function donors(User $actor, int $campaignId): array
    {
        $this->auth()->staff($actor, self::MODULE, self::DONORS);
        $campaign = GivingCampaign::findOrFail($campaignId);
        $donations = GivingDonation::where('campaign_id', $campaign->id)->orderBy('id')->limit(1000)->get()->map(fn (GivingDonation $d) => $this->donation($d))->values()->all();
        $pledges = GivingPledge::where('campaign_id', $campaign->id)->orderBy('id')->limit(1000)->get()->map(fn (GivingPledge $p) => [
            'id' => (int) $p->id, 'reference' => self::reference('P', $p->reference_number), 'donor_name' => $p->donor_name, 'donor_email' => $p->donor_email,
            'amount_cents' => $p->amount_cents, 'currency' => $p->currency, 'pledged_on' => $p->pledged_on->format('Y-m-d'),
            'received_cents' => (int) GivingDonation::where('pledge_id', $p->id)->sum('amount_cents')])->values()->all();
        $this->auth()->staff($actor, self::MODULE, self::DONORS);

        return ['campaign' => $this->describe($campaign) + $this->totals($campaign), 'donations' => $donations, 'pledges' => $pledges];
    }

    private function donation(GivingDonation $d): array
    {
        return ['id' => (int) $d->id, 'receipt' => self::reference('D', $d->receipt_number), 'donor_name' => $d->donor_name, 'donor_email' => $d->donor_email,
            'amount_cents' => $d->amount_cents, 'currency' => $d->currency, 'method' => $d->method, 'source_reference' => $d->source_reference,
            'received_on' => $d->received_on->format('Y-m-d'), 'pledge_id' => $d->pledge_id, 'ledger_entry_id' => $d->ledger_entry_id];
    }

    /** The donation receipt for the donor, prepared by Finance. */
    public function receipt(User $actor, int $donationId): array
    {
        $this->auth()->staff($actor, self::MODULE, self::DONORS);
        $donation = GivingDonation::findOrFail($donationId);
        $campaign = GivingCampaign::findOrFail($donation->campaign_id);

        return $this->donation($donation) + ['campaign' => $campaign->name, 'school' => (string) app(CurrentSchool::class)->school()?->name, 'issued_at' => $donation->created_at->toIso8601String()];
    }

    public static function reference(string $prefix, int $number): string
    {
        return $prefix.'-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
