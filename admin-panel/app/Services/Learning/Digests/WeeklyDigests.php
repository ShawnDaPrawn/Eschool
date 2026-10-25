<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\Learning\DigestLanguageTemplate;
use App\Models\Learning\DigestPreference;
use App\Models\Learning\WeeklyParentDigest;
use App\Models\Notification;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WeeklyDigests
{
    public function generate(User $parent, int $learnerId, CarbonImmutable $start): ?WeeklyParentDigest
    {
        app(DigestAuthority::class)->enabled(true);
        app(DigestAuthority::class)->family($parent, $learnerId);
        $timezone = app(CurrentSchool::class)->school()->timezone;
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $start = CarbonImmutable::parse($start->toDateString(), $timezone)->startOfDay();
        abort_unless($start->isMonday() && $start->addDays(6)->lt($today) && $start->gte($today->subWeeks((int) config('weekly_digests.retention_weeks'))), 422);

        return DB::transaction(function () use ($parent, $learnerId, $start): ?WeeklyParentDigest {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $learner = app(DigestAuthority::class)->family($parent, $learnerId);
            $old = WeeklyParentDigest::where('recipient_id', $parent->id)->where('learner_id', $learnerId)->whereDate('week_start', $start)->first();
            if ($old !== null) {
                $this->queue($old);

                return $old;
            }
            $language = DigestPreference::where('user_id', $parent->id)->value('language') ?? 'en';
            $template = app(DigestTemplates::class)->approved($language);
            if ($template === null) {
                return null; // Do not invent an unapproved translation or use another language.
            }
            $evidence = app(DigestEvidence::class)->collect($parent, $learnerId, $start);
            $kind = $evidence['action_kind'];
            $action = ['kind' => $kind, 'learner_id' => $learnerId, 'label' => $template->action_templates[$kind], 'content_version_id' => $evidence['content_version_id']];
            $person = User::acrossSchools()->findOrFail($learner->user_id);
            $rendered = app(DigestTemplates::class)->render($template, [
                'learner' => mb_substr((string) $person->full_name, 0, 100), 'week_start' => $start->toDateString(), 'week_end' => $start->addDays(6)->toDateString(),
                'marks_count' => count($evidence['marks']), 'attendance_present' => $evidence['attendance']['present'], 'attendance_absent' => $evidence['attendance']['absent'],
                'practice_completed' => $evidence['practice']['completed'], 'practice_total' => $evidence['practice']['total'], 'event_count' => $evidence['event_count'], 'home_action' => $action['label'],
            ]);
            $digest = WeeklyParentDigest::create(['learner_id' => $learnerId, 'recipient_id' => $parent->id, 'session_year_id' => (int) schoolSetting('session_year'),
                'template_id' => $template->id, 'template_version' => $template->version, 'language' => $language,
                'week_start' => $start, 'week_end' => $start->addDays(6), 'title' => $rendered['title'], 'summary' => $rendered['summary'],
                'snapshot' => $evidence, 'home_action' => $action, 'expires_at' => now()->addWeeks((int) config('weekly_digests.retention_weeks'))]);
            app(DigestAuthority::class)->family($parent, $learnerId);
            $this->queue($digest);

            return $digest;
        }, 3);
    }

    private function queue(WeeklyParentDigest $digest): void
    {
        $parent = User::findOrFail($digest->recipient_id);
        app(DigestAuthority::class)->family($parent, (int) $digest->learner_id);
        $template = DigestLanguageTemplate::whereNotNull('approved_at')->whereNull('revoked_at')->find($digest->template_id);
        if ($template === null || $digest->expires_at->isPast()) {
            return;
        }
        if ($digest->notification_id === null) {
            $notice = app(Notifier::class)->makeNotice('digest.weekly', 'NavigateEd', $template->notice_prompt, '/learning/weekly-digest');
            $notice->delivery_payload = ['link' => '/learning/weekly-digest', 'digest_id' => (int) $digest->id];
            $notice->save();
            $digest->notification_id = $notice->id;
            $digest->save();
        }
        $notice = Notification::findOrFail($digest->notification_id);
        app(Notifier::class)->assign($notice, [(int) $parent->id]);
    }

    public function monday(): array
    {
        app(DigestAuthority::class)->enabled(true);
        $today = CarbonImmutable::now(app(CurrentSchool::class)->school()->timezone);
        if (! $today->isMonday() || $today->hour < (int) config('weekly_digests.local_hour')) {
            return ['created' => 0, 'unavailable' => 0];
        }
        $start = $today->startOfWeek()->subWeek()->startOfDay();
        $counts = ['created' => 0, 'unavailable' => 0];
        Students::orderBy('id')->chunkById(100, function ($learners) use ($start, &$counts): void {
            foreach ($learners as $learner) {
                if (! app(DigestAuthority::class)->currentLearner($learner)) {
                    continue;
                }
                foreach (User::whereIn('id', app(GuardianService::class)->guardianUserIds($learner))->get() as $parent) {
                    try {
                        $existing = WeeklyParentDigest::where('learner_id', $learner->id)->where('recipient_id', $parent->id)->whereDate('week_start', $start)->exists();
                        $digest = $this->generate($parent, (int) $learner->id, $start);
                        if ($digest === null) {
                            $counts['unavailable']++;
                        } elseif (! $existing) {
                            $counts['created']++;
                        }
                    } catch (HttpExceptionInterface|ModelNotFoundException) {
                        $counts['unavailable']++;
                    }
                }
            }
        });

        return $counts;
    }
}
