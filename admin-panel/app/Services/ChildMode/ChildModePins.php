<?php

declare(strict_types=1);

namespace App\Services\ChildMode;

use App\Models\ChildMode\ChildModePin;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * The child-mode PIN (Action Plan 4.2): one per child at a school, set or
 * changed by an approving guardian, stored only as a hash. Wrong PINs are
 * counted per child under a row lock, whichever guardian or device enters
 * them; after child_mode_pin_attempts wrong ones the PIN is locked for
 * child_mode_pin_lock_minutes. Setting a new PIN clears the lock and ends
 * every child session started with the old one.
 */
final class ChildModePins
{
    public function __construct(private readonly ChildModeAccess $access) {}

    /** Sets or changes the child's PIN; returns whether it replaced one. */
    public function set(User $guardian, int $learnerId, string $pin): bool
    {
        $learner = $this->access->child($guardian, $learnerId);
        $this->access->assertOpen($guardian, $learner);

        return DB::transaction(function () use ($guardian, $learner, $pin): bool {
            Students::query()->whereKey($learner->id)->lockForUpdate()->firstOrFail();
            $row = ChildModePin::query()->where('learner_id', $learner->id)->lockForUpdate()->first();
            $changed = $row !== null;
            $row ??= new ChildModePin(['learner_id' => $learner->id, 'version' => 0]);
            $row->fill(['pin_hash' => Hash::make($pin), 'version' => $row->version + 1, 'set_by' => $guardian->id, 'failed_attempts' => 0, 'locked_until' => null])->save();
            Audit::record($changed ? 'child_mode.pin_changed' : 'child_mode.pin_set', $row, [], ['learner_id' => (int) $learner->id, 'version' => $row->version]);
            if ($changed) {
                ChildModeSessions::revokeForLearner((int) $learner->id, ChildModeSessions::PIN_CHANGED);
            }

            return $changed;
        }, 3);
    }

    /**
     * Checks a PIN. A wrong one is counted and saved before the refusal, so
     * the count survives; the lock comes after the last allowed attempt.
     */
    public function verify(Students $learner, string $pin): ChildModePin
    {
        [$outcome, $row] = DB::transaction(function () use ($learner, $pin): array {
            $row = ChildModePin::query()->where('learner_id', $learner->id)->lockForUpdate()->first();
            if ($row === null) {
                return ['missing', null];
            }
            if ($row->lockedNow()) {
                return ['locked', $row];
            }
            if (Hash::check($pin, $row->pin_hash)) {
                if ($row->failed_attempts > 0 || $row->locked_until !== null) {
                    $row->forceFill(['failed_attempts' => 0, 'locked_until' => null])->save();
                }

                return ['ok', $row];
            }
            $attempts = $row->failed_attempts + 1;
            if ($attempts >= self::attempts()) {
                $row->forceFill(['failed_attempts' => 0, 'locked_until' => now()->addMinutes(self::lockMinutes())])->save();
                Audit::record('child_mode.pin_locked', $row, [], ['learner_id' => (int) $learner->id, 'locked_until' => $row->locked_until?->toIso8601String()]);

                return ['locked', $row];
            }
            $row->forceFill(['failed_attempts' => $attempts])->save();

            return ['wrong', $row];
        }, 3);

        return match ($outcome) {
            'ok' => $row,
            'missing' => throw ValidationException::withMessages(['pin' => __('child_mode_pin_needed')]),
            'wrong' => throw ValidationException::withMessages(['pin' => __('child_mode_pin_wrong', ['count' => self::attempts() - $row->failed_attempts])]),
            default => abort(429, __('child_mode_pin_locked', ['minutes' => max(1, (int) ceil(now()->diffInSeconds($row->locked_until) / 60))]),
                ['Retry-After' => (string) max(1, (int) ceil(now()->diffInSeconds($row->locked_until)))]),
        };
    }

    public static function attempts(): int
    {
        return max(3, (int) config('identity.child_mode_pin_attempts', 5));
    }

    public static function lockMinutes(): int
    {
        return max(1, (int) config('identity.child_mode_pin_lock_minutes', 15));
    }

    /** A PIN that is all one digit or a straight run (1234, 9876) is refused. */
    public static function tooEasy(string $pin): bool
    {
        if (count(array_unique(str_split($pin))) === 1) {
            return true;
        }
        foreach ([1, -1] as $step) {
            $run = true;
            for ($i = 1; $i < strlen($pin); $i++) {
                $run = $run && ((int) $pin[$i] - (int) $pin[$i - 1]) === $step;
            }
            if ($run) {
                return true;
            }
        }

        return false;
    }
}
