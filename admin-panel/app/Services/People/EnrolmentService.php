<?php

declare(strict_types=1);

namespace App\Services\People;

use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Services\Academics\AcademicMemberships;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EnrolmentService
{
    public function place(int $learnerId, int $homeroomId, int $yearId, string $startsOn, string $reason, bool $legacyDates = false): Enrolment
    {
        return DB::transaction(function () use ($learnerId, $homeroomId, $yearId, $startsOn, $reason, $legacyDates) {
            $learner = Students::query()->when($legacyDates, fn ($query) => $query->withTrashed())->lockForUpdate()->findOrFail($learnerId);
            $homeroom = ClassSection::query()->when($legacyDates, fn ($query) => $query->withTrashed())->findOrFail($homeroomId);
            $year = SessionYear::query()->when($legacyDates, fn ($query) => $query->withTrashed())->findOrFail($yearId);
            $date = Carbon::parse($startsOn)->toDateString();
            $yearStart = Carbon::parse($year->start_date)->toDateString();
            $yearEnd = Carbon::parse($year->end_date)->toDateString();
            if ($date < $yearStart || $date > $yearEnd) {
                throw ValidationException::withMessages(['starts_on' => 'Choose a date within the school year.']);
            }
            $current = Enrolment::query()->where('learner_id', $learner->id)->onDate($date)->lockForUpdate()->first();
            if ($current !== null && (int) $current->class_section_id === $homeroomId && (int) $current->session_year_id === $yearId) {
                return $current;
            }
            if ($current !== null && $current->starts_on->toDateString() === $date) {
                abort(409, 'An enrolment already starts on this date.');
            }
            $next = Enrolment::query()->where('learner_id', $learner->id)->whereDate('starts_on', '>', $date)->orderBy('starts_on')->first();
            $endsOn = $next !== null ? min($yearEnd, $next->starts_on->subDay()->toDateString()) : $yearEnd;
            if ($current !== null) {
                $current->update(['ends_on' => Carbon::parse($date)->subDay()->toDateString()]);
            }
            $entry = Enrolment::query()->create([
                'learner_id' => $learner->id, 'class_section_id' => $homeroom->id, 'session_year_id' => $year->id,
                'starts_on' => $date, 'ends_on' => $endsOn, 'reason' => $reason,
                'previous_enrolment_id' => $current?->id, 'legacy_dates' => $legacyDates,
            ]);
            app(StudentNumbers::class)->assign($learner);
            if (! $legacyDates && Schema::hasTable('teaching_group_memberships')) {
                app(AcademicMemberships::class)->reconcileLearner((int) $learner->id, $date);
            }
            $this->syncProjection((int) $learner->id, (int) $year->id);

            return $entry;
        });
    }

    /** A legacy edit appends history; existing history is never rewritten. */
    public function fromLegacy(StudentSessions $session): void
    {
        if (app(CurrentSchool::class)->isMigrating() || ! $session->class_section_id || ! $session->session_year_id) {
            return;
        }
        $year = SessionYear::query()->withTrashed()->find($session->session_year_id);
        if ($year === null) {
            return;
        }
        $known = Enrolment::query()->where('learner_id', $session->student_id)->where('session_year_id', $year->id)->exists();
        $oldRoom = $session->getRawOriginal('class_section_id');
        if (! $known && ! $session->wasRecentlyCreated && $session->wasChanged('class_section_id') && $oldRoom) {
            $this->place((int) $session->student_id, (int) $oldRoom, (int) $year->id, (string) $year->start_date, 'Imported previous class at known year boundary', true);
            $known = true;
        }
        $date = $known ? max((string) $year->start_date, min(now()->toDateString(), (string) $year->end_date)) : (string) $year->start_date;
        $this->place((int) $session->student_id, (int) $session->class_section_id, (int) $year->id, $date, 'Legacy class assignment', ! $known);
    }

    /** Physical compatibility projection: existing v1 routes and fields stay. */
    public function syncProjection(int $learnerId, int $yearId): void
    {
        $today = now(app(CurrentSchool::class)->school()?->timezone ?? config('app.timezone'))->toDateString();
        $entries = Enrolment::query()->where('learner_id', $learnerId)->where('session_year_id', $yearId);
        $entry = (clone $entries)->onDate($today)->first()
            ?? (clone $entries)->whereDate('starts_on', '>', $today)->orderBy('starts_on')->first()
            ?? $entries->orderByDesc('starts_on')->first();
        if ($entry === null) {
            return;
        }
        StudentSessions::withoutEvents(fn () => StudentSessions::query()->updateOrCreate(
            ['student_id' => $learnerId, 'session_year_id' => $yearId], ['class_section_id' => $entry->class_section_id],
        ));
        if ((int) schoolSetting('session_year') === $yearId && $entry->starts_on->toDateString() <= $today) {
            Students::query()->whereKey($learnerId)->update(['class_section_id' => $entry->class_section_id]);
        }
    }

    public function classOn(int $learnerId, string $date): ?int
    {
        $value = Enrolment::query()->where('learner_id', $learnerId)->onDate($date)->value('class_section_id');

        return $value === null ? null : (int) $value;
    }
}
