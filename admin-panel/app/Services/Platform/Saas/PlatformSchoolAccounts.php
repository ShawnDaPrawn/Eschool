<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\School;
use App\Models\Platform\SchoolAccount;
use App\Models\Platform\SchoolAccountEvent;
use App\Models\Platform\SchoolContact;
use App\Models\Platform\SchoolContract;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * HQ's own record of each customer school (Action Plan SA.4 and SA.5): profile,
 * lifecycle stage, contacts, contracts with the POPIA operator agreement, and
 * a timeline of notes and changes. Only platform staff reach it, through /saas.
 * It holds Navigate Ed's business contacts, never a school's learners or staff
 * records. Every change is audited against the school.
 * Specification: engineering/specs/platform/hq-school-accounts.md.
 */
class PlatformSchoolAccounts
{
    public const STAGES = ['lead', 'demo', 'contracted', 'onboarding', 'live', 'at_risk', 'churned'];

    /** A school set up before HQ recorded stages is treated as onboarding until someone says otherwise. */
    public const DEFAULT_STAGE = 'onboarding';

    public const TYPES = ['public', 'independent'];

    public const PHASES = ['early_childhood', 'primary', 'secondary', 'combined', 'special_needs'];

    public const CURRICULA = ['caps', 'ieb', 'cambridge', 'other'];

    public const PROVINCES = ['eastern_cape', 'free_state', 'gauteng', 'kwazulu_natal', 'limpopo', 'mpumalanga', 'north_west', 'northern_cape', 'western_cape'];

    public const CONTACT_ROLES = ['principal', 'deputy_principal', 'bursar', 'it', 'information_officer', 'other'];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** Everything HQ holds about one school. */
    public function show(int $schoolId): array
    {
        $this->school($schoolId);

        return $this->currentSchool->run($schoolId, function () {
            $account = SchoolAccount::query()->first();

            return [
                'account' => ($account?->only(['stage', 'school_type', 'phase', 'curriculum', 'province', 'district', 'emis_number', 'address', 'expected_learners', 'revision']))
                    ?? ['stage' => self::DEFAULT_STAGE, 'revision' => 0],
                'contacts' => SchoolContact::query()->orderBy('role')->orderBy('name')->get()->map(fn ($contact) => $contact->only(['id', 'role', 'name', 'email', 'phone']))->all(),
                'contracts' => SchoolContract::query()->orderByDesc('starts_on')->orderByDesc('id')->get()->map(fn ($contract) => [
                    'id' => (int) $contract->id, 'starts_on' => $contract->starts_on->toDateString(), 'ends_on' => $contract->ends_on?->toDateString(),
                    'renewal_on' => $contract->renewal_on?->toDateString(), 'contracted_learners' => $contract->contracted_learners,
                    'price_per_learner_cents' => $contract->price_per_learner_cents, 'signatory' => $contract->signatory,
                    'operator_agreement_version' => $contract->operator_agreement_version, 'operator_agreement_signed_on' => $contract->operator_agreement_signed_on->toDateString(),
                    'data_processing_signed_on' => $contract->data_processing_signed_on?->toDateString(), 'notes' => $contract->notes,
                ])->all(),
                'events' => SchoolAccountEvent::query()->orderByDesc('created_at')->orderByDesc('id')->limit(100)->get()
                    ->map(fn ($event) => ['kind' => $event->kind, 'body' => $event->body, 'at' => $event->created_at, 'actor' => $this->actorName($event->actor_id)])->all(),
            ];
        });
    }

    public static function accountRules(): array
    {
        return [
            'stage' => ['required', Rule::in(self::STAGES)], 'school_type' => ['nullable', Rule::in(self::TYPES)],
            'phase' => ['nullable', Rule::in(self::PHASES)], 'curriculum' => ['nullable', Rule::in(self::CURRICULA)],
            'province' => ['nullable', Rule::in(self::PROVINCES)], 'district' => ['nullable', 'string', 'max:100'],
            'emis_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9A-Za-z-]+$/'], 'address' => ['nullable', 'string', 'max:500'],
            'expected_learners' => ['nullable', 'integer', 'between:1,20000'], 'revision' => ['required', 'integer', 'min:0'],
        ];
    }

    public static function contactRules(): array
    {
        return [
            'role' => ['required', Rule::in(self::CONTACT_ROLES)], 'name' => ['required', 'string', 'max:191'],
            'email' => ['nullable', 'email', 'max:191'], 'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9 +()-]+$/'],
        ];
    }

    public static function contractRules(): array
    {
        $today = CarbonImmutable::now('Africa/Johannesburg')->toDateString();

        return [
            'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['nullable', 'date_format:Y-m-d', 'after:starts_on'],
            'renewal_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'contracted_learners' => ['required', 'integer', 'between:1,20000'],
            'price_per_learner' => ['required', 'regex:/^\d{1,5}(\.\d{1,2})?$/'],
            'signatory' => ['required', 'string', 'max:191'], 'operator_agreement_version' => ['required', 'string', 'max:32'],
            'operator_agreement_signed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'data_processing_signed_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today], 'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public static function noteRules(): array
    {
        return ['body' => ['required', 'string', 'min:3', 'max:1000']];
    }

    public function saveAccount(int $schoolId, array $input, User $actor): void
    {
        $data = Validator::make($input, self::accountRules())->validate();
        $this->write($schoolId, function () use ($data, $actor) {
            $account = SchoolAccount::query()->lockForUpdate()->first();
            abort_if(($account?->revision ?? 0) !== (int) $data['revision'], 409, __('This school record changed. Reload before saving.'));
            $before = $account?->getAttributes() ?? [];
            $was = $account?->stage ?? self::DEFAULT_STAGE;
            $account ??= new SchoolAccount;
            $account->fill(array_diff_key($data, ['revision' => true]));
            $account->revision = (int) $data['revision'] + 1;
            $account->save();
            Audit::record('platform_school_account_saved', $account, $before, $account->getAttributes());
            if ($was !== $account->stage) {
                $this->event('stage', __('Stage changed from :from to :to', ['from' => __('stage_'.$was), 'to' => __('stage_'.$account->stage)]), $actor);
            }
        });
    }

    public function saveContact(int $schoolId, ?int $contactId, array $input, User $actor): void
    {
        $data = Validator::make($input, self::contactRules())->validate();
        if (($data['email'] ?? null) === null && ($data['phone'] ?? null) === null) {
            throw ValidationException::withMessages(['email' => __('Give an email address or a phone number.')]);
        }
        $this->write($schoolId, function () use ($contactId, $data, $actor) {
            $contact = $contactId === null ? new SchoolContact : SchoolContact::query()->lockForUpdate()->findOrFail($contactId);
            $before = $contact->exists ? $contact->getAttributes() : [];
            $contact->fill($data)->save();
            Audit::record('platform_school_contact_saved', $contact, $before, $contact->getAttributes());
            if ($before === []) {
                $this->event('contact', __('Contact added: :name (:role)', ['name' => $contact->name, 'role' => __('contact_'.$contact->role)]), $actor);
            }
        });
    }

    public function removeContact(int $schoolId, int $contactId, User $actor): void
    {
        $this->write($schoolId, function () use ($contactId, $actor) {
            $contact = SchoolContact::query()->lockForUpdate()->findOrFail($contactId);
            $before = $contact->getAttributes();
            $contact->delete();
            Audit::record('platform_school_contact_removed', $contact, $before, []);
            $this->event('contact', __('Contact removed: :name (:role)', ['name' => $contact->name, 'role' => __('contact_'.$contact->role)]), $actor);
        });
    }

    /** Contracts are history: recorded once, never edited; a change is a new contract. */
    public function recordContract(int $schoolId, array $input, User $actor): void
    {
        $data = Validator::make($input, self::contractRules())->validate();
        // Rands typed as text become integer cents without floating point.
        [$rands, $cents] = array_pad(explode('.', $data['price_per_learner']), 2, '0');
        $data['price_per_learner_cents'] = (int) $rands * 100 + (int) str_pad($cents, 2, '0');
        unset($data['price_per_learner']);
        $this->write($schoolId, function () use ($data, $actor) {
            $contract = SchoolContract::query()->create($data);
            Audit::record('platform_school_contract_recorded', $contract, [], $contract->getAttributes());
            $this->event('contract', __('Contract recorded from :date for :learners learners; operator agreement :version signed :signed', [
                'date' => $contract->starts_on->toDateString(), 'learners' => $contract->contracted_learners,
                'version' => $contract->operator_agreement_version, 'signed' => $contract->operator_agreement_signed_on->toDateString()]), $actor);
        });
    }

    public function addNote(int $schoolId, array $input, User $actor): void
    {
        $data = Validator::make($input, self::noteRules())->validate();
        $this->write($schoolId, fn () => $this->event('note', $data['body'], $actor));
    }

    private function write(int $schoolId, callable $change): void
    {
        $this->school($schoolId);
        $this->currentSchool->run($schoolId, fn () => DB::transaction(function () use ($change) {
            $change();
        }, 3));
    }

    private function event(string $kind, string $body, User $actor): void
    {
        SchoolAccountEvent::query()->create(['kind' => $kind, 'body' => $body, 'actor_id' => $actor->id]);
    }

    private function school(int $schoolId): School
    {
        return School::query()->findOrFail($schoolId);
    }

    private function actorName(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }
        // Platform staff may belong to another school, or none, so look past this school's members.
        $user = User::acrossSchools()->withTrashed()->find($id);

        return $user === null ? null : trim($user->first_name.' '.$user->last_name);
    }
}
