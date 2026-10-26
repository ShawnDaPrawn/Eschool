<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Classroom;

use App\Http\Controllers\Controller;
use App\Http\Middleware\FoundationStaffContext;
use App\Http\Requests\Classroom\FoundationRequest;
use App\Models\Academics\TeachingGroup;
use App\Models\Cards\CardReaderDevice;
use App\Models\Classroom\ClassroomEligibility;
use App\Models\Classroom\ClassroomLease;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Cards\CardAccess;
use App\Services\Cards\CardReaders;
use App\Services\Classroom\ClassroomAccess;
use App\Services\Classroom\ClassroomAuthority;
use App\Services\Classroom\ClassroomPractice;
use App\Services\Classroom\ClassroomSessions;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\JsonResponse;

class FoundationController extends Controller
{
    public function configuration(FoundationRequest $request): JsonResponse
    {
        return $this->result($this->configurationData($request->user(), (int) $request->validated('page', 1)));
    }

    /** No learner identities, review reasons or credential material appear here. */
    public function configurationData(User $actor, int $page = 1): array
    {
        $access = app(FoundationStaffContext::class);
        abort_unless($access->available($actor), 403);
        $fresh = app(CardAccess::class)->actor($actor);
        $manager = $access->manager($fresh);
        $academic = app(AcademicAuthority::class);
        $today = $academic->today();
        $year = (int) schoolSetting('session_year');
        $teachers = $academic->teachers($fresh);
        $query = TeachingGroup::where('active', true)->where('session_year_id', $year)
            ->whereHas('year', fn ($q) => $q->whereNull('session_years.deleted_at')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today));
        if (! $manager) {
            $query->whereHas('allocations', fn ($q) => $q->whereIn('teacher_id', $teachers)
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today));
        }
        $rows = $query->orderBy('id')->offset(($page - 1) * 50)->limit(51)->get();
        $ids = $rows->take(50)->pluck('id')->all();
        $eligibility = ClassroomEligibility::whereIn('teaching_group_id', $ids)->get()->keyBy('teaching_group_id');
        $own = TeachingGroup::whereIn('id', $ids)->whereHas('allocations', fn ($q) => $q->whereIn('teacher_id', $teachers)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today))->pluck('id')->all();
        $devices = CardReaderDevice::where('kind', 'classroom')->orderByDesc('created_at')->limit(100)->get()
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'status' => $d->status,
                'expires_at' => $d->expires_at->toIso8601String(), 'active' => $d->status === 'active' && $d->revoked_at === null && $d->expires_at->isFuture()])->all();
        $leases = ClassroomLease::query();
        if (! $manager) {
            $leases->where('teacher_user_id', $fresh->id);
        }
        $leaseRows = $leases->orderByDesc('created_at')->limit(100)->get()->map(fn ($l) => ['id' => $l->id, 'device_id' => $l->device_id,
            'teaching_group_id' => (int) $l->teaching_group_id, 'expires_at' => $l->expires_at->toIso8601String(),
            'active' => $l->revoked_at === null && $l->expires_at->isFuture()])->all();

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $fresh->id, 'session_year_id' => $year,
            'can_manage' => $manager, 'can_grant' => in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true),
            'writable' => app(ModuleGate::class)->writable('cards'), 'page' => $page, 'next_page' => $rows->count() > 50 ? $page + 1 : null,
            'groups' => $rows->take(50)->map(fn ($g) => ['id' => (int) $g->id, 'name' => (string) $g->name,
                'eligible' => (bool) ($eligibility->get($g->id)?->eligible ?? false), 'revision' => (int) ($eligibility->get($g->id)?->revision ?? 0),
                'can_authorize' => in_array($g->id, $own, true)])->values()->all(), 'devices' => $devices, 'leases' => $leaseRows];
    }

    public function staffWrite(FoundationRequest $request): JsonResponse
    {
        return $this->result($this->staffResult($request));
    }

    public function staffResult(FoundationRequest $request): array
    {
        $actor = $request->user();
        $input = $request->inputForService();

        return match ($request->route('foundation_action')) {
            'eligibility' => app(ClassroomAuthority::class)->configure($actor, (int) $request->route('group'), $input),
            'register' => $this->register($actor, $input),
            'grant' => app(ClassroomAuthority::class)->grant($actor, $input),
            'revoke' => $this->revoke($actor, (string) $request->route('lease')),
            'device-revoke' => $this->revokeDevice($actor, (string) $request->route('device')),
            default => abort(404),
        };
    }

    private function register(User $actor, array $input): array
    {
        app(ClassroomAccess::class)->manager($actor);
        $encoded = substr($input['setup_code'], 7);
        $public = base64_decode(strtr($encoded, '-_', '+/').'=', true);
        abort_unless($public !== false && strlen($public) === SODIUM_CRYPTO_BOX_PUBLICKEYBYTES
            && rtrim(strtr(base64_encode($public), '+/', '-_'), '=') === $encoded && $public !== str_repeat("\0", 32), 422);
        unset($input['setup_code']);
        $result = app(CardReaders::class)->register($actor, $input + ['kind' => 'classroom', 'public_key' => base64_encode($public)]);

        return ['id' => $result['id'], 'tablet_access_code' => $result['credential'], 'kind' => 'classroom', 'expires_at' => $result['expires_at']];
    }

    private function revoke(User $actor, string $id): array
    {
        app(ClassroomAuthority::class)->revoke($actor, $id);

        return ['revoked' => true];
    }

    private function revokeDevice(User $actor, string $id): array
    {
        abort_unless(app(FoundationStaffContext::class)->manager($actor), 403);
        CardReaderDevice::where('kind', 'classroom')->findOrFail($id);
        app(CardReaders::class)->revoke($actor, $id);

        return ['revoked' => true];
    }

    public function device(FoundationRequest $request): JsonResponse
    {
        return $this->result(app(ClassroomSessions::class)->select($this->deviceCredential($request), $request->inputForService()));
    }

    public function learner(FoundationRequest $request): JsonResponse
    {
        $sessions = app(ClassroomSessions::class);
        $device = $this->deviceCredential($request);
        $learner = (string) $request->attributes->get('foundation_learner_credential');
        $action = $request->route('foundation_action');
        if ($action === 'context') {
            $data = $sessions->data($sessions->authenticate($device, $learner));
        } elseif ($action === 'leave') {
            $sessions->leave($device, $learner);
            $data = ['left' => true];
        } else {
            $data = app(ClassroomPractice::class)->run($device, $learner, $action, $request->inputForService(),
                $request->route('set') === null ? null : (int) $request->route('set'),
                $request->route('entry') === null ? null : (int) $request->route('entry'));
        }

        return $this->result($data);
    }

    private function deviceCredential(FoundationRequest $request): string
    {
        return (string) $request->attributes->get('foundation_device_credential');
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Classroom request completed.', 'data' => $data, 'code' => 200]);
    }
}
