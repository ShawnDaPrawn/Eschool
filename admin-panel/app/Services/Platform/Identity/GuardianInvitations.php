<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Http\Resources\Platform\SchoolSummaryResource;
use App\Mail\Platform\GuardianInvitationMail;
use App\Models\Platform\GuardianInvitation;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\MailService;
use App\Services\Platform\SchoolProfile;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuardianInvitations
{
    public function issue(User $guardian, User $actor): GuardianInvitation
    {
        abort_unless(filter_var($guardian->email, FILTER_VALIDATE_EMAIL) !== false && (int) $guardian->status === 1, 422);
        abort_unless(GuardianLink::query()->where('guardian_user_id', $guardian->id)->where('status', 'active')->exists(), 404);

        return DB::transaction(function () use ($guardian, $actor) {
            GuardianInvitation::query()->where('guardian_user_id', $guardian->id)->whereNull('used_at')->get()
                ->each(fn (GuardianInvitation $invitation) => $invitation->update(['used_at' => now()]));
            $token = Str::random(64);
            $invitation = GuardianInvitation::query()->create([
                'guardian_user_id' => $guardian->id, 'created_by' => $actor->id,
                'token_hash' => hash('sha256', $token), 'encrypted_token' => $token,
                'email_hash' => hash('sha256', strtolower(trim($guardian->email))),
                'expires_at' => now()->addDays((int) config('identity.invitation_days', 7)),
            ]);
            // afterCommit avoids sending a link whose creation rolled back.
            DB::afterCommit(fn () => $this->send($invitation));

            return $invitation;
        });
    }

    public function preview(string $token, Request $request): array
    {
        $invitation = $this->find($token);

        return app(CurrentSchool::class)->run((int) $invitation->school_id, function () use ($invitation, $request) {
            $school = School::query()->active()->findOrFail($invitation->school_id);
            $guardian = User::query()->where('status', 1)->findOrFail($invitation->guardian_user_id);
            abort_unless($this->boundTo($invitation, $guardian), 404);
            $ids = GuardianLink::query()->where('guardian_user_id', $guardian->id)->where('status', 'active')->pluck('learner_id');
            abort_if($ids->isEmpty(), 404);
            $learners = Students::query()->with('user')->whereIn('id', $ids)->get();

            return [
                'school' => (new SchoolSummaryResource(SchoolProfile::summary($school)))->resolve($request),
                'email' => $guardian->email,
                'learners' => $learners->map(fn (Students $learner) => ['id' => (int) $learner->id, 'name' => $learner->user->full_name])->values()->all(),
                'expires_at' => $invitation->expires_at->toIso8601String(),
                'needs_password' => $guardian->email_verified_at === null,
            ];
        });
    }

    public function accept(string $token, ?string $password, Request $request): array
    {
        return DB::transaction(function () use ($token, $password, $request) {
            $invitation = $this->find($token, lock: true);

            return app(CurrentSchool::class)->run((int) $invitation->school_id, function () use ($invitation, $password, $request) {
                School::query()->active()->findOrFail($invitation->school_id);
                $guardian = User::query()->where('status', 1)->lockForUpdate()->findOrFail($invitation->guardian_user_id);
                abort_unless($this->boundTo($invitation, $guardian), 404);
                abort_unless(GuardianLink::query()->where('guardian_user_id', $guardian->id)->where('status', 'active')->exists(), 404);
                if ($guardian->email_verified_at === null && $password === null) {
                    throw ValidationException::withMessages(['password' => 'Choose a password to accept your invitation.']);
                }
                if ($password !== null) {
                    $guardian->forceFill(['password' => Hash::make($password)])->save();
                    AppTokens::revokeAll($guardian);
                    DB::table('sessions')->where('user_id', $guardian->id)->delete();
                }
                // Possession of this emailed, one-use secret verifies the mailbox.
                $guardian->forceFill(['email_verified_at' => now()])->save();
                if (! PortalSession::isPortal($request) && $request->filled('fcm_id')) {
                    $guardian->forceFill(['fcm_id' => $request->input('fcm_id'), 'device_type' => $request->input('device_type')])->save();
                }
                abort_unless($guardian->hasRole('Parent'), 403);
                $invitation->update(['used_at' => now()]);
                $token = PortalSession::signIn($request, $guardian, AppTokens::PARENT);
                $children = GuardianLink::query()->where('guardian_user_id', $guardian->id)->where('status', 'active')->pluck('learner_id');
                $data = $guardian->toArray();
                $data['children'] = Students::query()->with('user')->whereIn('id', $children)->get()->toArray();

                return ['data' => $data, 'token' => $token];
            });
        });
    }

    /** Send reminders on day three and the seventh day before expiry. */
    public function remind(): int
    {
        $sent = 0;
        GuardianInvitation::query()->whereNull('used_at')->where('expires_at', '>', now())->orderBy('id')->each(function (GuardianInvitation $invitation) use (&$sent) {
            $field = $invitation->created_at->lte(now()->subDays(6)) ? 'reminded_day_seven_at' : 'reminded_day_three_at';
            if ($invitation->created_at->gt(now()->subDays(3)) || $invitation->getAttribute($field) !== null) {
                return;
            }
            // A concurrent scheduler claims the reminder only once.
            $claimed = GuardianInvitation::query()->whereKey($invitation->id)->whereNull($field)->update([$field => now()]);
            if ($claimed) {
                $sent += (int) $this->send($invitation);
            }
        });

        return $sent;
    }

    private function find(string $token, bool $lock = false): GuardianInvitation
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{64}$/D', $token) === 1, 404);

        // The secret identifies its school before authentication; no unscoped list.
        return GuardianInvitation::acrossSchools()->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')->where('expires_at', '>', now())
            ->when($lock, fn ($query) => $query->lockForUpdate())->firstOrFail();
    }

    private function send(GuardianInvitation $invitation): bool
    {
        return app(CurrentSchool::class)->run((int) $invitation->school_id, function () use ($invitation) {
            if (! $this->canDeliver($invitation)) {
                return false;
            }
            $guardian = User::query()->findOrFail($invitation->guardian_user_id);
            $school = School::query()->findOrFail($invitation->school_id);
            $portal = rtrim((string) config('identity.portal_url'), '/');

            return MailService::queue(new GuardianInvitationMail($guardian->email, $portal.'/invite/'.$invitation->encrypted_token, $school->name, (int) $invitation->id), (int) $guardian->id);
        });
    }

    public function canDeliver(GuardianInvitation $invitation): bool
    {
        return app(CurrentSchool::class)->run((int) $invitation->school_id, function () use ($invitation) {
            $current = GuardianInvitation::query()->whereNull('used_at')->where('expires_at', '>', now())->find($invitation->id);
            $guardian = User::query()->where('status', 1)->find($invitation->guardian_user_id);

            return $current !== null && $guardian !== null && School::query()->active()->whereKey($invitation->school_id)->exists()
                && $this->boundTo($current, $guardian)
                && GuardianLink::query()->where('guardian_user_id', $guardian->id)->where('status', 'active')->exists();
        });
    }

    private function boundTo(GuardianInvitation $invitation, User $guardian): bool
    {
        return is_string($invitation->email_hash) && hash_equals($invitation->email_hash, hash('sha256', strtolower(trim($guardian->email))))
            && SchoolMembership::query()->active()->where('school_id', $invitation->school_id)->where('user_id', $guardian->id)->where('role', 'Parent')->exists();
    }
}
