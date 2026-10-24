<?php

declare(strict_types=1);

namespace App\Services\Academics\Attendance;

use App\Models\Academics\AttendanceEntry;
use App\Models\ClassSection;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Academics\RegisterPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Academics\TimetableQueries;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RegisterQueries
{
    public function choices(User $actor, ?string $date = null, ?int $yearId = null): array
    {
        $authority = app(AcademicAuthority::class);
        $authority->staff($actor);
        $date ??= $authority->today();
        $years = SessionYear::whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->orderByDesc('default')->orderByDesc('id')->get();
        $year = $yearId === null ? $years->first() : $years->firstWhere('id', $yearId);
        abort_if($year === null, 404, __('No school year covers the register date.'));
        $homerooms = [];
        $rosters = app(RegisterRosters::class);
        foreach (ClassSection::with('class', 'section')->get() as $room) {
            if ($room->class?->grade_level_id === null) {
                continue;
            }
            try {
                $target = $rosters->target(['session_year_id' => $year->id, 'on_date' => $date, 'class_section_id' => $room->id]);
                app(RegisterPolicy::class)->authorise($actor, $target);
                $homerooms[] = ['id' => (int) $room->id, 'name' => trim($room->class->name.' '.$room->section->name)];
            } catch (HttpExceptionInterface $error) {
                if ($error->getStatusCode() !== 403) {
                    throw $error;
                }
            }
        }
        $periods = [];
        foreach (app(TimetableQueries::class)->week($actor, (int) $year->id, $date)['lessons'] as $lesson) {
            if ($lesson['date'] !== $date) {
                continue;
            }
            try {
                $target = $rosters->target(['session_year_id' => $year->id, 'on_date' => $date, 'timetable_lesson_id' => $lesson['id']]);
                app(RegisterPolicy::class)->authorise($actor, $target);
                $periods[] = ['id' => $lesson['id'], 'name' => $lesson['starts_at'].' · '.$lesson['title'].' · '.$lesson['room_name']];
            } catch (HttpExceptionInterface $error) {
                if ($error->getStatusCode() !== 403) {
                    throw $error;
                }
            }
        }

        return ['on_date' => $date, 'session_year_id' => (int) $year->id, 'school_years' => $years->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name])->all(), 'homerooms' => $homerooms, 'periods' => $periods,
            'can_manage' => $authority->manages($actor), 'settings' => app(RegisterSettings::class)->get()];
    }

    public function today(User $actor, int $learnerId): array
    {
        app(ReleasedMarks::class)->learner($actor, $learnerId);
        $date = app(AcademicAuthority::class)->today();
        $entries = AttendanceEntry::with('register')->where('learner_id', $learnerId)->whereHas('register', fn ($q) => $q->whereDate('on_date', $date))->get();

        return ['learner_id' => $learnerId, 'on_date' => $date, 'entries' => $entries->map(fn ($entry) => ['register_id' => (int) $entry->attendance_register_id,
            'label' => $entry->register->label, 'kind' => $entry->register->timetable_lesson_id === null ? 'homeroom' : 'period', 'state' => $entry->state, 'source' => $entry->source, 'amended_at' => $entry->amended_at?->toISOString()])->all()];
    }
}
