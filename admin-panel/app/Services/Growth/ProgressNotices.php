<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\MarkRelease;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Academics\MarkSnapshot;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\ProgressBandAlert;
use App\Models\Growth\ProgressBandState;
use App\Models\Notification;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\MarkDefinitions;
use App\Services\Academics\MarkSnapshots;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** No names, scores, subjects or learner IDs enter notification bodies/payloads. */
class ProgressNotices
{
    public const TYPE = 'progress.band_changed';

    public const STAFF_TYPE = 'progress.staff_band_changed';

    public function group(int $id): void
    {
        app(LearnerNotesPolicy::class)->enabled();
        $group = TeachingGroup::where('active', true)->findOrFail($id);
        foreach (app(MarkDefinitions::class)->plans($group) as $plan) {
            app(MarkSnapshots::class)->recompute($id, (int) $plan->term_id);
            $today = app(AcademicAuthority::class)->today();
            $members = TeachingGroupMembership::where('teaching_group_id', $id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');
            $rows = MarkSnapshot::whereIn('learner_id', $members)->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)
                ->where('session_year_id', $group->session_year_id)->where('period_key', 'term:'.$plan->term_id)->orderByDesc('version')->get()->unique('learner_id');
            foreach ($rows as $snapshot) {
                $alert = $this->observe($snapshot, 'staff', null, $id);
                if ($alert !== null) {
                    $today = app(AcademicAuthority::class)->today();
                    $teachers = TeachingAllocation::where('teaching_group_id', $id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('teacher_id');
                    $this->queue($alert, Teacher::whereIn('id', $teachers)->pluck('user_id')->all());
                }
            }
        }
    }

    public function released(int $id): void
    {
        app(LearnerNotesPolicy::class)->enabled();
        $release = MarkRelease::findOrFail($id);
        if ($release->term_id === null) {
            return; // The feature is a term-running band, never a second annual result.
        }
        foreach (MarkReleaseEntry::where('mark_release_id', $id)->with('snapshot')->get() as $entry) {
            $latest = MarkReleaseEntry::where('learner_id', $entry->learner_id)->whereHas('release', fn ($q) => $q
                ->where('subject_id', $release->subject_id)->where('grade_level_id', $release->grade_level_id)
                ->where('session_year_id', $release->session_year_id)->where('period_key', $release->period_key))->latest('id')->value('id');
            if ((int) $latest !== (int) $entry->id) {
                continue; // An older queued event cannot roll back the observed family band.
            }
            $alert = $this->observe($entry->snapshot, 'family', $id, null);
            $learner = Students::find($entry->learner_id);
            if ($alert !== null && $learner !== null) {
                $this->queue($alert, app(GuardianService::class)->guardianUserIds($learner)->push((int) $learner->user_id)->unique()->all());
            }
        }
    }

    private function observe(MarkSnapshot $snapshot, string $audience, ?int $release, ?int $group): ?ProgressBandAlert
    {
        return DB::transaction(function () use ($snapshot, $audience, $release, $group) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $band = app(ProgressBands::class)->classify($snapshot->running_bp, $snapshot->coverage_bp);
            $scope = ['learner_id' => $snapshot->learner_id, 'subject_id' => $snapshot->subject_id, 'grade_level_id' => $snapshot->grade_level_id,
                'session_year_id' => $snapshot->session_year_id, 'period_key' => $snapshot->period_key, 'audience' => $audience];
            $state = ProgressBandState::where($scope)->lockForUpdate()->first();
            if ($state !== null && $state->band === $band['band']) {
                // A replay retries the existing receipt; a newer same-band source
                // does not manufacture another transition or repeated alert.
                return ProgressBandAlert::where('state_id', $state->id)->where('state_revision', $state->revision)
                    ->where('snapshot_id', $snapshot->id)->where('release_id', $release)->first();
            }
            $state ??= new ProgressBandState($scope);
            $state->band = $band['band'];
            $state->revision = (int) ($state->revision ?? 0) + 1;
            $state->save();
            if (! $band['alerts_eligible']) {
                return null;
            }

            return ProgressBandAlert::create(['state_id' => $state->id, 'state_revision' => $state->revision,
                'snapshot_id' => $snapshot->id, 'release_id' => $release, 'group_id' => $group, 'band' => $band['band'],
                'settings_revision' => $band['settings_revision'], 'created_at' => now()]);
        }, 3);
    }

    private function queue(ProgressBandAlert $alert, array $recipients): void
    {
        $notifier = app(Notifier::class);
        $notice = DB::transaction(function () use ($alert, $notifier) {
            $locked = ProgressBandAlert::lockForUpdate()->findOrFail($alert->id);
            if ($locked->notification_id !== null) {
                return Notification::findOrFail($locked->notification_id);
            }
            $state = ProgressBandState::findOrFail($locked->state_id);
            $type = $state->audience === 'staff' ? self::STAFF_TYPE : self::TYPE;
            $link = $state->audience === 'staff' ? '/learner-notes' : '/growth/progress';
            $notice = $notifier->makeNotice($type, __('Learning progress updated'), __('Open Learning progress to review an available update.'), $link);
            $notice->delivery_payload = ['link' => $link, 'progress_alert_id' => (int) $locked->id];
            $notice->save();
            $locked->notification_id = $notice->id;
            $locked->save();

            return $notice;
        });
        $ids = User::whereIn('id', $recipients)->get()->filter(fn (User $u) => $this->allows($u, $notice->type, $notice->delivery_payload))->pluck('id')->all();
        $notifier->assign($notice, $ids);
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        $rawId = $payload['progress_alert_id'] ?? null;
        if (! in_array($type, [self::TYPE, self::STAFF_TYPE], true) || (! is_int($rawId) && ! is_string($rawId))
            || ! preg_match('/^[1-9][0-9]*$/D', (string) $rawId)
            || filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return false;
        }
        if (($payload['link'] ?? null) !== ($type === self::STAFF_TYPE ? '/learner-notes' : '/growth/progress')) {
            return false;
        }
        try {
            app(LearnerNotesPolicy::class)->enabled();
            $alert = ProgressBandAlert::findOrFail($payload['progress_alert_id']);
            $state = ProgressBandState::findOrFail($alert->state_id);
            if ($type !== ($state->audience === 'staff' ? self::STAFF_TYPE : self::TYPE) || $state->revision !== $alert->state_revision || $state->band !== $alert->band) {
                return false;
            }
            $band = null;
            if ($state->audience === 'staff' && $alert->group_id !== null) {
                $band = app(ProgressBands::class)->staff($actor, (int) $alert->group_id, (int) $state->learner_id, (int) substr($state->period_key, 5));
                if ($band['snapshot_id'] !== (int) $alert->snapshot_id) {
                    return false;
                }
            } elseif ($state->audience === 'family' && $alert->release_id !== null) {
                $released = app(ProgressBands::class)->family($actor, (int) $state->learner_id, (int) $state->session_year_id, (int) substr($state->period_key, 5));
                $band = collect($released['results'])->first(fn ($row) => $row['release_id'] === (int) $alert->release_id && $row['subject_id'] === (int) $state->subject_id && $row['grade_level_id'] === (int) $state->grade_level_id);
            }

            return $band !== null && $band['alerts_eligible'] && $band['band'] === $alert->band && $band['settings_revision'] === $alert->settings_revision;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
