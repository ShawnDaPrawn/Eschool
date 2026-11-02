<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\TimetableLesson;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\PushDevices;
use App\Services\Platform\Notifications\PushQueue;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

/**
 * Silent timetable.refresh pushes (Action Plan 1.9). When lessons change, the
 * devices of the people they affect are told to fetch their timetable again
 * and reschedule their next-lesson reminders. The push shows nothing and
 * carries only its type and the school id: no lesson, learner or time.
 * Specification: engineering/specs/platform/rich-push.md.
 */
class TimetableRefreshPush
{
    public const TYPE = 'timetable.refresh';

    /**
     * Queues one silent push per device of each affected learner, their
     * current guardians and the lessons' teachers, in the current school.
     *
     * A lesson moved to another teacher, group or homeroom also refreshes the
     * people of its previous placement, named by ids that resolve only within
     * the current school.
     *
     * @param  list<int>  $lessonIds
     * @param  list<array<string, mixed>>  $previous  placements the lessons moved away from
     * @return int the pushes queued
     */
    public function queue(array $lessonIds, array $previous = []): int
    {
        if (! config('platform.rich_push.timetable_refresh') || $lessonIds === []) {
            return 0;
        }
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools(), 403);
        $schoolId = $school->id();
        $today = CarbonImmutable::now($school->school()?->timezone ?? 'Africa/Johannesburg')->toDateString();
        $engine = app(TimetableEngine::class);
        // School-scoped: another school's lesson ids find nothing here.
        $lessons = TimetableLesson::query()->whereIn('id', array_map('intval', $lessonIds))->get();
        $learners = [];
        $teachers = [];
        $placements = $lessons->map(fn (TimetableLesson $lesson) => TimetableEngine::placement($lesson->getAttributes()))->all();
        // Only previous placements of lessons that exist in this school count.
        if ($lessons->isNotEmpty()) {
            foreach ($previous as $placement) {
                $placements[] = is_array($placement) ? TimetableEngine::placement($placement) : null;
            }
        }
        foreach (array_filter($placements) as $placement) {
            $teachers[] = $placement['teacher_id'];
            foreach ($engine->learners($placement, $today) as $learner) {
                $learners[$learner] = true;
            }
        }
        $users = Teacher::query()->whereIn('id', array_unique($teachers))->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        foreach (Students::query()->whereIn('id', array_keys($learners))->get() as $learner) {
            $users[] = (int) $learner->user_id;
            array_push($users, ...app(GuardianService::class)->guardianUserIds($learner)->all());
        }
        $members = SchoolMembership::query()->active()->where('school_id', $schoolId)->whereIn('user_id', array_unique(array_filter($users)))->pluck('user_id');
        $queued = 0;
        foreach (User::query()->whereIn('id', $members)->where('status', 1)->get() as $user) {
            foreach (app(PushDevices::class)->destinations($user) as $device) {
                PushQueue::toDevice(self::TYPE, (int) $user->id, $device['token'], ['message' => self::message($device['token'], $device['platform'], $schoolId)]);
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * Data only, no notification block. iOS: a background push at low
     * priority; Android: a normal-priority data message.
     *
     * @return array<string, mixed>
     */
    public static function message(string $token, string $platform, int $schoolId): array
    {
        $message = ['token' => $token, 'data' => ['type' => self::TYPE, 'school_id' => (string) $schoolId]];
        if ($platform === 'ios') {
            $message['apns'] = ['headers' => ['apns-push-type' => 'background', 'apns-priority' => '5'], 'payload' => ['aps' => ['content-available' => 1]]];
        } else {
            $message['android'] = ['priority' => 'normal'];
        }

        return $message;
    }
}
