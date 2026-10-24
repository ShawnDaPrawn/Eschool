<?php

declare(strict_types=1);

namespace App\Services\Academics\Attendance;

use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceNotice;
use App\Models\Academics\AttendanceRegister;
use App\Models\Leave;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RegisterNotices
{
    /** Run in each school's timezone; an unmarked learner is never guessed absent. */
    public function due(): int
    {
        $now = CarbonImmutable::now(app(CurrentSchool::class)->school()?->timezone ?? 'Africa/Johannesburg');
        $settings = app(RegisterSettings::class)->get();
        if (! in_array($now->dayOfWeekIso, $settings['teaching_days'], true) || $now->format('H:i') < $settings['absence_cutoff']) {
            return 0;
        }
        $sent = 0;
        AttendanceRegister::whereDate('on_date', $now->toDateString())->whereNull('timetable_lesson_id')->orderBy('id')->each(function ($register) use (&$sent) {
            $eligible = app(RegisterRosters::class)->learners($register)->pluck('id');
            AttendanceEntry::where('attendance_register_id', $register->id)->where('state', 'absent')->whereIn('learner_id', $eligible)->each(function ($entry) use (&$sent) {
                $sent += $this->entry((int) $entry->id, 'absence');
            });
        });

        return $sent;
    }

    public function entry(int $id, string $kind, ?int $revision = null): int
    {
        return DB::transaction(function () use ($id, $kind, $revision) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $entry = AttendanceEntry::with('register', 'learner.user')->find($id);
            if ($entry === null || ($kind === 'absence' && $entry->register?->timetable_lesson_id !== null) || $entry->learner?->user === null || ($kind === 'absence' && $entry->state !== 'absent') || ($revision !== null && $entry->revision !== $revision)) {
                return 0;
            }
            // Membership, guardianship and current state are checked at delivery, not enqueue.
            if (! app(RegisterRosters::class)->learners($entry->register)->contains('id', $entry->learner_id)) {
                return 0;
            }
            if ($kind === 'absence' && isset(Leave::approvedTypesForDate($entry->register->on_date->toDateString())[$entry->learner->user_id])) {
                return 0;
            }
            $guardians = app(GuardianService::class)->guardianUserIds($entry->learner);
            $notifier = app(Notifier::class);
            $count = 0;
            foreach (User::whereIn('id', $guardians)->get() as $user) {
                $key = ['attendance_entry_id' => $id, 'user_id' => (int) $user->id, 'kind' => $kind, 'revision' => $entry->revision];
                if (AttendanceNotice::where($key)->exists()) {
                    continue;
                }
                $name = trim($entry->learner->user->first_name.' '.$entry->learner->user->last_name);
                $date = $entry->register->on_date->toDateString();
                $title = $kind === 'absence' ? __('Absence alert') : __('Attendance updated');
                $body = $kind === 'absence' ? __(':name was marked absent on :date.', ['name' => $name, 'date' => $date]) : __('Attendance for :name on :date was updated. Open the register to review it.', ['name' => $name, 'date' => $date]);
                $notice = $notifier->makeNotice($kind === 'absence' ? 'attendance.absence' : 'academics.register_amended', $title, $body, '/academics/attendance');
                $notice->delivery_payload = ['link' => '/academics/attendance', 'register_learner_id' => (int) $entry->learner_id, 'register_entry_id' => (int) $entry->id, 'register_entry_revision' => $entry->revision, 'register_notice_kind' => $kind];
                $notice->save();
                AttendanceNotice::create($key + ['notification_id' => (int) $notice->id]);
                $channels = $notifier->send($user, $kind === 'absence' ? 'attendance.absence' : 'academics.register_amended', $title, $body, '/academics/attendance', $notice);
                if ($channels !== []) {
                    $count++;
                }
            }

            return $count;
        });
    }
}
