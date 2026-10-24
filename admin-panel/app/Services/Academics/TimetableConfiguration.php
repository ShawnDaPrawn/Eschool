<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\BellPeriod;
use App\Models\Academics\BellSchedule;
use App\Models\Academics\Room;
use App\Models\Academics\TimetableLesson;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TimetableConfiguration
{
    public static function roomRules(): array
    {
        return ['name' => ['required', 'string', 'max:191'], 'building' => ['nullable', 'string', 'max:191'],
            'capacity' => ['nullable', 'integer', 'between:1,5000'], 'type' => ['required', 'string', 'max:64'], 'active' => ['required', 'boolean']];
    }

    public static function bellRules(): array
    {
        return ['name' => ['required', 'string', 'max:191'], 'section' => ['required', 'string', 'max:100'], 'session_year_id' => ['required', 'integer', 'min:1'],
            'periods' => ['required', 'array', 'min:1', 'max:30'], 'periods.*.name' => ['required', 'string', 'max:100'],
            'periods.*.starts_at' => ['required', 'date_format:H:i'], 'periods.*.ends_at' => ['required', 'date_format:H:i'], 'periods.*.is_break' => ['required', 'boolean']];
    }

    public function lock(User $actor): void
    {
        School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
        app(AcademicAuthority::class)->manage($actor);
    }

    public function room(User $actor, array $input, ?int $id = null): Room
    {
        app(AcademicAuthority::class)->manage($actor);
        $rules = self::roomRules() + ($id === null ? [] : ['revision' => ['required', 'integer', 'min:1']]);
        $data = Validator::make($input, $rules)->validate() + ['capacity' => null, 'building' => null];

        return DB::transaction(function () use ($actor, $data, $id) {
            $this->lock($actor);
            $room = $id === null ? new Room : Room::query()->lockForUpdate()->findOrFail($id);
            abort_if($id !== null && $room->revision !== $data['revision'], 409, __('This room changed. Reload before saving.'));
            if (! $data['active'] && $room->exists) {
                abort_if(TimetableLesson::where('room_id', $id)->where('active', true)->whereDate('ends_on', '>=', app(AcademicAuthority::class)->today())->exists(), 409, __('Move the room’s future lessons before deactivating it.'));
            }
            if ($room->exists && $data['capacity'] !== null && ($room->capacity === null || $data['capacity'] < $room->capacity)) {
                foreach (TimetableLesson::with('period')->where('room_id', $id)->where('active', true)->whereDate('ends_on', '>=', app(AcademicAuthority::class)->today())->get() as $lesson) {
                    foreach (app(TimetableEngine::class)->dates($lesson->getAttributes()) as $date) {
                        if ($date >= app(AcademicAuthority::class)->today() && count(app(TimetableEngine::class)->learners($lesson->getAttributes(), $date)) > $data['capacity']) {
                            throw ValidationException::withMessages(['capacity' => __('The room is too small for an existing lesson.')]);
                        }
                    }
                }
            }
            $before = $room->exists ? $room->getAttributes() : [];
            unset($data['revision']);
            $room->fill($data);
            $room->revision = $id === null ? 1 : $room->revision + 1;
            $room->save();
            Audit::record('academics.room_saved', $room, $before, $room->getAttributes());

            return $room;
        }, 3);
    }

    public function bells(User $actor, array $input, ?int $id = null): BellSchedule
    {
        app(AcademicAuthority::class)->manage($actor);
        $rules = self::bellRules() + ($id === null ? [] : ['revision' => ['required', 'integer', 'min:1']]);
        $data = Validator::make($input, $rules)->validate();

        return DB::transaction(function () use ($actor, $data, $id) {
            $this->lock($actor);
            SessionYear::findOrFail($data['session_year_id']);
            $schedule = $id === null ? new BellSchedule : BellSchedule::query()->lockForUpdate()->findOrFail($id);
            abort_if($id !== null && $schedule->revision !== $data['revision'], 409, __('This bell schedule changed. Reload before saving.'));
            if ($id !== null) {
                abort_if(TimetableLesson::whereIn('bell_period_id', BellPeriod::where('bell_schedule_id', $id)->select('id'))->exists(), 409, __('Copy a bell schedule already used by lessons, then move the lessons to the new periods.'));
            }
            $last = null;
            foreach ($data['periods'] as $period) {
                if ($period['starts_at'] >= $period['ends_at'] || ($last !== null && $period['starts_at'] < $last)) {
                    throw ValidationException::withMessages(['periods' => __('Periods must be ordered, non-overlapping and finish after they start.')]);
                }
                $last = $period['ends_at'];
            }
            $before = $schedule->exists ? $schedule->load('periods')->toArray() : [];
            $schedule->fill(array_intersect_key($data, array_flip(['name', 'section', 'session_year_id'])));
            $schedule->revision = $id === null ? 1 : $schedule->revision + 1;
            $schedule->save();
            $schedule->periods()->delete();
            foreach ($data['periods'] as $position => $period) {
                $schedule->periods()->create($period + ['position' => $position + 1]);
            }
            Audit::record('academics.bells_saved', $schedule, $before, $schedule->load('periods')->toArray());

            return $schedule;
        }, 3);
    }
}
