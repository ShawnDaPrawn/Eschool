<?php

declare(strict_types=1);

namespace App\Services\ChildMode;

use App\Models\ChildMode\ChildModePin;
use App\Models\ChildMode\ChildModeSession;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\PortalSession;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Child sessions (Action Plan 4.2). An approving guardian, signed in to the
 * parent app, enters the child's PIN and gets a child token: short-lived,
 * bound to that child and the child's school, and issued from that
 * parent-app sign-in. One child session per parent-app sign-in: starting
 * another ends the first.
 *
 * A child session ends, and its token is deleted at once, when the guardian
 * ends it, when the child leaves with the PIN, and when the guardian is
 * removed, loses the approval right or their Parent membership at the
 * school, the child's approval is withdrawn, the PIN changes, or the
 * guardian signs out of that device, of the parent app or everywhere, or
 * that parent-app sign-in expires. A child token never outlives the
 * parent-app sign-in it was issued from. Every child request also re-reads all of this
 * (verify), so nothing that changed is missed.
 *
 * The audit trail (child_mode_sessions and the audit log) holds ids, times
 * and the reason a session ended: never anything the child opened or typed.
 */
final class ChildModeSessions
{
    public const ENDED_WITH_PIN = 'ended_with_pin';

    public const ENDED_BY_GUARDIAN = 'ended_by_guardian';

    public const REPLACED = 'replaced';

    public const PIN_CHANGED = 'pin_changed';

    public const GUARDIAN_REMOVED = 'guardian_removed';

    public const APPROVAL_RIGHT_REMOVED = 'approval_right_removed';

    public const APPROVAL_WITHDRAWN = 'approval_withdrawn';

    public const PARENT_SIGNED_OUT = 'parent_signed_out';

    public const SIGNED_OUT_EVERYWHERE = 'signed_out_everywhere';

    public const EXPIRED = 'expired';

    public const NO_LONGER_ALLOWED = 'no_longer_allowed';

    public const MEMBERSHIP_ENDED = 'membership_ended';

    public const PARENT_SIGN_IN_EXPIRED = 'parent_sign_in_expired';

    public function __construct(private readonly ChildModeAccess $access, private readonly ChildModePins $pins) {}

    /** @return array<string, mixed> whether child mode is open for the guardian's child, and what the child may open */
    public function status(User $guardian, int $learnerId): array
    {
        $learner = $this->access->child($guardian, $learnerId);
        $reason = $this->access->refusal($guardian, $learner);
        $pin = ChildModePin::query()->where('learner_id', $learner->id)->first();

        return [
            'learner_id' => (int) $learner->id,
            'first_name' => (string) ($learner->user?->first_name ?? ''),
            'available' => $reason === null,
            'reason' => $reason,
            'message' => $reason === null ? null : (string) __(ChildModeAccess::REASONS[$reason]),
            'pin_set' => $pin !== null,
            'pin_locked_until' => $pin?->lockedNow() ? $pin->locked_until?->toIso8601String() : null,
            'opens' => $reason === null ? $this->access->opens($learner) : [],
            'session_minutes' => ChildModeAccess::minutes(),
        ];
    }

    /** @return array{token: string, session: array<string, mixed>} */
    public function start(Request $request, User $guardian, int $learnerId, string $pin): array
    {
        // Child mode lives in the parent app, never a web portal or another app's sign-in.
        $parentToken = $guardian->currentAccessToken();
        abort_if(PortalSession::isPortal($request) || ! $parentToken instanceof PersonalAccessToken || $parentToken->name !== AppTokens::PARENT, 403, __('child_mode_app_only'));
        $learner = $this->access->child($guardian, $learnerId);
        $this->access->assertOpen($guardian, $learner);
        $version = $this->pins->verify($learner, $pin)->version;
        $schoolId = app(CurrentSchool::class)->id();

        [$plain, $session] = DB::transaction(function () use ($guardian, $learner, $version, $parentToken, $schoolId): array {
            $row = ChildModePin::query()->where('learner_id', $learner->id)->lockForUpdate()->first();
            if ($row === null || $row->version !== $version) {
                throw ValidationException::withMessages(['pin' => __('child_mode_pin_changed')]);
            }
            $parent = PersonalAccessToken::query()->whereKey($parentToken->getKey())->lockForUpdate()->first();
            abort_unless($parent instanceof PersonalAccessToken && AppTokens::current($parent), 401);
            // Re-read after the lock: the guardian's rights may have changed meanwhile.
            $this->access->assertOpen($guardian, $learner);
            self::close(ChildModeSession::acrossSchools()->where('issued_from_token_id', $parentToken->getKey()), self::REPLACED);
            // Never outlive the parent-app sign-in it comes from.
            $expires = now()->addMinutes(ChildModeAccess::minutes());
            $parentExpires = AppTokens::expiresAt($parent);
            if ($parentExpires !== null && $parentExpires->lessThan($expires)) {
                $expires = Carbon::instance($parentExpires);
            }
            $token = $guardian->createToken(AppTokens::CHILD, ChildModeAccess::abilities($schoolId, (int) $learner->id), $expires);
            $session = ChildModeSession::query()->create([
                'learner_id' => $learner->id, 'guardian_user_id' => $guardian->id, 'token_id' => $token->accessToken->getKey(),
                'issued_from_token_id' => $parentToken->getKey(), 'pin_version' => $version, 'started_at' => now(), 'expires_at' => $expires,
            ]);
            Audit::record('child_mode.session_started', $session, [], self::trail($session));

            return [$token->plainTextToken, $session];
        }, 3);

        return ['token' => $plain, 'session' => $this->profile($session, $learner)];
    }

    /**
     * The open session of the child token on this request, re-checked: the
     * token is the session's, the parent-app sign-in that started it still
     * exists, the PIN has not changed, and the guardian may still use child
     * mode for this child. Anything else ends the session (401).
     */
    public function verify(Request $request, int $learnerId): ChildModeSession
    {
        $token = $request->user()?->currentAccessToken();
        abort_unless($token instanceof PersonalAccessToken, 401);
        $session = ChildModeSession::query()->where('token_id', $token->getKey())->whereNull('ended_at')->first();
        abort_if($session === null, 401, __('child_mode_ended'));
        $guardian = $request->user();
        $parent = $session->issued_from_token_id === null ? null : PersonalAccessToken::query()->find($session->issued_from_token_id);
        $reason = null;
        if ($session->learner_id !== $learnerId || $session->guardian_user_id !== (int) $guardian->getAuthIdentifier()) {
            $reason = self::NO_LONGER_ALLOWED;
        } elseif ($session->issued_from_token_id !== null && $parent === null) {
            $reason = self::PARENT_SIGNED_OUT;
        } elseif ($parent !== null && ! AppTokens::current($parent)) {
            // Sanctum no longer accepts the parent-app sign-in, even if its row is still there.
            $reason = self::PARENT_SIGN_IN_EXPIRED;
        } elseif (ChildModePin::query()->where('learner_id', $session->learner_id)->value('version') !== $session->pin_version) {
            $reason = self::PIN_CHANGED;
        } else {
            $learner = Students::query()->find($session->learner_id);
            $refusal = $learner === null ? 'learner_removed' : $this->access->refusal($guardian, $learner);
            $reason = match ($refusal) {
                null => null,
                'not_member' => self::MEMBERSHIP_ENDED,
                default => self::NO_LONGER_ALLOWED.':'.$refusal,
            };
        }
        if ($reason !== null) {
            self::close(ChildModeSession::query()->whereKey($session->id), $reason);
            abort(401, __('child_mode_ended'));
        }

        return $session;
    }

    /** @return array<string, mixed> the child session as the app shows it, with what the child may open now */
    public function profile(ChildModeSession $session, ?Students $learner = null): array
    {
        $learner ??= Students::query()->findOrFail($session->learner_id);
        $school = app(CurrentSchool::class)->school();

        return [
            'learner_id' => (int) $learner->id,
            'first_name' => (string) ($learner->user?->first_name ?? ''),
            'school' => ['id' => (int) $school?->id, 'name' => (string) $school?->name],
            'opens' => $this->access->opens($learner),
            'started_at' => $session->started_at->toIso8601String(),
            'expires_at' => $session->expires_at->toIso8601String(),
        ];
    }

    /** The child leaves child mode: only with the PIN, which counts towards the lock like any other try. */
    public function endWithPin(ChildModeSession $session, string $pin): void
    {
        $learner = Students::query()->findOrFail($session->learner_id);
        $this->pins->verify($learner, $pin);
        self::close(ChildModeSession::query()->whereKey($session->id), self::ENDED_WITH_PIN);
    }

    /** The guardian ends their own child sessions for this child, on any device; returns how many. */
    public function endForGuardian(User $guardian, int $learnerId): int
    {
        $learner = $this->access->child($guardian, $learnerId);

        return self::close(ChildModeSession::query()->where('learner_id', $learner->id)->where('guardian_user_id', $guardian->id), self::ENDED_BY_GUARDIAN);
    }

    /** Ends every child session for a child at the current school (or one guardian's only). */
    public static function revokeForLearner(int $learnerId, string $reason, ?int $guardianUserId = null): int
    {
        return self::close(ChildModeSession::query()->where('learner_id', $learnerId)
            ->when($guardianUserId !== null, fn (Builder $query) => $query->where('guardian_user_id', $guardianUserId)), $reason);
    }

    /** Ends every child session a guardian started, at every school. */
    public static function revokeForGuardian(int $guardianUserId, string $reason): int
    {
        return self::close(ChildModeSession::acrossSchools()->where('guardian_user_id', $guardianUserId), $reason);
    }

    /** Ends a guardian's child sessions at one school (their Parent membership there stopped). */
    public static function revokeAtSchool(int $guardianUserId, int $schoolId, string $reason): int
    {
        return self::close(ChildModeSession::acrossSchools()->where('school_id', $schoolId)->where('guardian_user_id', $guardianUserId), $reason);
    }

    /** Ends the child sessions started from one parent-app sign-in. */
    public static function revokeIssuedFrom(int $parentTokenId, string $reason): int
    {
        return self::close(ChildModeSession::acrossSchools()->where('issued_from_token_id', $parentTokenId), $reason);
    }

    /**
     * Ends the open sessions the query finds: deletes each child token and
     * records when and why. A session already past its expiry is recorded
     * as expired at that time.
     *
     * @param  Builder<ChildModeSession>  $query
     */
    private static function close(Builder $query, string $reason): int
    {
        $sessions = $query->whereNull('ended_at')->orderBy('id')->get();
        $by = Auth::hasUser() ? (int) Auth::id() : null;
        foreach ($sessions as $session) {
            if ($session->token_id !== null) {
                PersonalAccessToken::query()->whereKey($session->token_id)->where('name', AppTokens::CHILD)->delete();
            }
            $expired = $session->expires_at->isPast();
            $session->forceFill(['ended_at' => $expired ? $session->expires_at : now(), 'end_reason' => mb_substr($expired ? self::EXPIRED : $reason, 0, 40), 'ended_by' => $expired ? null : $by])->save();
            Audit::record('child_mode.session_ended', $session, [], self::trail($session) + ['reason' => $session->end_reason], (int) $session->school_id);
        }

        return $sessions->count();
    }

    /** @return array<string, int|string|null> ids and times only */
    private static function trail(ChildModeSession $session): array
    {
        return ['learner_id' => (int) $session->learner_id, 'guardian_user_id' => (int) $session->guardian_user_id,
            'started_at' => $session->started_at->toIso8601String(), 'expires_at' => $session->expires_at->toIso8601String(), 'ended_at' => $session->ended_at?->toIso8601String()];
    }
}
