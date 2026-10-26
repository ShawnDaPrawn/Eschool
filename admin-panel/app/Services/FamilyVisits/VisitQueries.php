<?php

declare(strict_types=1);

namespace App\Services\FamilyVisits;

use App\Models\FamilyVisits\FamilyVisitRule;
use App\Models\FamilyVisits\GatePass;
use App\Models\FamilyVisits\MeetingSlot;
use App\Models\Parents;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class VisitQueries
{
    public function gateRules(): array
    {
        $row = FamilyVisitRule::first();

        return ['max_window_minutes' => $row?->max_window_minutes ?? 240, 'max_future_days' => $row?->max_future_days ?? 30,
            'scan_max_age_seconds' => $row?->scan_max_age_seconds ?? 120, 'revision' => $row?->revision ?? 0,
            'staff_approval_required' => true, 'collector_check_required' => true];
    }

    public function changeRules(User $actor, array $input): array
    {
        $data = VisitOperations::input($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'max_window_minutes' => ['required', 'integer', 'between:15,240'], 'max_future_days' => ['required', 'integer', 'between:1,30'], 'scan_max_age_seconds' => ['required', 'integer', 'between:15,120']]);

        return app(VisitOperations::class)->once('user', (string) $actor->id, 'gate.rules', $data, fn () => app(VisitAccess::class)->manager($actor, true), function () use ($data) {
            $row = FamilyVisitRule::first();
            abort_unless(($row?->revision ?? 0) === $data['revision'], 409, 'Refresh the school collection rules.');
            $row ??= new FamilyVisitRule;
            $row->fill(array_diff_key($data, ['operation_id' => true, 'revision' => true]) + ['revision' => $data['revision'] + 1])->save();

            return ['id' => $row->id] + $this->gateRules();
        });
    }

    public function configuration(User $actor): array
    {
        $fresh = app(VisitAccess::class)->actor($actor);
        $manager = app(ProfilePolicy::class)->permission($fresh, VisitAccess::MANAGE);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $children = [];
        if (in_array('Parent', $roles, true)) {
            $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);
            $rows = app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->orderBy('id')->limit(201)->get();
            abort_if($rows->count() > 200, 422, 'Narrow the family roster.');
            foreach ($rows as $row) {
                try {
                    app(VisitAccess::class)->guardian($fresh, (int) $row->id);
                    $children[] = ['id' => (int) $row->id, 'name' => $row->user->full_name];
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    // A retained guardian link cannot expose a withdrawn learner.
                }
            }
        }
        $hosts = [];
        if ($manager || in_array('Parent', $roles, true)) {
            $rows = User::whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->whereNotIn('role', ['Parent', 'Student'])->select('user_id'))->where('status', 1)->orderBy('id')->limit(201)->get();
            abort_if($rows->count() > 200, 422, 'Narrow the meeting staff roster.');
            foreach ($rows as $row) {
                try {
                    app(VisitAccess::class)->host((int) $row->id);
                    $hosts[] = ['id' => (int) $row->id, 'name' => $row->full_name];
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    // Platform identities are never parent meeting hosts.
                }
            }
        }
        app(VisitAccess::class)->actor($fresh);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $fresh->id, 'timezone' => app(CurrentSchool::class)->school()->timezone,
            'writable' => app(ModuleGate::class)->writable('family_visits'), 'can_manage' => $manager, 'can_host' => array_diff($roles, ['Parent', 'Student']) !== [],
            'children' => $children, 'hosts' => $hosts, 'gate_rules' => $this->gateRules()];
    }

    public function meetings(User $actor, ?int $learner, int $page = 1): array
    {
        $fresh = app(VisitAccess::class)->actor($actor);
        abort_unless($page > 0 && $page <= 100000, 422);
        $query = MeetingSlot::query();
        if ($learner !== null) {
            app(VisitAccess::class)->guardian($fresh, $learner);
            $query->where(function ($q) use ($actor, $learner) {
                $q->where(fn ($open) => $open->where('status', 'open')->where('booking_closes_at', '>', now()->utc()))
                    ->orWhere(fn ($own) => $own->where('learner_id', $learner)->where('guardian_user_id', $actor->id));
            });
        } elseif (! app(ProfilePolicy::class)->permission($fresh, VisitAccess::MANAGE)) {
            app(VisitAccess::class)->host((int) $fresh->id);
            $query->where('host_id', $fresh->id);
        }
        $rows = $query->orderBy('starts_at')->orderBy('id')->offset(($page - 1) * 25)->limit(26)->get();
        $items = [];
        foreach ($rows->take(25) as $row) {
            try {
                $items[] = app(Meetings::class)->summary($row, $learner === null || $row->learner_id !== null);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // A stale participant cannot restore a current private identity.
            }
        }
        if ($learner !== null) {
            app(VisitAccess::class)->guardian($fresh, $learner);
        }
        app(VisitAccess::class)->actor($fresh);

        return ['items' => $items, 'next_page' => $rows->count() > 25 ? $page + 1 : null];
    }

    public function passes(User $actor, ?int $learner, int $page = 1): array
    {
        app(VisitAccess::class)->actor($actor);
        abort_unless($page > 0 && $page <= 100000, 422);
        $query = GatePass::query();
        if ($learner !== null) {
            app(VisitAccess::class)->guardian($actor, $learner);
            $query->where('learner_id', $learner)->where('requested_by', $actor->id);
        } else {
            app(VisitAccess::class)->manager($actor);
        }
        $rows = $query->orderByDesc('starts_at')->orderByDesc('id')->offset(($page - 1) * 25)->limit(26)->get();
        $items = [];
        foreach ($rows->take(25) as $row) {
            try {
                app(GatePasses::class)->validSource($row);
                $items[] = app(GatePasses::class)->summary($row) + ['learner_name' => app(VisitAccess::class)->learner((int) $row->learner_id)->user->full_name];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // Approval withdrawal erases current private pass readers.
            }
        }
        if ($learner !== null) {
            app(VisitAccess::class)->guardian($actor, $learner);
        } else {
            app(VisitAccess::class)->manager($actor);
        }

        return ['items' => $items, 'next_page' => $rows->count() > 25 ? $page + 1 : null];
    }
}
