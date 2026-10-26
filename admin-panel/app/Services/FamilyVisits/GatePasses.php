<?php

declare(strict_types=1);

namespace App\Services\FamilyVisits;

use App\Models\Cards\Card;
use App\Models\Cards\CardScanLog;
use App\Models\FamilyVisits\GatePass;
use App\Models\Students;
use App\Models\User;
use App\Services\Cards\CardAccess;
use App\Services\Cards\CardReaders;
use App\Services\FamilyForms\SchoolFormTime;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GatePasses
{
    public static function rules(string $action): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];

        return match ($action) {
            'request' => $operation + ['learner_id' => ['required', 'integer', 'min:1'], 'collector' => ['required', 'string', 'max:191'], 'reason' => ['required', 'string', 'max:2000'],
                'starts_at' => ['required', 'date_format:Y-m-d\TH:i'], 'ends_at' => ['required', 'date_format:Y-m-d\TH:i']],
            'approve', 'cancel' => $operation + ['revision' => ['required', 'integer', 'min:1']],
            'collect' => $operation + ['revision' => ['required', 'integer', 'min:1'], 'scan_id' => ['required', 'uuid'], 'collector_confirmed' => ['required', 'accepted']],
        };
    }

    public function summary(GatePass $pass): array
    {
        return ['id' => $pass->id, 'learner_id' => (int) $pass->learner_id, 'requested_by' => (int) $pass->requested_by,
            'approved_by' => $pass->approved_by === null ? null : (int) $pass->approved_by,
            'collector' => $pass->collector, 'reason' => $pass->reason, 'starts_at' => $pass->starts_at->toIso8601String(), 'ends_at' => $pass->ends_at->toIso8601String(),
            'status' => $pass->status, 'revision' => $pass->revision, 'collected_at' => $pass->collected_at?->toIso8601String()];
    }

    public function validSource(GatePass $pass): void
    {
        // A stored approval never bypasses fresh guardian/current approved learner policy.
        app(VisitAccess::class)->guardian(User::findOrFail($pass->requested_by), (int) $pass->learner_id, attest: true);
        abort_if(in_array($pass->status, ['approved', 'collected'], true) && $pass->approved_by === null, 403);
        if ($pass->approved_by !== null) {
            app(VisitAccess::class)->manager(User::findOrFail($pass->approved_by));
        }
    }

    public function request(User $actor, array $input): array
    {
        $data = VisitOperations::input($input, self::rules('request'));
        $zone = app(CurrentSchool::class)->school()->timezone;
        foreach (['starts_at', 'ends_at'] as $key) {
            $data[$key] = app(SchoolFormTime::class)->utc($data[$key], $zone);
        }
        $authorize = fn () => app(VisitAccess::class)->guardian($actor, $data['learner_id'], true);

        return app(VisitOperations::class)->once('user', (string) $actor->id, 'gate.request', $data, $authorize, function () use ($actor, $data) {
            $start = CarbonImmutable::parse($data['starts_at']);
            $end = CarbonImmutable::parse($data['ends_at']);
            $rules = app(VisitQueries::class)->gateRules();
            abort_unless($start->gte(now()->startOfMinute()) && $start->lte(now()->addDays($rules['max_future_days'])) && $end->gt($start) && $end->lte($start->addMinutes($rules['max_window_minutes'])), 422, 'Choose a collection window within the school rules.');
            abort_if(GatePass::where('learner_id', $data['learner_id'])->whereIn('status', ['pending', 'approved'])->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists(), 409, 'A collection request already exists in this time.');
            $pass = GatePass::create(array_diff_key($data, ['operation_id' => true]) + ['requested_by' => $actor->id, 'status' => 'pending', 'revision' => 1]);

            return $this->summary($pass);
        });
    }

    public function approve(User $actor, string $id, array $input): array
    {
        $data = VisitOperations::input($input, self::rules('approve'));
        $authorize = function () use ($actor, $id) {
            app(VisitAccess::class)->manager($actor, true);
            $pass = GatePass::findOrFail($id);
            $this->validSource($pass);
            abort_if((int) $pass->requested_by === (int) $actor->id, 403, 'Another authorised staff member must approve collection.');
        };

        return app(VisitOperations::class)->once('user', (string) $actor->id, 'gate.approve', ['pass_id' => $id] + $data, $authorize, function () use ($actor, $id, $data) {
            $pass = GatePass::findOrFail($id);
            abort_unless($pass->revision === $data['revision'] && $pass->status === 'pending' && $pass->ends_at->isFuture(), 409, 'Refresh the pending collection request.');
            $pass->forceFill(['status' => 'approved', 'approved_by' => $actor->id, 'revision' => $pass->revision + 1])->save();

            return $this->summary($pass);
        });
    }

    public function cancel(User $actor, string $id, array $input): array
    {
        $data = VisitOperations::input($input, self::rules('cancel'));
        $authorize = function () use ($actor, $id) {
            $pass = GatePass::findOrFail($id);
            if ((int) $pass->requested_by === (int) $actor->id) {
                app(VisitAccess::class)->guardian($actor, (int) $pass->learner_id, revocation: true);
            } else {
                app(VisitAccess::class)->manager($actor, revocation: true);
            }
        };

        return app(VisitOperations::class)->once('user', (string) $actor->id, 'gate.cancel', ['pass_id' => $id] + $data, $authorize, function () use ($id, $data) {
            $pass = GatePass::findOrFail($id);
            abort_unless($pass->revision === $data['revision'] && in_array($pass->status, ['pending', 'approved'], true), 409, 'Refresh the collection request.');
            $pass->forceFill(['status' => 'cancelled', 'revision' => $pass->revision + 1])->save();

            return ['id' => $pass->id, 'status' => $pass->status, 'revision' => $pass->revision];
        });
    }

    private function scan(string $credential, string $id): array
    {
        $device = app(CardReaders::class)->authenticate($credential, 'gate', true);
        app(VisitAccess::class)->actor(User::findOrFail($device->issued_by), revocation: true);
        $scan = CardScanLog::where('device_id', $device->id)->where('outcome', 'accepted')->whereIn('method', ['qr', 'sun'])->findOrFail($id);
        $age = app(VisitQueries::class)->gateRules()['scan_max_age_seconds'];
        abort_unless($scan->scanned_at->lte(now()) && $scan->scanned_at->gte(now()->subSeconds($age)), 409, 'Scan the learner card again.');
        $card = Card::findOrFail($scan->card_id);
        abort_unless($card->type === 'learner' && (int) $card->user_id === (int) $scan->user_id, 403);
        $person = app(CardAccess::class)->active($card);
        $learner = Students::where('user_id', $person->id)->firstOrFail();
        app(VisitAccess::class)->learner((int) $learner->id);

        return [$device, $scan, $learner];
    }

    public function lookup(string $credential, string $scanId): array
    {
        [$device, $scan, $learner] = $this->scan($credential, $scanId);
        if (! app(ModuleGate::class)->writable('family_visits')) {
            return ['scan_id' => $scan->id, 'learner_id' => (int) $learner->id, 'learner_name' => '', 'passes' => [], 'collector_check_required' => true, 'available' => false];
        }
        $rows = GatePass::where('learner_id', $learner->id)->where('status', 'approved')->where('starts_at', '<=', now()->utc())->where('ends_at', '>', now()->utc())->orderBy('starts_at')->limit(2)->get();
        $passes = [];
        foreach ($rows as $pass) {
            try {
                $this->validSource($pass);
                // Reason is private to the requesting family and approving staff.
                $passes[] = ['id' => $pass->id, 'revision' => $pass->revision, 'collector' => $pass->collector,
                    'starts_at' => $pass->starts_at->toIso8601String(), 'ends_at' => $pass->ends_at->toIso8601String()];
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                // A withdrawn source never appears as a collection clearance.
            }
        }
        $this->scan($credential, $scanId);

        return ['scan_id' => $scan->id, 'learner_id' => (int) $learner->id, 'learner_name' => $learner->user->full_name, 'passes' => $passes, 'collector_check_required' => true, 'available' => true];
    }

    public function collect(string $credential, string $id, array $input): array
    {
        $data = VisitOperations::input($input, self::rules('collect'));
        [$device] = $this->scan($credential, $data['scan_id']);
        $authorize = function () use ($credential, $id, $data) {
            [$device, , $learner] = $this->scan($credential, $data['scan_id']);
            app(VisitAccess::class)->actor(User::findOrFail($device->issued_by), true);
            $pass = GatePass::findOrFail($id);
            abort_unless((int) $pass->learner_id === (int) $learner->id, 404);
            $this->validSource($pass);
        };

        return app(VisitOperations::class)->once('device', $device->id, 'gate.collect', ['pass_id' => $id] + $data, $authorize, function () use ($device, $id, $data) {
            $pass = GatePass::findOrFail($id);
            abort_unless($pass->status === 'approved' && $pass->revision === $data['revision'] && $pass->starts_at->lte(now()) && $pass->ends_at->gt(now()), 409, 'This request is not currently approved for collection.');
            $pass->forceFill(['status' => 'collected', 'revision' => $pass->revision + 1, 'collected_at' => now()->utc(), 'collected_device_id' => $device->id, 'collected_scan_id' => $data['scan_id']])->save();

            return ['id' => $pass->id, 'status' => $pass->status, 'revision' => $pass->revision, 'collected_at' => $pass->collected_at->toIso8601String()];
        });
    }
}
