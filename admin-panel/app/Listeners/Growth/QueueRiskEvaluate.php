<?php

declare(strict_types=1);

namespace App\Listeners\Growth;

use App\Jobs\Growth\EvaluateRisk;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\MarkRelease;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class QueueRiskEvaluate implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null) {
            return;
        }
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            $gate = app(ModuleGate::class);
            $gate->forget();
            if (! $gate->allows('early_warning')) {
                return;
            }
            $row = null;
            if (isset($event->payload['group_id'])) {
                $row = TeachingGroup::find($event->payload['group_id']);
            } elseif (isset($event->payload['release_id'])) {
                $row = MarkRelease::find($event->payload['release_id']);
            } elseif (isset($event->payload['register_id'])) {
                $row = AttendanceRegister::find($event->payload['register_id']);
            } elseif (isset($event->payload['grade_level_id'], $event->payload['session_year_id'])) {
                if ((int) $event->payload['session_year_id'] === (int) schoolSetting('session_year')) {
                    EvaluateRisk::dispatch((int) $event->school_id, (int) $event->payload['session_year_id'], (int) $event->payload['grade_level_id']);
                }

                return;
            } elseif (in_array($event->type, ['growth.risk_rules_configured', 'discipline.framework_published'], true) || ($event->type === 'context.changed' && ($event->payload['reason'] ?? null) === 'modules')) {
                foreach (GradeLevel::pluck('id') as $grade) {
                    EvaluateRisk::dispatch((int) $event->school_id, (int) schoolSetting('session_year'), (int) $grade);
                }

                return;
            }
            if ($row !== null && (int) $row->session_year_id === (int) schoolSetting('session_year')) {
                EvaluateRisk::dispatch((int) $event->school_id, (int) $row->session_year_id, (int) $row->grade_level_id);
            }
        });
    }
}
