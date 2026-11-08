<?php

declare(strict_types=1);

namespace App\Services\Emergency;

use App\Models\Emergency\EmergencyAlert;
use App\Models\Emergency\EmergencyDrill;
use App\Models\User;
use App\Services\FamilyForms\SchoolFormTime;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * The drill log: each drill's type, planned time, actual start and end, and
 * notes. A drill planned here can be run as a drill alert (EmergencyAlerts):
 * the alert's confirmation and all-clear fill in the actual times.
 */
class EmergencyDrills
{
    public const TYPES = ['fire', 'lockdown', 'evacuation', 'shelter', 'other'];

    public static function rules(string $action): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];
        $time = ['nullable', 'date_format:Y-m-d\TH:i'];

        return match ($action) {
            'plan' => $operation + ['type' => ['required', 'string', 'in:'.implode(',', self::TYPES)], 'planned_at' => ['required', 'date_format:Y-m-d\TH:i'], 'notes' => ['nullable', 'string', 'max:2000']],
            'record' => $operation + ['revision' => ['required', 'integer', 'min:1'], 'started_at' => $time, 'ended_at' => $time, 'notes' => ['nullable', 'string', 'max:2000']],
            'cancel' => $operation + ['revision' => ['required', 'integer', 'min:1']],
        };
    }

    public function summary(EmergencyDrill $drill): array
    {
        return ['id' => $drill->id, 'type' => $drill->type, 'status' => $drill->status, 'planned_at' => $drill->planned_at->toIso8601String(),
            'started_at' => $drill->started_at?->toIso8601String(), 'ended_at' => $drill->ended_at?->toIso8601String(), 'notes' => $drill->notes,
            'alert_id' => $drill->alert_id, 'recorded_by' => (int) $drill->recorded_by, 'revision' => $drill->revision];
    }

    public function plan(User $actor, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('plan'));
        $data['planned_at'] = $this->utc($data['planned_at']);
        $authorize = fn () => app(EmergencyAccess::class)->manager($actor);

        return app(EmergencyOperations::class)->once($actor, 'drill.plan', $data, $authorize, function () use ($actor, $data) {
            $drill = EmergencyDrill::create(['type' => $data['type'], 'status' => 'planned', 'planned_at' => $data['planned_at'], 'notes' => $data['notes'] ?? null, 'recorded_by' => $actor->id, 'revision' => 1]);
            EmergencyOperations::event($actor, 'drill.planned', null, $drill->id, ['type' => $drill->type]);

            return $this->summary($drill);
        });
    }

    /** Record what actually happened: the start, the end and notes. */
    public function record(User $actor, string $id, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('record'));
        foreach (['started_at', 'ended_at'] as $key) {
            $data[$key] = ($data[$key] ?? null) === null ? null : $this->utc($data[$key]);
        }
        $authorize = function () use ($actor, $id) {
            app(EmergencyAccess::class)->manager($actor);
            EmergencyDrill::findOrFail($id);
        };

        return app(EmergencyOperations::class)->once($actor, 'drill.record', ['drill_id' => $id] + $data, $authorize, function () use ($actor, $id, $data) {
            $drill = EmergencyDrill::findOrFail($id);
            abort_unless($drill->revision === $data['revision'] && $drill->status !== 'cancelled', 409, 'Refresh the drill log.');
            $alert = $drill->alert_id === null ? null : EmergencyAlert::find($drill->alert_id);
            abort_if($alert?->status === 'active', 409, 'Send the drill\'s all-clear first.');
            $started = $data['started_at'] ?? $drill->started_at?->toIso8601String();
            $ended = $data['ended_at'] ?? $drill->ended_at?->toIso8601String();
            abort_if($started === null || $ended === null, 422, 'Give the time the drill started and ended.');
            abort_unless(strtotime($ended) > strtotime($started), 422, 'The drill must end after it started.');
            abort_if(strtotime($started) > now()->addMinute()->getTimestamp(), 422, 'A drill cannot be recorded before it happens.');
            $drill->forceFill(['status' => 'completed', 'started_at' => $started, 'ended_at' => $ended, 'notes' => $data['notes'] ?? $drill->notes, 'revision' => $drill->revision + 1])->save();
            EmergencyOperations::event($actor, 'drill.recorded', $drill->alert_id, $drill->id, ['type' => $drill->type]);

            return $this->summary($drill);
        });
    }

    public function cancel(User $actor, string $id, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('cancel'));
        $authorize = function () use ($actor, $id) {
            app(EmergencyAccess::class)->manager($actor);
            EmergencyDrill::findOrFail($id);
        };

        return app(EmergencyOperations::class)->once($actor, 'drill.cancel', ['drill_id' => $id] + $data, $authorize, function () use ($actor, $id, $data) {
            $drill = EmergencyDrill::findOrFail($id);
            abort_unless($drill->revision === $data['revision'] && $drill->status === 'planned' && $drill->alert_id === null, 409, 'Only a planned drill that has not started can be cancelled.');
            $drill->forceFill(['status' => 'cancelled', 'revision' => $drill->revision + 1])->save();
            EmergencyOperations::event($actor, 'drill.cancelled', null, $drill->id);

            return $this->summary($drill);
        });
    }

    /** Newest first. */
    public function list(int $limit = 50): array
    {
        return EmergencyDrill::orderByDesc('planned_at')->limit($limit)->get()->map(fn (EmergencyDrill $drill) => $this->summary($drill))->all();
    }

    private function utc(string $wall): string
    {
        return app(SchoolFormTime::class)->utc($wall, (string) (app(CurrentSchool::class)->school()?->timezone ?: 'Africa/Johannesburg'));
    }
}
