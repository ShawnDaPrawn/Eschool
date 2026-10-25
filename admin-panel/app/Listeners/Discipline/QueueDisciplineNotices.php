<?php

declare(strict_types=1);

namespace App\Listeners\Discipline;

use App\Models\Platform\OutboxEvent;
use App\Services\Discipline\DisciplineNotices;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class QueueDisciplineNotices implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null) {
            return;
        }
        app(CurrentSchool::class)->run((int) $event->school_id, function () use ($event) {
            app(ModuleGate::class)->forget();
            if (! app(ModuleGate::class)->allows('discipline')) {
                return;
            }
            $source = $event->payload;
            if (! is_int($source['revision'] ?? null) || $source['revision'] < 1) {
                return;
            }
            $notices = app(DisciplineNotices::class);
            if ($event->type === 'discipline.record_changed' && is_int($source['record_id'] ?? null)) {
                $notices->record($source['record_id'], $source['revision']);
            } elseif ($event->type === 'discipline.detention_changed' && is_int($source['session_id'] ?? null)) {
                $notices->detention($source['session_id'], $source['revision']);
            }
        });
    }
}
