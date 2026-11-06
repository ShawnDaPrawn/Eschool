<?php

declare(strict_types=1);

namespace App\Services\FamilySummary;

use App\Models\Ai\AiUsage;
use App\Models\Family\FamilyDailySummary;
use App\Models\Family\FamilyDailySummaryPart;
use App\Models\Family\FamilySummaryPreference;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * The family.daily_summary job (Action Plan 5.11). For each guardian who
 * opted in, once their chosen time has passed on their local day and their
 * quiet hours are not running, one summary across all their children and
 * schools: each school's part is read and written inside that school, and
 * one private notice says it is ready. Once per guardian per day.
 * engineering/specs/notifications/family-daily-summary.md.
 */
class FamilyDailySummaries
{
    /** @return array<string, int> what happened to each opted-in guardian */
    public function run(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $counts = ['sent' => 0, 'empty' => 0, 'already' => 0, 'not_due' => 0, 'quiet_hours' => 0, 'not_a_guardian' => 0, 'failed' => 0];
        FamilySummaryPreference::query()->where('enabled', true)->orderBy('id')->chunkById(200, function ($rows) use ($now, &$counts): void {
            foreach ($rows as $preference) {
                try {
                    $counts[$this->forGuardian($preference, $now)]++;
                } catch (Throwable $error) {
                    $counts['failed']++;
                    Log::warning('family_summary_retry_needed', ['preference_id' => (int) $preference->id, 'exception_class' => $error::class]);
                }
            }
        });

        return $counts;
    }

    public function forGuardian(FamilySummaryPreference $preference, CarbonImmutable $now): string
    {
        $zone = in_array($preference->timezone, timezone_identifiers_list(), true) ? $preference->timezone : 'Africa/Johannesburg';
        $local = $now->setTimezone($zone);
        if ($local->format('H:i') < substr((string) $preference->send_at, 0, 5)) {
            return 'not_due';
        }
        // The chosen time is outside quiet hours; if they have since moved, the summary waits for them to end (on the same day only).
        if (app(QuietHours::class)->holdUntil((int) $preference->user_id, FamilySummaryFacts::TYPE, $now) !== null) {
            return 'quiet_hours';
        }
        $user = User::acrossSchools()->where('status', 1)->find($preference->user_id);
        $schools = $user === null ? collect() : SchoolMembership::query()->where('user_id', $user->id)->where('role', 'Parent')
            ->where('status', SchoolMembership::ACTIVE)->whereIn('school_id', School::query()->active()->select('id'))
            ->orderBy('school_id')->pluck('school_id')->map(fn ($id) => (int) $id)->unique()->values();
        if ($user === null || $schools->isEmpty()) {
            return 'not_a_guardian';
        }
        $summary = $this->claim($user, $local->toDateString());
        if ($summary === null) {
            return 'already';
        }
        $parts = [];
        foreach ($schools as $schoolId) {
            try {
                $part = app(CurrentSchool::class)->run($schoolId, fn () => $this->part($summary, $user, $now));
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // Not this school's guardian today (role, account or link withdrawn): no part here.
                $part = null;
                app(CurrentSchool::class)->run($schoolId, fn () => FamilyDailySummaryPart::query()->where('family_daily_summary_id', $summary->id)->delete());
            }
            if ($part !== null) {
                $parts[$schoolId] = $part;
            }
        }
        if ($parts === [] || (! config('family_summary.send_when_empty') && ! collect($parts)->contains(fn (FamilyDailySummaryPart $p) => FamilySummaryFacts::hasContent($p->facts)))) {
            $this->finish($summary, $parts === [] ? 'not_a_guardian' : FamilyDailySummary::EMPTY);

            return $parts === [] ? 'not_a_guardian' : 'empty';
        }

        return $this->deliver($summary, $user, array_key_first($parts)) ? 'sent' : 'already';
    }

    /** The day's row, or null when that day's summary is already done. */
    private function claim(User $user, string $date): ?FamilyDailySummary
    {
        try {
            return DB::transaction(function () use ($user, $date): ?FamilyDailySummary {
                $row = FamilyDailySummary::query()->where('user_id', $user->id)->whereDate('summary_date', $date)->lockForUpdate()->first();
                if ($row === null) {
                    return FamilyDailySummary::query()->create(['user_id' => $user->id, 'summary_date' => $date, 'state' => FamilyDailySummary::COMPOSING,
                        'expires_at' => CarbonImmutable::parse($date)->addDays(max(1, (int) config('family_summary.retention_days', 7)) + 1)]);
                }

                // An unfinished day (a crash mid-way) is composed again; a finished one never.
                return $row->state === FamilyDailySummary::COMPOSING ? $row : null;
            });
        } catch (QueryException $error) {
            if (($error->errorInfo[1] ?? null) === 1062) {
                return null; // Another worker claimed the day a moment ago.
            }
            throw $error;
        }
    }

    /** This school's part, read and written in this school; null when there is no current child here. */
    private function part(FamilyDailySummary $summary, User $user, CarbonImmutable $now): ?FamilyDailySummaryPart
    {
        $facts = app(FamilySummaryFacts::class)->collect($user, $now);
        $existing = FamilyDailySummaryPart::query()->where('family_daily_summary_id', $summary->id)->first();
        if ($facts === null) {
            $existing?->delete();

            return null;
        }
        $hash = hash('sha256', json_encode($facts, JSON_THROW_ON_ERROR));
        $part = $existing ?? new FamilyDailySummaryPart(['family_daily_summary_id' => $summary->id, 'user_id' => $user->id]);
        if ($existing === null || ! hash_equals((string) $existing->facts_hash, $hash)) {
            // A new receipt for new facts; a retry of the same facts reuses the stored one.
            $part->operation_id = (string) Str::uuid();
        }
        $learners = [];
        $fresh = app(FamilySummaryAuthority::class)->guardian($user);
        foreach (app(FamilySummaryAuthority::class)->children($fresh) as $learner) {
            $learners[] = (int) $learner->id;
        }
        $part->fill(['facts' => $facts, 'facts_hash' => $hash, 'learner_ids' => $learners, 'expires_at' => $summary->expires_at,
            'text' => $existing?->text ?? '', 'wording' => $existing?->wording ?? 'template'])->save();
        $words = app(FamilySummaryWriter::class)->write($fresh, $part);
        $part->fill(['text' => $words['text'], 'wording' => $words['wording'], 'fallback_reason' => $words['reason']])->save();

        return $part;
    }

    private function finish(FamilyDailySummary $summary, string $state): void
    {
        DB::transaction(function () use ($summary, $state): void {
            $fresh = FamilyDailySummary::query()->lockForUpdate()->findOrFail($summary->id);
            if ($fresh->state === FamilyDailySummary::COMPOSING) {
                // A day with no school part keeps no row at all.
                $state === FamilyDailySummary::EMPTY ? $fresh->update(['state' => $state]) : $fresh->delete();
            }
        });
    }

    /** One private notice, filed in the first school with a part; never twice. */
    private function deliver(FamilyDailySummary $summary, User $user, int $noticeSchool): bool
    {
        return DB::transaction(function () use ($summary, $user, $noticeSchool): bool {
            $fresh = FamilyDailySummary::query()->lockForUpdate()->findOrFail($summary->id);
            if ($fresh->state !== FamilyDailySummary::COMPOSING || $fresh->notification_id !== null) {
                return false;
            }

            return app(CurrentSchool::class)->run($noticeSchool, function () use ($fresh, $user): bool {
                app(FamilySummaryAuthority::class)->guardian($user);
                $link = '/family/daily-summary/'.$fresh->id;
                $notice = app(Notifier::class)->makeNotice(FamilySummaryFacts::TYPE, __('Your daily family summary'),
                    __('Your family summary is ready. Open NavigateEd to read it.'), $link);
                $notice->delivery_payload = ['link' => $link, 'summary_id' => (int) $fresh->id];
                $notice->save();
                $fresh->update(['state' => FamilyDailySummary::SENT, 'notice_school_id' => app(CurrentSchool::class)->id(), 'notification_id' => $notice->id, 'sent_at' => now()]);
                app(Notifier::class)->assign($notice, [(int) $user->id]);

                return true;
            });
        });
    }

    /**
     * Erase summaries (and every school's part) past their retention, and the
     * AI answers the gateway kept on its receipts for them: metering and cost
     * stay, the words do not.
     */
    public function prune(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $cutoff = $now->subDays(max(1, (int) config('family_summary.retention_days', 7)) + 1);
        foreach (School::query()->orderBy('id')->pluck('id') as $schoolId) {
            app(CurrentSchool::class)->run((int) $schoolId, fn () => AiUsage::query()->where('feature', FamilySummaryWriter::FEATURE)->where('created_at', '<=', $cutoff)
                ->where(fn ($q) => $q->whereNotNull('inference_output')->orWhereNotNull('inference_sources'))
                ->update(['inference_output' => null, 'inference_sources' => null]));
        }
        $deleted = 0;
        FamilyDailySummary::query()->where('expires_at', '<=', $now)->orderBy('id')->chunkById(200, function ($rows) use (&$deleted): void {
            foreach ($rows as $row) {
                FamilyDailySummaryPart::acrossSchools()->where('family_daily_summary_id', $row->id)->delete();
                $row->delete();
                $deleted++;
            }
        });

        return $deleted;
    }
}
