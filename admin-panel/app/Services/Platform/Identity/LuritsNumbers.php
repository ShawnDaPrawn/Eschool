<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The LURITS number is the learner's primary identifier: it follows them to
 * any school in South Africa. It belongs to the human's global identity
 * (learner_identifiers), never to one school's learner row.
 * Specification: engineering/specs/platform/lurits.md (ADR 0007).
 *
 * The NavigateEd student number (ADR 0005) stays the fallback for learners
 * without a LURITS number, and history stays linked to NavigateEd's own
 * records, so a mistyped or corrected LURITS number never breaks marks,
 * reports or transfers.
 */
class LuritsNumbers
{
    public const RECORDED = 'recorded';

    public const UNCHANGED = 'unchanged';

    public const CONFLICT = 'conflict';

    /** recordIfUnset(): the learner already has a different number, which is kept. */
    public const KEPT = 'kept';

    /** The platform_counters row that serialises claims. */
    public const CLAIMS_LOCK = 'lurits_claims';

    public const SOURCES = ['school_entry', 'sasams_import', 'onboarding_import', 'transfer', 'dbe_correction'];

    /** Digits only: spaces and dashes that people type are removed. */
    public static function normalise(string $number): string
    {
        return preg_replace('/[\s\-]/', '', trim($number)) ?? '';
    }

    /**
     * Whether a normalised number has the expected shape. The official format
     * is configured (config/identity.php lurits.pattern) until it is confirmed
     * against the SA-SAMS / DBE specification.
     */
    public static function valid(string $number): bool
    {
        return preg_match((string) config('identity.lurits.pattern'), $number) === 1;
    }

    /** Keyed so the stored hash cannot be reversed by trying every number. */
    public static function hash(string $number): string
    {
        return hash_hmac('sha256', 'lurits:'.$number, (string) config('app.key'));
    }

    /**
     * Records a learner's LURITS number. A number another person already holds
     * is not written: a conflict is opened for review and CONFLICT is returned,
     * without revealing who holds it or at which school.
     */
    public function record(Students $learner, string $number, string $source, ?User $actor = null, bool $onlyIfUnset = false): string
    {
        $number = self::normalise($number);
        if (! self::valid($number)) {
            throw new InvalidArgumentException('That is not a valid LURITS number.');
        }
        if (! in_array($source, self::SOURCES, true)) {
            throw new InvalidArgumentException('Unknown LURITS source.');
        }
        $hash = self::hash($number);
        $this->ownedHere($learner);

        // A deadlock in an outermost transaction is retried, and the retry
        // rechecks the holder, so a lost race answers CONFLICT.
        return DB::transaction(function () use ($learner, $number, $source, $actor, $hash, $onlyIfUnset) {
            $this->ownedHere($learner);
            // One claim at a time, platform-wide: two schools recording the same
            // unused number at once must not both pass the holder check, and
            // the second must get CONFLICT rather than a database error.
            $this->lockClaims();
            app(StudentNumbers::class)->assign($learner);
            $identity = DB::table('learner_identifiers')->where('user_id', $learner->user_id)->lockForUpdate()->first();
            // Checked under the row lock, so a number recorded meanwhile (for
            // example by the office during an import) is never replaced.
            if ($onlyIfUnset && $identity->lurits_hash !== null && $identity->lurits_hash !== $hash) {
                return self::KEPT;
            }
            $holder = DB::table('learner_identifiers')->where('lurits_hash', $hash)->lockForUpdate()->value('user_id');

            if ($holder !== null && (int) $holder !== (int) $learner->user_id) {
                $open = SchoolTable::query('lurits_conflicts')->where('learner_id', $learner->id)->where('lurits_hash', $hash)->where('status', 'open')->exists();
                if (! $open) {
                    SchoolTable::query('lurits_conflicts')->insert([
                        'school_id' => app(CurrentSchool::class)->id(), 'learner_id' => $learner->id, 'lurits_hash' => $hash,
                        'holder_user_id' => (int) $holder, 'source' => $source, 'status' => 'open', 'raised_by' => $actor?->id,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                Audit::record('identity.lurits_conflict', $learner, [], ['source' => $source, 'lurits_hash' => $hash]);

                return self::CONFLICT;
            }
            if ($identity->lurits_hash === $hash) {
                return self::UNCHANGED;
            }

            DB::table('learner_identifiers')->where('user_id', $learner->user_id)->update([
                'lurits_hash' => $hash, 'lurits_number' => Crypt::encryptString($number), 'lurits_source' => $source,
                'lurits_recorded_at' => now(), 'lurits_recorded_by' => $actor?->id, 'updated_at' => now(),
            ]);
            // Hashes only: the number itself never enters the audit log.
            Audit::record('identity.lurits_recorded', $learner, ['lurits_hash' => $identity->lurits_hash], ['lurits_hash' => $hash, 'source' => $source]);

            return self::RECORDED;
        }, 3);
    }

    /**
     * As record(), but only when the learner has no number yet: a different
     * number already recorded is kept and KEPT is returned, nothing written
     * and no claim raised. Used by imports, which never correct a number.
     */
    public function recordIfUnset(Students $learner, string $number, string $source, ?User $actor = null): string
    {
        return $this->record($learner, $number, $source, $actor, true);
    }

    /** The learner's LURITS number, or null when none is recorded. */
    public function forLearner(Students $learner): ?string
    {
        $this->ownedHere($learner);
        $encrypted = DB::table('learner_identifiers')->where('user_id', $learner->user_id)->value('lurits_number');

        return $encrypted === null ? null : Crypt::decryptString((string) $encrypted);
    }

    /**
     * LURITS numbers for many learners of the current school at once (exports),
     * keyed by learner id; null for a learner without one. Every learner must
     * belong to the current school, as for forLearner(), or nothing is read.
     *
     * @param  iterable<Students>  $learners
     * @return array<int, string|null>
     */
    public function forLearners(iterable $learners): array
    {
        $users = [];
        foreach ($learners as $learner) {
            $users[(int) $learner->getKey()] = (int) $learner->user_id;
        }
        if ($users === []) {
            return [];
        }
        $owned = [];
        foreach (array_chunk(array_keys($users), 1000) as $ids) {
            $owned += Students::acrossSchools()->withTrashed()->whereKey($ids)->where('school_id', app(CurrentSchool::class)->id())
                ->pluck('user_id', 'id')->map(fn ($user) => (int) $user)->all();
        }
        foreach ($users as $id => $user) {
            abort_unless(($owned[$id] ?? null) === $user, 404);
        }
        $encrypted = [];
        foreach (array_chunk(array_values(array_unique($users)), 1000) as $chunk) {
            $encrypted += DB::table('learner_identifiers')->whereIn('user_id', $chunk)->whereNotNull('lurits_number')->pluck('lurits_number', 'user_id')->all();
        }

        return array_map(fn (int $user) => isset($encrypted[$user]) ? Crypt::decryptString((string) $encrypted[$user]) : null, $users);
    }

    /**
     * The identifier shown first wherever a learner is identified: LURITS when
     * recorded, otherwise the NavigateEd number, labelled as temporary.
     *
     * @return array{type: 'lurits'|'navigateed', value: string}|null
     */
    public function primary(Students $learner): ?array
    {
        $this->ownedHere($learner);
        $lurits = $this->forLearner($learner);
        if ($lurits !== null) {
            return ['type' => 'lurits', 'value' => $lurits];
        }
        $number = DB::table('learner_identifiers')->where('user_id', $learner->user_id)->value('student_number');

        return $number === null ? null : ['type' => 'navigateed', 'value' => (string) $number];
    }

    /**
     * The human who holds a LURITS number, for matching at admission and
     * import. Internal only: callers must not reveal the holder's school or
     * records to the school asking; an existing holder means "start a transfer".
     */
    public function holderUserId(string $number): ?int
    {
        $number = self::normalise($number);
        if (! self::valid($number)) {
            return null;
        }
        $userId = DB::table('learner_identifiers')->where('lurits_hash', self::hash($number))->value('user_id');

        return $userId === null ? null : (int) $userId;
    }

    /**
     * Takes the platform-wide claims lock. The migration creates its row; it is
     * locked directly, because INSERT IGNORE on an existing row takes a shared
     * lock first, and two claims that both hold it deadlock on the upgrade.
     */
    private function lockClaims(): void
    {
        $lock = fn () => DB::table('platform_counters')->where('name', self::CLAIMS_LOCK)->lockForUpdate()->value('value');
        if ($lock() === null) {
            DB::table('platform_counters')->insertOrIgnore(['name' => self::CLAIMS_LOCK, 'value' => 0]);
            $lock();
        }
    }

    /**
     * Reads and writes act on a learner of the current school only, even for a
     * model loaded elsewhere: the identity is global, the authority is not.
     * holderUserId() is the one deliberately cross-school, internal lookup.
     */
    private function ownedHere(Students $learner): void
    {
        $owned = Students::acrossSchools()->withTrashed()->whereKey($learner->getKey())
            ->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $learner->user_id)->exists();
        abort_unless($owned, 404);
    }
}
