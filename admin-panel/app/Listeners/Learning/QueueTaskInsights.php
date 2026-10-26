<?php

declare(strict_types=1);

namespace App\Listeners\Learning;

use App\Jobs\Learning\RefreshTaskInsights;
use App\Models\Academics\TeachingGroup;
use App\Models\Platform\OutboxEvent;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class QueueTaskInsights implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id !== null && isset($event->payload['group_id']) && in_array($event->type, ['academics.marks_changed', 'academics.assignment_changed'], true)) {
            app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
                $gate = app(ModuleGate::class);
                $gate->forget();
                if ($gate->allows('test_insights') && TeachingGroup::whereKey((int) $event->payload['group_id'])->where('active', true)->where('session_year_id', schoolSetting('session_year'))->exists()) {
                    RefreshTaskInsights::dispatch((int) $event->school_id, (int) $event->payload['group_id']);
                }
            });
        }
    }
}
