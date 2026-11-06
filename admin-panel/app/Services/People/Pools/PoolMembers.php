<?php

declare(strict_types=1);

namespace App\Services\People\Pools;

use App\Models\Activities\ActivitySession;
use App\Models\People\Pools\PoolBooking;
use App\Models\People\Pools\PoolMember;
use App\Models\People\Pools\PoolVetting;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Inviting people into the school's pool, vetting them and withdrawing
 * (Action Plan 5.6). engineering/specs/community/people-pools.md.
 */
final class PoolMembers
{
    public function __construct(private readonly PoolAccess $access, private readonly PoolOperations $operations) {}

    public static function rules(string $action): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];

        return match ($action) {
            'invite' => $operation + [
                'kind' => ['required', Rule::in([PoolMember::ALUMNUS, PoolMember::VOLUNTEER])],
                'learner_id' => ['nullable', 'integer', 'min:1', 'required_if:kind,'.PoolMember::ALUMNUS, 'prohibited_unless:kind,'.PoolMember::ALUMNUS],
                'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email:rfc', 'max:191'], 'initial_password' => ['required', 'string', Password::defaults(), 'max:72'],
                'occupation' => ['nullable', 'string', 'max:191'], 'organisation' => ['nullable', 'string', 'max:191'],
                'offers_career_talk' => ['required', 'boolean'], 'offers_job_shadow' => ['required', 'boolean'],
            ],
            'vet' => $operation + [
                'revision' => ['required', 'integer', 'min:0'],
                'outcome' => ['required', Rule::in(['cleared', 'refused', 'revoked'])],
                'checks' => ['present', 'array', 'max:10'], 'checks.*' => ['string', 'distinct', Rule::in((array) config('people_pools.checks'))],
                'checked_on' => ['required', 'date_format:Y-m-d'],
                'expires_on' => ['nullable', 'date_format:Y-m-d', 'required_if:outcome,cleared', 'prohibited_unless:outcome,cleared', 'after:checked_on'],
                'note' => ['nullable', 'string', 'max:2000'],
            ],
            'list' => ['page' => ['sometimes', 'integer', 'min:1', 'max:10000']],
            default => [],
        };
    }

    /** @return array<string, mixed> */
    public function invite(User $actor, array $input): array
    {
        $this->access->manager($actor, true);
        $data = Validator::make($input, self::rules('invite'))->validate();
        $data['email'] = strtolower(trim($data['email']));
        foreach (['offers_career_talk', 'offers_job_shadow'] as $field) {
            $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
        }
        if (! $data['offers_career_talk'] && ! $data['offers_job_shadow']) {
            throw ValidationException::withMessages(['offers_career_talk' => __('pool_offer_needed')]);
        }
        $data['learner_id'] = isset($data['learner_id']) ? (int) $data['learner_id'] : null;
        // The request hash binds the password without keeping it.
        $fingerprint = $data;
        $fingerprint['initial_password'] = hash_hmac('sha256', $data['initial_password'], (string) config('app.key'));
        $receipt = $this->operations->once($actor, 'invite', $fingerprint, fn () => $this->access->manager($actor, true), function () use ($actor, $data): array {
            if ($data['kind'] === PoolMember::ALUMNUS) {
                $this->assertFormerLearner($data['learner_id']);
                abort_if(PoolMember::where('learner_id', $data['learner_id'])->lockForUpdate()->exists(), 422, __('pool_already_invited'));
            }
            abort_if(DB::table('users')->where('email', $data['email'])->lockForUpdate()->exists(), 422, __('pool_email_in_use'));
            $user = new User;
            $user->forceFill(['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $data['email'], 'password' => Hash::make($data['initial_password']), 'status' => 1, 'password_change_required' => true])->save();
            $member = PoolMember::create(['user_id' => $user->id, 'kind' => $data['kind'], 'learner_id' => $data['learner_id'], 'occupation' => $data['occupation'] ?? null, 'organisation' => $data['organisation'] ?? null,
                'offers_career_talk' => $data['offers_career_talk'], 'offers_job_shadow' => $data['offers_job_shadow'], 'consent_status' => PoolMember::INVITED, 'vetting_status' => PoolMember::PENDING, 'invited_by' => $actor->id]);
            Audit::record('people_pool.invited', $member, [], ['kind' => $member->kind, 'learner_id' => $member->learner_id, 'by' => $actor->id]);

            return ['id' => (int) $member->id];
        });

        return $this->detail($actor, $receipt['id']);
    }

    /**
     * A former learner is one of this school's leaver records (a Student
     * membership that went through the leaver state, reading or ended) and
     * an adult by the date of birth the school holds.
     */
    private function assertFormerLearner(int $learnerId): void
    {
        $learner = Students::withTrashed()->lockForUpdate()->find($learnerId);
        $leaver = $learner !== null && SchoolMembership::query()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $learner->user_id)->where('role', 'Student')
            ->whereNotNull('leaver_until')->whereIn('status', [SchoolMembership::LEAVER, SchoolMembership::ENDED])->exists();
        if (! $leaver) {
            throw ValidationException::withMessages(['learner_id' => __('pool_not_a_leaver')]);
        }
        $dob = User::acrossSchools()->whereKey($learner->user_id)->value('dob');
        $adult = $dob !== null && CarbonImmutable::parse((string) $dob)->addYears((int) config('people_pools.minimum_age', 18))->toDateString() <= $this->access->today();
        if (! $adult) {
            throw ValidationException::withMessages(['learner_id' => __('pool_not_adult')]);
        }
    }

    /** @return array<string, mixed> */
    public function vet(User $actor, int $memberId, array $input): array
    {
        $this->access->manager($actor, true, PoolAccess::VET);
        PoolMember::findOrFail($memberId);
        $data = Validator::make($input, self::rules('vet'))->validate();
        $data['revision'] = (int) $data['revision'];
        $today = $this->access->today();
        if ($data['checked_on'] > $today) {
            throw ValidationException::withMessages(['checked_on' => __('pool_checked_in_future')]);
        }
        if ($data['outcome'] === 'cleared') {
            $missing = array_diff((array) config('people_pools.required_checks'), $data['checks']);
            if ($missing !== []) {
                throw ValidationException::withMessages(['checks' => __('pool_checks_missing', ['checks' => implode(', ', $missing)])]);
            }
            $latest = CarbonImmutable::parse($data['checked_on'])->addMonths((int) config('people_pools.maximum_vetting_months', 36))->toDateString();
            if ($data['expires_on'] > $latest || $data['expires_on'] < $today) {
                throw ValidationException::withMessages(['expires_on' => __('pool_expiry_range', ['months' => (int) config('people_pools.maximum_vetting_months', 36)])]);
            }
        }
        $this->operations->once($actor, 'vet:'.$memberId, $data, fn () => $this->access->manager($actor, true, PoolAccess::VET), function () use ($actor, $memberId, $data, $today): array {
            $member = PoolMember::lockForUpdate()->findOrFail($memberId);
            abort_unless($member->revision === $data['revision'], 409, __('pool_changed'));
            abort_if($member->consent_status === PoolMember::WITHDRAWN, 409, __('pool_withdrawn'));
            abort_if($data['outcome'] === 'revoked' && $member->vetting_status !== PoolMember::CLEARED, 409, __('pool_nothing_to_revoke'));
            $before = ['vetting_status' => $member->vetting_status, 'vetting_expires_on' => $member->vetting_expires_on?->toDateString()];
            $vetting = PoolVetting::create(['pool_member_id' => $member->id, 'outcome' => $data['outcome'], 'checks' => array_values($data['checks']), 'checked_by' => $actor->id,
                'checked_on' => $data['checked_on'], 'expires_on' => $data['expires_on'] ?? null, 'note' => $data['note'] ?? null]);
            $member->fill(['vetting_status' => $data['outcome'], 'vetted_by' => $actor->id, 'vetted_on' => $data['checked_on'], 'vetting_expires_on' => $data['expires_on'] ?? null, 'revision' => $member->revision + 1])->save();
            if ($data['outcome'] !== 'cleared') {
                $this->cancelUpcoming($member, 'vetting_ended', $actor->id, $today);
            }
            Audit::record('people_pool.vetted', $member, $before, ['vetting_id' => $vetting->id, 'vetting_status' => $member->vetting_status, 'vetting_expires_on' => $data['expires_on'] ?? null, 'by' => $actor->id]);

            return ['id' => (int) $member->id, 'vetting_id' => (int) $vetting->id];
        });

        return $this->detail($actor, $memberId);
    }

    /**
     * The pool member withdraws consent: from this request on they can do
     * nothing in the pool. Their upcoming bookings are cancelled and every
     * pool session of the account ends. Withdrawing twice changes nothing.
     *
     * @return array<string, mixed>
     */
    public function withdraw(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $member = PoolMember::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            if ($member->consent_status !== PoolMember::WITHDRAWN) {
                $before = ['consent_status' => $member->consent_status];
                $member->fill(['consent_status' => PoolMember::WITHDRAWN, 'withdrawn_at' => now(), 'revision' => $member->revision + 1])->save();
                $this->cancelUpcoming($member, 'consent_withdrawn', null, $this->access->today());
                Audit::record('people_pool.consent_withdrawn', $member, $before, ['consent_status' => PoolMember::WITHDRAWN]);
            }
            AppTokens::revokeApp($user, AppTokens::POOL);

            return ['consent_status' => PoolMember::WITHDRAWN, 'withdrawn_at' => $member->withdrawn_at?->toIso8601String()];
        }, 3);
    }

    private function cancelUpcoming(PoolMember $member, string $reason, ?int $by, string $today): void
    {
        PoolBooking::where('pool_member_id', $member->id)->where('status', PoolBooking::BOOKED)
            ->whereIn('activity_session_id', ActivitySession::whereDate('on_date', '>=', $today)->select('id'))->lockForUpdate()->get()
            ->each(fn (PoolBooking $booking) => $booking->fill(['status' => PoolBooking::CANCELLED, 'cancel_reason' => $reason, 'cancelled_by' => $by, 'cancelled_at' => now(), 'revision' => $booking->revision + 1])->save());
    }

    /** @return array<string, mixed> */
    public function listing(User $actor, int $page = 1): array
    {
        $this->access->manager($actor);
        $rows = PoolMember::orderBy('id')->paginate(50, ['*'], 'page', $page);

        return ['items' => $rows->getCollection()->map(fn (PoolMember $member) => $this->present($member))->all(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total()];
    }

    /**
     * One pool member, for those who manage the pool or vet it: a vetter
     * needs the current revision and history to record a decision, and the
     * vetting answer is this same view, so it is never refused after the
     * decision was saved.
     *
     * @return array<string, mixed>
     */
    public function detail(User $actor, int $memberId): array
    {
        $fresh = $this->access->manager($actor, false, [PoolAccess::MANAGE, PoolAccess::VET]);
        $member = PoolMember::findOrFail($memberId);
        $notes = $this->access->mayVet($fresh);

        return $this->present($member) + [
            'vettings' => PoolVetting::where('pool_member_id', $member->id)->orderByDesc('id')->limit(50)->get()->map(fn (PoolVetting $row) => [
                'id' => (int) $row->id, 'outcome' => $row->outcome, 'checks' => array_values((array) $row->checks), 'checked_by' => (int) $row->checked_by,
                'checked_on' => $row->checked_on->toDateString(), 'expires_on' => $row->expires_on?->toDateString(), 'note' => $notes ? $row->note : null,
            ])->all(),
            'bookings' => app(PoolBookings::class)->presentAll(PoolBooking::where('pool_member_id', $member->id)->orderByDesc('id')->limit(50)->get()),
        ];
    }

    /** @return array<string, mixed> */
    public function present(PoolMember $member): array
    {
        $user = User::acrossSchools()->whereKey($member->user_id)->first(['id', 'first_name', 'last_name']);

        return ['id' => (int) $member->id, 'kind' => $member->kind, 'learner_id' => $member->learner_id === null ? null : (int) $member->learner_id,
            'name' => $user === null ? '' : trim($user->first_name.' '.$user->last_name), 'occupation' => $member->occupation, 'organisation' => $member->organisation,
            'offers_career_talk' => $member->offers_career_talk, 'offers_job_shadow' => $member->offers_job_shadow,
            'consent_status' => $member->consent_status, 'consented_at' => $member->consented_at?->toIso8601String(), 'withdrawn_at' => $member->withdrawn_at?->toIso8601String(),
            'vetting_status' => $member->vetting_status, 'vetted_on' => $member->vetted_on?->toDateString(), 'vetting_expires_on' => $member->vetting_expires_on?->toDateString(),
            'bookable_today' => $member->consentCurrent() && $member->vettedOn($this->access->today()), 'revision' => $member->revision];
    }
}
