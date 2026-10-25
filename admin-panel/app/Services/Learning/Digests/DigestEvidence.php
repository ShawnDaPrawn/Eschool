<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Event;
use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Learning\PracticeSetEntry;
use App\Models\MultipleEvent;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

/** Aggregates only released school evidence; never notes, risk labels or AI text. */
class DigestEvidence
{
    public function collect(User $actor, int $learnerId, CarbonImmutable $start): array
    {
        $learner = app(DigestAuthority::class)->family($actor, $learnerId);
        $end = $start->addDays(6);
        $timezone = app(CurrentSchool::class)->school()->timezone;
        $from = $start->setTimezone($timezone)->startOfDay()->utc();
        $until = $end->setTimezone($timezone)->endOfDay()->utc();
        $marks = MarkReleaseEntry::where('learner_id', $learnerId)->whereBetween('created_at', [$from, $until])->with(['release', 'snapshot'])->orderByDesc('id')->limit(301)->get();
        abort_if($marks->count() > 300, 422, 'This weekly evidence needs a smaller release batch.');
        $latest = $marks->unique(fn ($entry) => $entry->release->subject_id.':'.$entry->release->grade_level_id.':'.$entry->release->period_key);
        $subjects = Subject::withTrashed()->whereIn('id', $latest->map(fn ($entry) => $entry->release->subject_id))->get()->keyBy('id');
        $released = $latest->map(function ($entry) use ($subjects): array {
            $release = $entry->release;
            $snapshot = $entry->snapshot;
            $basis = $release->mode === 'running' ? $snapshot->running_bp : $snapshot->percentage_bp;

            return ['release_id' => (int) $release->id, 'subject' => mb_substr((string) $subjects->get($release->subject_id)?->name, 0, 80), 'percentage_bp' => $basis === null ? null : (int) $basis, 'provisional' => $release->mode === 'running'];
        })->values()->all();
        $entries = AttendanceEntry::where('learner_id', $learnerId)->whereHas('register', fn ($q) => $q->whereNull('timetable_lesson_id')->whereBetween('on_date', [$start->toDateString(), $end->toDateString()]))
            ->with('register')->orderByDesc('id')->limit(101)->get();
        abort_if($entries->count() > 100, 422);
        // A correction is the current canonical entry; homeroom days, not lessons.
        $days = $entries->unique(fn ($entry) => $entry->register->on_date->toDateString());
        $attendance = ['recorded' => $days->count(), 'present' => $days->whereIn('state', ['present', 'late'])->count(), 'absent' => $days->where('state', 'absent')->count(), 'excused' => $days->where('state', 'excused')->count()];
        $gate = app(ModuleGate::class);
        $gate->forget();
        $sets = $gate->allows('daily_practice') ? PracticeDailySet::where('learner_id', $learnerId)->whereBetween('practice_on', [$start->toDateString(), $end->toDateString()])->get(['id', 'completed_at']) : collect();
        $upcomingStart = $end->addDay()->toDateString();
        $upcomingEnd = $end->addDays(7)->toDateString();
        $eventsQuery = Event::whereDoesntHave('multipleEvent')->where('session_year_id', (int) schoolSetting('session_year'))->whereBetween('start_date', [$upcomingStart, $upcomingEnd])->orderBy('start_date');
        $eventCount = (clone $eventsQuery)->count();
        $events = $eventsQuery->limit(3)->get(['id', 'title', 'start_date']);
        $multipleQuery = MultipleEvent::whereBetween('date', [$upcomingStart, $upcomingEnd])->whereHas('events', fn ($q) => $q->where('session_year_id', (int) schoolSetting('session_year')))->with('events')->orderBy('date');
        $eventCount += (clone $multipleQuery)->count();
        $multiple = $multipleQuery->limit(3)->get();
        $calendar = collect([
            ...$events->map(fn ($event) => ['event_id' => (int) $event->id, 'date' => substr((string) $event->start_date, 0, 10), 'title' => mb_substr((string) $event->title, 0, 100)])->all(),
            ...$multiple->map(fn ($day) => ['event_id' => (int) $day->event_id, 'date' => substr((string) $day->date, 0, 10), 'title' => mb_substr((string) $day->events?->title, 0, 100)])->all(),
        ])->unique(fn ($event) => $event['event_id'].':'.$event['date'])->sortBy('date')->values();
        $kind = $sets->contains(fn ($set) => $set->completed_at === null) ? 'practice' : ($released !== [] ? 'marks' : ($attendance['absent'] > 0 ? 'attendance' : 'calendar'));
        $contentVersion = null;
        if ($kind === 'practice') {
            $entries = PracticeSetEntry::whereIn('practice_daily_set_id', $sets->whereNull('completed_at')->pluck('id'))->orderBy('position')->limit(300)->pluck('practice_item_version_id');
            $sources = PracticeItemVersion::whereIn('id', $entries)->pluck('content_version_id');
            $contentVersion = app(ContentPolicy::class)->readableVersions($actor, $learnerId)->whereIn('id', $sources)->orderByDesc('id')->value('id');
        }
        $result = ['marks' => $released, 'attendance' => $attendance, 'practice' => ['total' => $sets->count(), 'completed' => $sets->whereNotNull('completed_at')->count()], 'events' => $calendar->take(3)->all(), 'event_count' => $eventCount, 'action_kind' => $kind, 'content_version_id' => $contentVersion === null ? null : (int) $contentVersion];
        app(DigestAuthority::class)->family($actor, (int) $learner->id);

        return $result;
    }
}
