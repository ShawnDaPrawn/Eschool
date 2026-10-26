<?php

declare(strict_types=1);

namespace App\Services\PeerTutoring;

use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\PeerTutoring\ServiceHour;
use App\Models\Recognition\HousePoint;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\FamilyForms\SchoolFormTime;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\HousePoints;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

class PeerSessions
{
    public static function rules(string $action): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];

        return match ($action) {
            'create' => $operation + ['teaching_group_id' => ['required', 'integer', 'min:1'], 'tutor_learner_id' => ['required', 'integer', 'min:1'],
                'tutee_learner_id' => ['required', 'integer', 'min:1'], 'topic_id' => ['required', 'integer', 'min:1'], 'venue' => ['required', 'string', 'max:191'],
                'starts_at' => ['required', 'date_format:Y-m-d\TH:i'], 'ends_at' => ['required', 'date_format:Y-m-d\TH:i']],
            'complete' => $operation + ['revision' => ['required', 'integer', 'min:1'], 'minutes' => ['required', 'integer', 'between:1,180'],
                'point_category_id' => ['nullable', 'integer', 'min:1'], 'points' => ['nullable', 'integer', 'between:1,10000']],
            'cancel', 'reverse' => $operation + ['revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:1000']],
        };
    }

    private function input(array $input, string $action): array
    {
        abort_if(array_diff(array_keys($input), array_keys(self::rules($action))) !== [], 422);
        $data = Validator::make($input, self::rules($action))->validate();
        foreach (['revision', 'minutes', 'teaching_group_id', 'tutor_learner_id', 'tutee_learner_id', 'topic_id', 'point_category_id', 'points'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = (int) $data[$key];
            }
        }

        return $data;
    }

    public function create(User $actor, array $input): PeerTutoringSession
    {
        $data = $this->input($input, 'create');
        $group = app(PeerAccess::class)->group($actor, $data['teaching_group_id'], true);
        $zone = app(CurrentSchool::class)->school()->timezone;
        foreach (['starts_at', 'ends_at'] as $key) {
            $data[$key] = app(SchoolFormTime::class)->utc($data[$key], $zone);
        }
        $start = CarbonImmutable::parse($data['starts_at']);
        $end = CarbonImmutable::parse($data['ends_at']);
        $authorize = function () use ($actor, $data, $zone, $start, $end): void {
            app(PeerAccess::class)->group($actor, $data['teaching_group_id'], true, $start->setTimezone($zone)->toDateString());
            app(PeerAccess::class)->group($actor, $data['teaching_group_id'], true, $end->setTimezone($zone)->toDateString());
            foreach ([$data['tutor_learner_id'], $data['tutee_learner_id']] as $id) {
                app(PeerMatches::class)->participant($actor, $data['teaching_group_id'], $id, $end->setTimezone($zone)->toDateString());
            }
        };

        return app(PeerOperations::class)->once($actor, 'session.create', $data, (int) $group->id, $authorize, function () use ($actor, $data, $group, $start, $end, $zone) {
            abort_unless($start->isFuture() && $start->lte(now()->addYear()) && $end->gt($start) && $end->lte($start->addHours(3)), 422, 'Choose a future peer session of up to three hours.');
            $year = SessionYear::findOrFail($group->session_year_id);
            abort_unless($start->setTimezone($zone)->toDateString() >= substr((string) $year->start_date, 0, 10)
                && $end->setTimezone($zone)->toDateString() <= substr((string) $year->end_date, 0, 10), 422, 'Use the current school year.');
            app(PeerMatches::class)->selected($actor, (int) $group->id, $data['tutor_learner_id'], $data['tutee_learner_id'], $data['topic_id']);
            $participants = [$data['tutor_learner_id'], $data['tutee_learner_id']];
            abort_if(PeerTutoringSession::where('status', 'scheduled')->where('starts_at', '<', CarbonImmutable::parse($data['ends_at']))
                ->where('ends_at', '>', CarbonImmutable::parse($data['starts_at']))->where(fn ($q) => $q->where('supervisor_user_id', $actor->id)
                ->orWhereIn('tutor_learner_id', $participants)->orWhereIn('tutee_learner_id', $participants))->exists(), 409, 'A supervisor or learner already has a peer session at this time.');
            $row = PeerTutoringSession::create(array_diff_key($data, ['operation_id' => true]) + ['session_year_id' => $group->session_year_id,
                'supervisor_user_id' => $actor->id, 'status' => 'scheduled', 'revision' => 1]);
            Audit::record('peer_tutoring.scheduled', $row, [], ['revision' => 1]);

            return $row;
        });
    }

    public function change(User $actor, string $id, string $action, array $input): PeerTutoringSession
    {
        abort_unless(in_array($action, ['complete', 'cancel', 'reverse'], true), 404);
        $data = $this->input($input, $action);
        $row = PeerTutoringSession::findOrFail($id);
        $authorize = fn () => app(PeerAccess::class)->staffSession($actor, $row->fresh(), $action !== 'cancel');

        return app(PeerOperations::class)->once($actor, 'session.'.$action, $data + ['session_id' => $id], (int) $row->teaching_group_id, $authorize, function () use ($actor, $id, $action, $data) {
            $row = PeerTutoringSession::lockForUpdate()->findOrFail($id);
            abort_unless($row->revision === $data['revision'], 409, 'Refresh this peer session before changing it.');
            if ($action === 'complete') {
                abort_unless($row->status === 'scheduled' && $row->ends_at->lte(now()), 409, 'Only a finished scheduled session can be confirmed.');
                $maximum = (int) $row->starts_at->diffInMinutes($row->ends_at);
                abort_unless($data['minutes'] <= $maximum, 422, 'Verified service cannot exceed the scheduled duration.');
                foreach ([$row->tutor_learner_id, $row->tutee_learner_id] as $learner) {
                    app(PeerMatches::class)->participant($actor, (int) $row->teaching_group_id, (int) $learner);
                }
                abort_unless(isset($data['points']) === isset($data['point_category_id']), 422, 'Choose both a point category and points, or neither.');
                if (isset($data['points'])) {
                    $point = app(HousePoints::class)->issue($actor, ['operation_id' => $data['operation_id'], 'learner_id' => (int) $row->tutor_learner_id,
                        'session_year_id' => (int) $row->session_year_id, 'point_category_id' => $data['point_category_id'], 'points' => $data['points'],
                        'reason' => 'Supervised peer tutoring service', 'public' => false]);
                    $row->house_point_id = $point->id;
                }
                ServiceHour::create(['peer_tutoring_session_id' => $id, 'learner_id' => $row->tutor_learner_id, 'verified_by' => $actor->id,
                    'kind' => 'earned', 'minutes' => $data['minutes']]);
                $row->verified_minutes = $data['minutes'];
                $row->status = 'completed';
            } elseif ($action === 'cancel') {
                abort_unless($row->status === 'scheduled', 409, 'Only a scheduled session can be cancelled.');
                $row->status = 'cancelled';
                $row->correction_reason = $data['reason'];
            } else {
                abort_unless($row->status === 'completed', 409, 'Only a completed session can be corrected.');
                if ($row->house_point_id !== null && ! HousePoint::where('reverses_id', $row->house_point_id)->exists()) {
                    app(HousePoints::class)->reverse($actor, (int) $row->house_point_id, ['operation_id' => $data['operation_id'], 'reason' => $data['reason']]);
                }
                ServiceHour::create(['peer_tutoring_session_id' => $id, 'learner_id' => $row->tutor_learner_id, 'verified_by' => $actor->id,
                    'kind' => 'reversed', 'minutes' => -(int) $row->verified_minutes]);
                $row->status = 'reversed';
                $row->correction_reason = $data['reason'];
            }
            $row->revision++;
            $row->save();
            Audit::record('peer_tutoring.'.$action, $row, [], ['revision' => $row->revision]);

            return $row;
        });
    }
}
