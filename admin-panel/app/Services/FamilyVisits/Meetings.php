<?php

declare(strict_types=1);

namespace App\Services\FamilyVisits;

use App\Models\FamilyVisits\MeetingSlot;
use App\Models\User;
use App\Services\FamilyForms\SchoolFormTime;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Meetings
{
    public static function rules(string $action): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];

        return match ($action) {
            'create' => $operation + ['host_id' => ['required', 'integer', 'min:1'], 'title' => ['required', 'string', 'max:191'], 'venue' => ['required', 'string', 'max:500'],
                'starts_at' => ['required', 'date_format:Y-m-d\TH:i'], 'ends_at' => ['required', 'date_format:Y-m-d\TH:i'], 'booking_closes_at' => ['required', 'date_format:Y-m-d\TH:i']],
            'book', 'unbook' => $operation + ['revision' => ['required', 'integer', 'min:1'], 'learner_id' => ['required', 'integer', 'min:1']],
            'cancel' => $operation + ['revision' => ['required', 'integer', 'min:1']],
        };
    }

    public function summary(MeetingSlot $slot, bool $participant = false): array
    {
        $host = app(VisitAccess::class)->host((int) $slot->host_id);
        $data = ['id' => $slot->id, 'host_id' => (int) $host->id, 'host_name' => $host->full_name, 'title' => $slot->title, 'venue' => $slot->venue,
            'starts_at' => $slot->starts_at->toIso8601String(), 'ends_at' => $slot->ends_at->toIso8601String(), 'booking_closes_at' => $slot->booking_closes_at->toIso8601String(), 'status' => $slot->status, 'revision' => $slot->revision];
        if ($participant) {
            $data += ['learner_id' => null, 'learner_name' => null, 'guardian_user_id' => null];
            if ($slot->learner_id !== null && $slot->guardian_user_id !== null) {
                try {
                    app(VisitAccess::class)->guardian(User::findOrFail($slot->guardian_user_id), (int) $slot->learner_id, attest: true);
                    $data['learner_id'] = (int) $slot->learner_id;
                    $data['learner_name'] = app(VisitAccess::class)->learner((int) $slot->learner_id)->user->full_name;
                    $data['guardian_user_id'] = (int) $slot->guardian_user_id;
                } catch (ModelNotFoundException|\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    // Staff can cancel the occupied slot without restoring a stale private participant.
                }
            }
        }

        return $data;
    }

    private function slot(string $id): MeetingSlot
    {
        $slot = MeetingSlot::findOrFail($id);
        app(VisitAccess::class)->host((int) $slot->host_id);

        return $slot;
    }

    public function create(User $actor, array $input): array
    {
        $data = VisitOperations::input($input, self::rules('create'));
        $zone = app(CurrentSchool::class)->school()->timezone;
        foreach (['starts_at', 'ends_at', 'booking_closes_at'] as $key) {
            $data[$key] = app(SchoolFormTime::class)->utc($data[$key], $zone);
        }
        $start = CarbonImmutable::parse($data['starts_at']);
        $end = CarbonImmutable::parse($data['ends_at']);
        $closes = CarbonImmutable::parse($data['booking_closes_at']);
        abort_unless($start->isFuture() && $start->lte(now()->addYear()) && $end->gt($start) && $end->lte($start->addHours(2)) && $closes->isFuture() && $closes->lte($start), 422, 'Choose a future meeting of up to two hours and an earlier booking deadline.');
        $authorize = function () use ($actor, $data) {
            app(VisitAccess::class)->manager($actor, true);
            app(VisitAccess::class)->host($data['host_id']);
        };

        return app(VisitOperations::class)->once('user', (string) $actor->id, 'meeting.create', $data, $authorize, function () use ($actor, $data) {
            abort_if(MeetingSlot::where('host_id', $data['host_id'])->where('status', '!=', 'cancelled')->where('starts_at', '<', CarbonImmutable::parse($data['ends_at']))->where('ends_at', '>', CarbonImmutable::parse($data['starts_at']))->exists(), 409, 'The host already has a meeting in this time.');
            $slot = MeetingSlot::create(array_diff_key($data, ['operation_id' => true]) + ['created_by' => $actor->id, 'status' => 'open', 'revision' => 1]);

            return $this->summary($slot, true);
        });
    }

    public function book(User $actor, string $id, array $input): array
    {
        $data = VisitOperations::input($input, self::rules('book'));
        $authorize = function () use ($actor, $id, $data) {
            app(VisitAccess::class)->guardian($actor, $data['learner_id'], true);
            $this->slot($id);
        };

        return app(VisitOperations::class)->once('user', (string) $actor->id, 'meeting.book', ['slot_id' => $id] + $data, $authorize, function () use ($actor, $id, $data) {
            $slot = $this->slot($id);
            abort_unless($slot->revision === $data['revision'] && $slot->status === 'open' && $slot->booking_closes_at->isFuture(), 409, 'Refresh the available meeting slots.');
            abort_if(MeetingSlot::where('status', 'booked')->where(fn ($q) => $q->where('learner_id', $data['learner_id'])->orWhere('guardian_user_id', $actor->id))->where('starts_at', '<', $slot->ends_at)->where('ends_at', '>', $slot->starts_at)->exists(), 409, 'This family already has a meeting in that time.');
            $slot->forceFill(['status' => 'booked', 'learner_id' => $data['learner_id'], 'guardian_user_id' => $actor->id, 'revision' => $slot->revision + 1])->save();

            return $this->summary($slot, true);
        });
    }

    public function unbook(User $actor, string $id, array $input): array
    {
        $data = VisitOperations::input($input, self::rules('unbook'));
        $authorize = function () use ($actor, $id, $data) {
            app(VisitAccess::class)->guardian($actor, $data['learner_id'], revocation: true);
            MeetingSlot::findOrFail($id);
        };

        return app(VisitOperations::class)->once('user', (string) $actor->id, 'meeting.unbook', ['slot_id' => $id] + $data, $authorize, function () use ($actor, $id, $data) {
            $slot = MeetingSlot::findOrFail($id);
            abort_unless($slot->revision === $data['revision'] && $slot->status === 'booked' && (int) $slot->learner_id === $data['learner_id'] && (int) $slot->guardian_user_id === (int) $actor->id, 409, 'Refresh your meeting booking.');
            $slot->forceFill(['status' => 'open', 'learner_id' => null, 'guardian_user_id' => null, 'revision' => $slot->revision + 1])->save();

            return ['id' => $slot->id, 'status' => $slot->status, 'revision' => $slot->revision];
        });
    }

    public function cancel(User $actor, string $id, array $input): array
    {
        $data = VisitOperations::input($input, self::rules('cancel'));
        $authorize = function () use ($actor, $id) {
            app(VisitAccess::class)->manager($actor, revocation: true);
            MeetingSlot::findOrFail($id);
        };

        return app(VisitOperations::class)->once('user', (string) $actor->id, 'meeting.cancel', ['slot_id' => $id] + $data, $authorize, function () use ($id, $data) {
            $slot = MeetingSlot::findOrFail($id);
            abort_unless($slot->revision === $data['revision'] && $slot->status !== 'cancelled', 409);
            $slot->forceFill(['status' => 'cancelled', 'revision' => $slot->revision + 1])->save();

            // A stale host/learner must not prevent withdrawing an appointment.
            return ['id' => $slot->id, 'status' => $slot->status, 'revision' => $slot->revision];
        });
    }
}
