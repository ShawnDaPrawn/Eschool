<?php

declare(strict_types=1);

namespace App\Services\Live;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityOperation;
use App\Models\Consultations\Consultation;
use App\Models\Platform\OutboxEvent;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\HousePoint;
use App\Services\Consultations\ConsultationAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\HousePoints;
use App\Services\Recognition\RecognitionAudience;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Server-only source references. Never route personal changes through shared channels. */
class LivePublisher
{
    public function event(int $id): void
    {
        abort_unless(DB::transactionLevel() === 0, 409);
        if (! app(LiveConfiguration::class)->ready()) {
            return;
        }
        $school = app(CurrentSchool::class)->id();
        $event = OutboxEvent::where('school_id', $school)->findOrFail($id);
        if (! in_array($event->type, LiveDispatch::EVENTS, true)) {
            return;
        }
        $source = $this->source($event);
        if ($source === null) {
            return;
        }
        $group = $event->type === 'activity.group_changed' ? $source['group_id'] : null;
        foreach (app(LiveLeases::class)->active($group) as $lease) {
            $actor = app(LiveLeases::class)->actor($lease);
            if ($actor === null) {
                continue;
            }
            try {
                if ($event->type === 'house_points.changed') {
                    app(HousePoints::class)->counters($actor, $source['session_year_id']);
                } elseif ($event->type === 'recognition.changed') {
                    $module = $source['kind'] === 'point' ? 'house_points' : 'awards';
                    if (! in_array($source['learner_id'], app(RecognitionAudience::class)->learners($actor, $module), true)) {
                        continue;
                    }
                } elseif ($event->type === 'consultations.counts_changed') {
                    $row = Consultation::findOrFail($source['consultation_id']);
                    if ($row->results_visibility !== 'public_live' || ! app(ConsultationAccess::class)->eligible($actor, $row)) {
                        continue;
                    }
                } elseif ($event->type === 'activity.group_changed') {
                    app(LiveAuthority::class)->group($actor, $source['group_id']);
                } else {
                    continue;
                }
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
            // The physical destination is specific to this still-valid credential lease.
            Broadcast::connection('reverb')->broadcast([LiveChannels::name($school, (int) $actor->id, $lease['id'], $group)], 'navigateed.invalidate', $source);
        }
    }

    public function presence(int $group): array
    {
        abort_unless(DB::transactionLevel() === 0 && app(LiveConfiguration::class)->ready(), 503);
        $school = app(CurrentSchool::class)->id();
        $authorized = [];
        $users = [];
        foreach (app(LiveLeases::class)->active($group) as $lease) {
            $actor = app(LiveLeases::class)->actor($lease);
            if ($actor === null) {
                continue;
            }
            try {
                app(LiveAuthority::class)->group($actor, $group);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
            $authorized[] = $lease;
            $users[(int) $actor->id] = true;
        }
        $key = 'ne.live.presence.revision.'.$school.'.'.$group;
        $revision = Cache::lock($key.'.lock', 5)->block(2, function () use ($key) {
            $next = max((int) floor(microtime(true) * 1000), (int) Cache::get($key, 0) + 1);
            Cache::put($key, $next, 60);

            return $next;
        });
        $data = ['type' => 'group.presence', 'school_id' => $school, 'group_id' => $group, 'revision' => $revision, 'online_count' => count($users)];
        foreach ($authorized as $lease) {
            Broadcast::connection('reverb')->broadcast([LiveChannels::name($school, $lease['user_id'], $lease['id'], $group)], 'navigateed.invalidate', $data);
        }

        return $data;
    }

    private function source(OutboxEvent $event): ?array
    {
        $data = $event->payload;
        $school = app(CurrentSchool::class)->id();
        $revision = $data['revision'] ?? null;
        if (! is_int($revision) || $revision < 1) {
            return null;
        }
        if ($event->type === 'activity.group_changed') {
            $group = ActivityGroup::find($data['group_id'] ?? 0);
            if ($group === null || ! ActivityOperation::whereKey($revision)->exists()) {
                return null;
            }

            return ['type' => $event->type, 'school_id' => $school, 'group_id' => (int) $group->id, 'revision' => $revision];
        }
        if ($event->type === 'house_points.changed') {
            $point = HousePoint::whereKey($revision)->where('session_year_id', $data['session_year_id'] ?? 0)->where('house_id', $data['house_id'] ?? 0)->first();

            return $point === null ? null : ['type' => $event->type, 'school_id' => $school, 'session_year_id' => (int) $point->session_year_id, 'house_id' => (int) $point->house_id, 'revision' => $revision];
        }
        if ($event->type === 'recognition.changed') {
            $kind = $data['kind'] ?? null;
            $row = match ($kind) {
                'point' => HousePoint::find($data['source_id'] ?? 0),
                'award' => AwardPresentation::find($data['source_id'] ?? 0),
                default => null,
            };
            if ($row === null || ($kind === 'award' && (int) $row->revision !== $revision) || ($kind === 'point' && $revision !== 1)) {
                return null;
            }

            return ['type' => $event->type, 'school_id' => $school, 'session_year_id' => (int) $row->session_year_id, 'learner_id' => (int) $row->learner_id, 'source_id' => (int) $row->id, 'kind' => $kind, 'revision' => $revision];
        }
        if ($event->type === 'consultations.counts_changed') {
            $row = Consultation::find($data['consultation_id'] ?? 0);
            if ($row === null || $row->results_visibility !== 'public_live') {
                return null;
            }

            return ['type' => $event->type, 'school_id' => $school, 'consultation_id' => (int) $row->id, 'revision' => $revision];
        }

        return null;
    }
}
