<?php

declare(strict_types=1);

namespace App\Listeners\Academics;

use App\Models\Academics\MarkRelease;
use App\Models\Notification;
use App\Models\Platform\OutboxEvent;
use App\Models\Students;
use App\Services\Platform\Events\OutboxListener;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;

class NotifyMarksReleased implements OutboxListener
{
    public function handle(OutboxEvent $event): void
    {
        if ($event->school_id === null) {
            return;
        }
        app(CurrentSchool::class)->run((int) $event->school_id, fn () => $this->notify($event));
    }

    private function notify(OutboxEvent $event): void
    {
        $release = MarkRelease::findOrFail($event->payload['release_id']);
        $notifier = app(Notifier::class);
        foreach ($release->entries()->get() as $entry) {
            $learner = Students::find($entry->learner_id);
            if ($learner === null) {
                continue;
            }
            $key = 'marks-release:'.$release->id.':'.$learner->id;
            $notice = Notification::where('source_key', $key)->first();
            if ($notice === null) {
                $notice = $notifier->makeNotice('academics.marks_released', __('Marks released'),
                    $release->mode === 'running' ? __('A provisional running mark is available.') : __('Released academic results are available.'), '/academics/marks');
                $notice->source_key = $key;
                $notice->save();
            }
            $ids = app(GuardianService::class)->guardianUserIds($learner)->push((int) $learner->user_id)->unique()->all();
            $notifier->assign($notice, $ids);
        }
    }
}
