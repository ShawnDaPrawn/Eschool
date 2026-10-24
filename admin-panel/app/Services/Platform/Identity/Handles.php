<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Platform\HandleProposal;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\Term;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\HandleProposed;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Handles
{
    public function state(User $user): array
    {
        $proposal = HandleProposal::query()->where('user_id', $user->id)->where('status', 'pending')->where('expires_at', '>', now())->latest('id')->first();
        $blockedThrough = $this->blockedThrough($user);

        return [
            'handle' => User::acrossSchools()->whereKey($user->id)->value('handle'),
            'proposal' => $proposal === null ? null : ['id' => (int) $proposal->id, 'handle' => $proposal->handle, 'status' => $proposal->status, 'expires_at' => $proposal->expires_at->toIso8601String()],
            'can_change_after' => $blockedThrough === null ? null : CarbonImmutable::parse($blockedThrough)->addDay()->toDateString(),
        ];
    }

    public function request(User $actor, string $handle): array
    {
        return DB::transaction(function () use ($actor, $handle) {
            $user = User::acrossSchools()->lockForUpdate()->findOrFail($actor->id);
            $handle = strtolower(trim($handle));
            if ($handle === $user->handle) {
                return $this->state($user);
            }
            $this->validate($user, $handle);
            $this->assertChangeAllowed($user);
            $this->claim($user, $handle, false);
            // A human has at most one current proposal, even across schools.
            HandleProposal::acrossSchools()->where('user_id', $user->id)->where('status', 'pending')->get()
                ->each(function (HandleProposal $pending) use ($handle, $user) {
                    $pending->update(['status' => 'superseded', 'decided_at' => now()]);
                    if ($pending->handle !== $handle) {
                        $this->releaseProposal($user, $pending->handle);
                    }
                });
            if ($this->requiresGuardian($user)) {
                $learner = Students::query()->where('user_id', $user->id)->first();
                if ($learner === null) {
                    throw ValidationException::withMessages(['handle' => 'Choose your learner school to request guardian approval.']);
                }
                $proposal = HandleProposal::query()->create([
                    'user_id' => $user->id, 'learner_id' => $learner->id, 'handle' => $handle,
                    'expires_at' => now()->addDays(7),
                ]);
                Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'handle.proposed'));
                Outbox::record(new HandleProposed(app(CurrentSchool::class)->id(), (int) $proposal->id));
            } else {
                $this->activate($user, $handle, $actor, null);
            }

            return $this->state($user);
        }, 5);
    }

    public function decide(User $guardian, int $proposalId, string $decision): array
    {
        $proposal = HandleProposal::query()->findOrFail($proposalId);
        $learner = Students::query()->findOrFail($proposal->learner_id);
        Gate::forUser($guardian)->authorize('approveAccess', $learner);

        return DB::transaction(function () use ($proposal, $learner, $guardian, $decision) {
            $user = User::acrossSchools()->lockForUpdate()->findOrFail($learner->user_id);
            $proposal = HandleProposal::query()->lockForUpdate()->findOrFail($proposal->id);
            // Recheck mutable guardian rights after obtaining the identity lock.
            Gate::forUser($guardian)->authorize('approveAccess', $learner);
            abort_unless($proposal->status === 'pending', 409, 'This proposal has already been decided.');
            abort_if($proposal->expires_at->isPast(), 410, 'This proposal has expired.');
            if ($decision === 'approve') {
                $this->validate($user, $proposal->handle);
                $this->assertChangeAllowed($user);
                $this->activate($user, $proposal->handle, $user, $guardian);
            } else {
                $this->releaseProposal($user, $proposal->handle);
            }
            $proposal->update(['status' => $decision === 'approve' ? 'approved' : 'rejected', 'decided_by' => $guardian->id, 'decided_at' => now()]);

            return $this->state($user);
        }, 5);
    }

    public function validate(User $user, string $handle): void
    {
        $invalid = ! preg_match('/^[a-z](?:[a-z0-9._]{1,18})[a-z0-9]$/D', $handle) || preg_match('/[._]{2}/', $handle);
        $reserved = ['admin', 'administrator', 'support', 'navigateed', 'student', 'learner', 'parent', 'teacher', 'facilitator', 'counsellor', 'saas', 'school', 'system', 'api', 'security', 'help'];
        $reservedKey = str_replace(['.', '_'], '', $handle);
        if ($invalid || in_array($reservedKey, $reserved, true) || School::query()->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(slug, "-", ""), ".", ""), "_", "")) = ?', [$reservedKey])->exists()
            || DB::table('roles')->whereRaw('LOWER(REPLACE(REPLACE(REPLACE(name, " ", ""), ".", ""), "_", "")) = ?', [$reservedKey])->exists()) {
            throw ValidationException::withMessages(['handle' => 'Choose a handle of 3–20 lower-case letters, digits, dots or underscores, starting with a letter. Avoid reserved names and repeated punctuation.']);
        }
        if (User::acrossSchools()->withTrashed()->where('email', $handle)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['handle' => 'This handle is unavailable.']);
        }
        if (SchoolMembership::query()->where('user_id', $user->id)->where('role', 'Student')->exists()) {
            $plain = str_replace(['.', '_'], '', $handle);
            $surname = preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii((string) $user->last_name)));
            $number = DB::table('learner_identifiers')->where('user_id', $user->id)->value('student_number');
            $privateValues = [$surname, $number];
            if ($user->dob) {
                $dob = CarbonImmutable::parse($user->dob);
                foreach (['Ymd', 'dmY', 'mdY', 'ymd', 'dmy', 'mdy'] as $format) {
                    $privateValues[] = $dob->format($format);
                }
            }
            foreach ($privateValues as $value) {
                if ($value !== null && $value !== '' && str_contains($plain, $value)) {
                    throw ValidationException::withMessages(['handle' => 'Choose a handle without your surname, student number or date of birth.']);
                }
            }
        }
    }

    private function requiresGuardian(User $user): bool
    {
        $learner = SchoolMembership::query()->where('user_id', $user->id)->where('role', 'Student')->exists();

        return $learner && (! $user->dob || CarbonImmutable::parse($user->dob)->age < 18);
    }

    private function assertChangeAllowed(User $user): void
    {
        $blockedThrough = $this->blockedThrough($user);
        if ($blockedThrough !== null && now()->toDateString() <= $blockedThrough) {
            throw ValidationException::withMessages(['handle' => 'You can change your handle after the current term ends.']);
        }
    }

    private function blockedThrough(User $user): ?string
    {
        $last = DB::table('handle_changes')->where('user_id', $user->id)->orderByDesc('id')->first();
        if ($last === null) {
            return null;
        }
        $schoolIds = SchoolMembership::query()->active()->where('user_id', $user->id)->select('school_id');
        $overlappingTerm = Term::acrossSchools()->whereIn('school_id', $schoolIds)
            ->whereDate('start_date', '<=', CarbonImmutable::parse($last->created_at)->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())->max('end_date');

        return max((string) $last->term_ends_on, (string) $overlappingTerm);
    }

    private function claim(User $user, string $handle, bool $active): void
    {
        DB::table('handle_claims')->insertOrIgnore(['handle' => $handle, 'user_id' => $user->id, 'active' => false, 'reserved_until' => now()->subSecond(), 'created_at' => now(), 'updated_at' => now()]);
        $claim = DB::table('handle_claims')->where('handle', $handle)->lockForUpdate()->first();
        $held = $claim->active || ($claim->reserved_until !== null && CarbonImmutable::parse($claim->reserved_until)->isFuture());
        if (($held && (int) $claim->user_id !== (int) $user->id)
            || User::acrossSchools()->withTrashed()->where('handle', $handle)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['handle' => 'This handle is unavailable.']);
        }
        $until = now()->addDays(7);
        if ((int) $claim->user_id === (int) $user->id && $claim->reserved_until !== null && CarbonImmutable::parse($claim->reserved_until)->greaterThan($until)) {
            $until = CarbonImmutable::parse($claim->reserved_until);
        }
        DB::table('handle_claims')->where('handle', $handle)->update([
            'user_id' => $user->id, 'active' => $active, 'reserved_until' => $active ? null : $until, 'updated_at' => now(),
        ]);
    }

    private function activate(User $user, string $handle, User $actor, ?User $guardian): void
    {
        $this->claim($user, $handle, true);
        $old = $user->handle;
        if ($old !== null) {
            DB::table('handle_claims')->where('handle', $old)->where('user_id', $user->id)->update(['active' => false, 'reserved_until' => now()->addDays(90), 'updated_at' => now()]);
        }
        // Use the latest end among overlapping terms at this human's schools;
        // switching schools cannot bypass the once-per-term limit.
        $schoolIds = SchoolMembership::query()->active()->where('user_id', $user->id)->select('school_id');
        $termEnd = Term::acrossSchools()->whereIn('school_id', $schoolIds)->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())->max('end_date');
        $termEnd ??= CarbonImmutable::now()->endOfQuarter()->toDateString();
        $user->forceFill(['handle' => $handle])->save();
        DB::table('handle_changes')->insert([
            'user_id' => $user->id, 'old_handle' => $old, 'new_handle' => $handle,
            'changed_by' => $actor->id, 'approved_by' => $guardian?->id, 'term_ends_on' => $termEnd,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Audit::record('identity.handle_changed', $user, ['handle' => $old], ['handle' => $handle, 'approved_by' => $guardian?->id]);
        Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'handle.changed'));
    }

    private function releaseProposal(User $user, string $handle): void
    {
        $oldChange = DB::table('handle_changes')->where('user_id', $user->id)->where('old_handle', $handle)->max('created_at');
        $until = $oldChange === null ? now() : max(now(), CarbonImmutable::parse($oldChange)->addDays(90));
        DB::table('handle_claims')->where('handle', $handle)->where('user_id', $user->id)->where('active', false)
            ->update(['reserved_until' => $until, 'updated_at' => now()]);
    }
}
