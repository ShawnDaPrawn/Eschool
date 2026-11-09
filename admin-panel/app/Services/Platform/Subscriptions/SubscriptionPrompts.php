<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions;

use App\Models\Platform\School;
use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Models\Platform\Subscriptions\LearnerSubscriptionPrompt;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Gentle prompts to guardians about learning plans (Action Plan 3.20),
 * through the notification rules: type subscription.prompt is off by default
 * on every channel, so nothing is sent until the school (in its notification
 * rules) or the guardian switches it on.
 *
 * Only where the school asks for an entitlement and the tutor is on, for an
 * approved child who could use the tutor (by stage) and has no learning plan
 * and no request waiting. Per guardian and child, at most once every
 * prompts.every_days days; sooner (but not within prompts.min_gap_days) when
 * a released result moved the child into the support band since the last
 * prompt. One notice per guardian per run, whatever the number of children.
 *
 * The notice is the same whatever the reason: it names no child, no subject
 * and no mark.
 */
class SubscriptionPrompts
{
    public const TYPE = 'subscription.prompt';

    public const LINK = '/subscriptions';

    /** @return array{schools: int, guardians: int, prompts: int} */
    public function run(): array
    {
        $totals = ['schools' => 0, 'guardians' => 0, 'prompts' => 0];
        School::query()->active()->orderBy('id')->each(function (School $school) use (&$totals): void {
            $done = app(CurrentSchool::class)->run((int) $school->id, fn (): ?array => $this->school());
            if ($done !== null) {
                $totals['schools']++;
                $totals['guardians'] += $done['guardians'];
                $totals['prompts'] += $done['prompts'];
            }
        });

        return $totals;
    }

    /** @return array{guardians: int, prompts: int}|null null when the school does not prompt */
    public function school(): ?array
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        $entitlements = app(Entitlements::class);
        $entitlements->forget();
        if (! $entitlements->required() || ! $gate->allows('tutor')) {
            return null;
        }
        $now = CarbonImmutable::now();
        $everyDays = max(1, (int) config('subscriptions.prompts.every_days', 30));
        $gapDays = max(1, (int) config('subscriptions.prompts.min_gap_days', 7));
        $waiting = LearnerSubscription::query()->where('status', LearnerSubscription::REQUESTED)->whereNotNull('learner_id')->pluck('learner_id')->flip();
        $due = [];
        Students::query()->orderBy('id')->chunkById(max(1, (int) config('subscriptions.prompts.batch', 200)), function ($learners) use (&$due, $waiting, $now, $everyDays, $gapDays, $entitlements): void {
            foreach ($learners as $learner) {
                if ($waiting->has($learner->id) || ! SchoolStages::moduleAllows('tutor', app(SchoolStages::class)->forLearner($learner))
                    || ! app(ApprovalService::class)->approved($learner) || $entitlements->entitled($learner)) {
                    continue;
                }
                foreach (app(GuardianService::class)->guardianUserIds($learner) as $guardianId) {
                    $reason = $this->reason((int) $guardianId, (int) $learner->id, $now, $everyDays, $gapDays);
                    if ($reason !== null) {
                        $due[(int) $guardianId][(int) $learner->id] = $reason;
                    }
                }
            }
        });
        $sent = ['guardians' => 0, 'prompts' => 0];
        foreach ($due as $guardianId => $children) {
            $guardian = User::query()->where('status', 1)->find($guardianId);
            if ($guardian === null || ! $this->wanted($guardian)) {
                continue;
            }
            $notice = DB::transaction(function () use ($guardianId, $children, $now) {
                $today = app(AcademicAuthority::class)->today();
                // The daily unique key makes a second run the same day send nothing.
                if (LearnerSubscriptionPrompt::query()->where('guardian_user_id', $guardianId)->whereIn('learner_id', array_keys($children))->where('sent_on', $today)->lockForUpdate()->exists()) {
                    return null;
                }
                $notifier = app(Notifier::class);
                $notice = $notifier->makeNotice(self::TYPE, __('Extra help with learning'), __('subscription_prompt_message'), self::LINK);
                $notice->setAttribute('delivery_payload', ['link' => self::LINK]);
                $notice->save();
                foreach ($children as $learnerId => $reason) {
                    LearnerSubscriptionPrompt::query()->create(['guardian_user_id' => $guardianId, 'learner_id' => $learnerId, 'reason' => $reason,
                        'notification_id' => $notice->id, 'sent_on' => $today, 'created_at' => $now]);
                }

                return $notice;
            });
            if ($notice !== null) {
                app(Notifier::class)->assign($notice, [$guardianId]);
                $sent['guardians']++;
                $sent['prompts'] += count($children);
            }
        }

        return $sent;
    }

    /** frequency, scores_fell, or null when this guardian was told recently enough. */
    private function reason(int $guardianId, int $learnerId, CarbonImmutable $now, int $everyDays, int $gapDays): ?string
    {
        $last = LearnerSubscriptionPrompt::query()->where('guardian_user_id', $guardianId)->where('learner_id', $learnerId)->max('created_at');
        $last = $last === null ? null : CarbonImmutable::parse((string) $last);
        $since = $last ?? $now->subDays($everyDays);
        $fell = DB::table('progress_band_alerts as a')->join('progress_band_states as s', 's.id', '=', 'a.state_id')
            ->where('a.school_id', app(CurrentSchool::class)->id())->where('s.school_id', app(CurrentSchool::class)->id())
            ->where('s.learner_id', $learnerId)->where('s.audience', 'family')->where('a.band', 'support')->where('a.created_at', '>', $since)->exists();
        if ($fell && ($last === null || $last->lte($now->subDays($gapDays)))) {
            return 'scores_fell';
        }
        if ($last === null || $last->lte($now->subDays($everyDays))) {
            return 'frequency';
        }

        return null;
    }

    /** Whether any channel would reach this guardian: off by default, on only by the school's rules or the guardian's choice. */
    private function wanted(User $guardian): bool
    {
        $rules = app(NotificationRules::class);
        foreach (NotificationRules::CHANNELS as $channel) {
            if ($rules->allows($guardian, self::TYPE, $channel)) {
                return true;
            }
        }

        return false;
    }
}
