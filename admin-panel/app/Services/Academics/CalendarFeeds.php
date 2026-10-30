<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\CalendarFeed;
use App\Models\ExamTimetable;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\TimetablePolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * A learner's timetable as a calendar subscription (Action Plan 1.10). The
 * learner, or a guardian who may see their timetable, makes a secret link;
 * the calendar app fetches it as iCalendar. Exams are always in it, lessons
 * only when asked for. Every fetch rechecks, inside the link's school, that
 * the person who made it may still see that learner's timetable; the link
 * stops working when they may not, or when they revoke it.
 * engineering/specs/academics/calendar-feed.md.
 */
class CalendarFeeds
{
    /** Live links one person may hold for one learner. */
    public const MAX_PER_LEARNER = 5;

    /** How far ahead lessons are listed (exams: the whole school year). */
    public const LESSON_WEEKS = 4;

    /** How far back past exams stay in the feed. */
    public const PAST_DAYS = 30;

    /** @return list<array{id: int, learner_id: int, learner_name: string, include_lessons: bool, created_at: string, last_used_at: ?string}> */
    public function listing(User $actor): array
    {
        // Anyone at the school sees only the links they made themselves.
        app(ProfilePolicy::class)->actor($actor);
        $feeds = CalendarFeed::query()->where('user_id', $actor->id)->whereNull('revoked_at')->orderBy('id')->get();
        $names = Students::query()->with('user:id,first_name,last_name')->whereIn('id', $feeds->pluck('learner_id'))->get()
            ->mapWithKeys(fn (Students $learner) => [(int) $learner->id => trim(($learner->user?->first_name ?? '').' '.($learner->user?->last_name ?? ''))]);

        return $feeds->map(fn (CalendarFeed $feed) => ['id' => (int) $feed->id, 'learner_id' => $feed->learner_id, 'learner_name' => (string) ($names[$feed->learner_id] ?? ''),
            'include_lessons' => $feed->include_lessons, 'created_at' => $feed->created_at?->toIso8601String() ?? '', 'last_used_at' => $feed->last_used_at?->toIso8601String()])->values()->all();
    }

    /**
     * Makes a link for a learner the actor may see. The URL (with its token)
     * is returned only here, once.
     *
     * @return array{id: int, learner_id: int, include_lessons: bool, url: string}
     */
    public function create(User $actor, int $learnerId, bool $lessons): array
    {
        $learner = (int) app(TimetablePolicy::class)->authorise($actor, 'read', $learnerId);
        abort_unless($learner === $learnerId, 404);

        return DB::transaction(function () use ($actor, $learner, $lessons) {
            // Serialise one person's links so the limit holds under concurrent requests.
            User::query()->whereKey($actor->id)->lockForUpdate()->first();
            $live = CalendarFeed::query()->where('user_id', $actor->id)->where('learner_id', $learner)->whereNull('revoked_at')->count();
            abort_if($live >= self::MAX_PER_LEARNER, 422, __('Remove a calendar link for this learner before making another.'));
            $token = Str::random(48);
            $feed = new CalendarFeed;
            $feed->forceFill(['user_id' => $actor->id, 'learner_id' => $learner, 'token_hash' => self::hash($token), 'include_lessons' => $lessons])->save();
            Audit::record('calendar_feed.created', $feed, [], ['learner_id' => $learner, 'include_lessons' => $lessons]);

            return ['id' => (int) $feed->id, 'learner_id' => $learner, 'include_lessons' => $lessons, 'url' => route('calendar.feed', ['token' => $token])];
        });
    }

    public function revoke(User $actor, int $id): void
    {
        $feed = CalendarFeed::query()->where('user_id', $actor->id)->whereNull('revoked_at')->findOrFail($id);
        $feed->forceFill(['revoked_at' => now()])->save();
        Audit::record('calendar_feed.revoked', $feed, [], ['learner_id' => $feed->learner_id]);
    }

    /**
     * The iCalendar text for a token, or null when the link is unknown,
     * revoked, or its owner may no longer see the learner's timetable.
     */
    public function render(string $token): ?string
    {
        if (preg_match('/^[A-Za-z0-9]{48}$/D', $token) !== 1) {
            return null;
        }
        $feed = app(CurrentSchool::class)->acrossSchools(fn () => CalendarFeed::query()->where('token_hash', self::hash($token))->whereNull('revoked_at')->first());
        if ($feed === null) {
            return null;
        }

        return app(CurrentSchool::class)->run($feed->school_id, function () use ($feed): ?string {
            $owner = User::query()->whereKey($feed->user_id)->where('status', 1)->first();
            $school = app(CurrentSchool::class)->school();
            if ($owner === null || $school === null || ! $school->isActive() || ! app(ModuleGate::class)->allows('academics')
                || ! SchoolMembership::query()->active()->where('school_id', $feed->school_id)->where('user_id', $owner->id)->exists()) {
                return $this->refused($feed, 'access');
            }
            try {
                abort_unless((int) app(TimetablePolicy::class)->authorise($owner, 'read', $feed->learner_id) === $feed->learner_id, 404);
                $events = $this->events($owner, $feed);
            } catch (HttpExceptionInterface|ModelNotFoundException $error) {
                if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404, 422], true)) {
                    throw $error;
                }

                return $this->refused($feed, 'access');
            }
            if ($feed->last_used_at === null || $feed->last_used_at->lt(now()->subHour())) {
                $feed->forceFill(['last_used_at' => now()])->save();
            }
            $learner = Students::query()->with('user:id,first_name')->find($feed->learner_id);

            return Ical::calendar(trim(($learner?->user?->first_name ?? '').' · '.$school->name, ' ·'), $school->timezone ?: 'UTC', $events);
        });
    }

    /** @return list<array{uid: string, start: CarbonImmutable, end: CarbonImmutable, summary: string, location: ?string, description: ?string}> */
    private function events(User $owner, CalendarFeed $feed): array
    {
        $zone = app(CurrentSchool::class)->school()?->timezone ?: 'UTC';
        $today = CarbonImmutable::now($zone)->startOfDay();
        $from = $today->subDays(self::PAST_DAYS);
        $to = $today->addWeeks(self::LESSON_WEEKS)->subDay();
        $events = [];
        $years = SessionYear::query()->whereDate('start_date', '<=', $to->toDateString())->whereDate('end_date', '>=', $from->toDateString())->orderBy('id')->get();
        foreach ($years as $year) {
            $events = array_merge($events, $this->exams($feed->learner_id, (int) $year->id, $from, $zone));
            if ($feed->include_lessons) {
                for ($week = $today; $week <= $to; $week = $week->addWeek()) {
                    foreach (app(TimetableQueries::class)->week($owner, (int) $year->id, $week->toDateString(), $feed->learner_id)['lessons'] as $lesson) {
                        if ($lesson['date'] > $to->toDateString() || $lesson['starts_at'] === '' || $lesson['ends_at'] === '') {
                            continue;
                        }
                        $events[] = ['uid' => 'lesson-'.$lesson['id'].'-'.$lesson['date'],
                            'start' => CarbonImmutable::parse($lesson['date'].' '.$lesson['starts_at'], $zone), 'end' => CarbonImmutable::parse($lesson['date'].' '.$lesson['ends_at'], $zone),
                            'summary' => (string) ($lesson['subject_name'] ?? $lesson['title']), 'location' => $lesson['room_name'] ?? null, 'description' => $lesson['period_name'] ?? null];
                    }
                }
            }
        }
        usort($events, fn ($a, $b) => [$a['start'], $a['uid']] <=> [$b['start'], $b['uid']]);

        return $events;
    }

    /** The exam sittings of every class the learner is placed in this year, from a month ago. */
    private function exams(int $learnerId, int $yearId, CarbonImmutable $from, string $zone): array
    {
        $classes = Enrolment::query()->with('homeroom')->where('learner_id', $learnerId)->where('session_year_id', $yearId)->get()
            ->map(fn (Enrolment $enrolment) => $enrolment->homeroom?->class_id)->filter()->unique()->values();
        if ($classes->isEmpty()) {
            return [];
        }

        return ExamTimetable::query()->with('subject', 'exam')->whereIn('class_id', $classes)->where('session_year_id', $yearId)->whereDate('date', '>=', $from->toDateString())
            ->orderBy('date')->limit(500)->get()->map(fn (ExamTimetable $sitting) => [
                'uid' => 'exam-'.$sitting->id,
                'start' => CarbonImmutable::parse(CarbonImmutable::parse($sitting->date)->toDateString().' '.substr((string) $sitting->start_time, 0, 5), $zone),
                'end' => CarbonImmutable::parse(CarbonImmutable::parse($sitting->date)->toDateString().' '.substr((string) $sitting->end_time, 0, 5), $zone),
                'summary' => __('Exam: :subject', ['subject' => (string) ($sitting->subject?->name ?? __('an exam'))]),
                'location' => $sitting->venue ?: null,
                'description' => $sitting->exam?->name,
            ])->all();
    }

    private function refused(CalendarFeed $feed, string $reason): ?string
    {
        Log::notice('calendar_feed_refused', ['feed_id' => $feed->id, 'school_id' => $feed->school_id, 'reason' => $reason]);

        return null;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
