<?php

declare(strict_types=1);

namespace App\Services\Emergency;

use App\Models\ClassSection;
use App\Models\Emergency\EmergencyAlert;
use App\Models\Emergency\EmergencyRelease;
use App\Models\Emergency\EmergencyRollCall;
use App\Models\FamilyVisits\GatePass;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\FamilyVisits\GatePasses;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Roll call and reunification during an alert. Each class's learners are
 * accounted, unaccounted (no mark yet) or released. A child is released only
 * to one of their guardians (checked by their guardian card at the gate
 * reader, or by staff checking an identity document) or to the named
 * collector of an early-collection pass the school approved for today
 * (identity document). Anyone else is refused. Every release is logged and
 * never changed. Class teachers take their own class's roll call; staff with
 * the respond permission see every class and release children. Guardians see
 * only their own children's status.
 */
class EmergencyReunification
{
    /**
     * Roll call and releases stay open while an alert is active, however long
     * that is, and for this long after its all-clear.
     */
    public const OPEN_HOURS = 24;

    public static function rules(string $action): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];

        return match ($action) {
            'roll_call' => ['class_section_id' => ['sometimes', 'integer', 'min:1']],
            'mark' => $operation + ['learner_ids' => ['required', 'array', 'min:1', 'max:200'], 'learner_ids.*' => ['integer', 'min:1', 'distinct'], 'status' => ['required', 'string', 'in:accounted,unaccounted']],
            'release' => $operation + ['learner_id' => ['required', 'integer', 'min:1'], 'released_to' => ['required', 'string', 'in:guardian,collector'],
                'guardian_user_id' => ['nullable', 'required_if:released_to,guardian', 'integer', 'min:1'], 'gate_pass_id' => ['nullable', 'required_if:released_to,collector', 'uuid'],
                'collector_name' => ['nullable', 'required_if:released_to,collector', 'string', 'max:191'], 'verification' => ['required', 'string', 'in:card_scan,id_document'],
                'scan_id' => ['nullable', 'required_if:verification,card_scan', 'uuid'], 'identity_checked' => ['required', 'accepted'], 'notes' => ['nullable', 'string', 'max:500']],
            'status' => ['learner_id' => ['sometimes', 'integer', 'min:1']],
        };
    }

    /** The alert whose roll call is open: active, or cleared less than OPEN_HOURS ago. */
    public function openAlert(string $id): EmergencyAlert
    {
        $alert = EmergencyAlert::findOrFail($id);
        abort_unless($alert->status === 'active' || ($alert->status === 'all_clear' && $alert->all_clear_at !== null
            && $alert->all_clear_at->gte(now()->subHours(self::OPEN_HOURS))), 409, 'The roll call for this alert is closed.');

        return $alert;
    }

    /**
     * The active alert, however long ago it was sent (it stays shown, with its
     * all-clear, until someone clears it); otherwise the most recently cleared
     * one still open for reunification; otherwise null. Guardians only see
     * alerts sent to families.
     */
    public function currentAlert(bool $families = false): ?EmergencyAlert
    {
        $scope = fn ($q) => $q->when($families, fn ($q) => $q->where('audience', 'everyone'));

        return EmergencyAlert::where('status', 'active')->tap($scope)->orderByDesc('activated_at')->first()
            ?? EmergencyAlert::where('status', 'all_clear')->where('all_clear_at', '>=', now()->utc()->subHours(self::OPEN_HOURS))->tap($scope)->orderByDesc('all_clear_at')->first();
    }

    public function rollCall(User $actor, string $alertId, ?int $section = null): array
    {
        $fresh = app(EmergencyAccess::class)->staff($actor);
        $alert = $this->openAlert($alertId);
        $allowed = $this->sectionsFor($fresh);
        abort_if($section !== null && $allowed !== null && ! in_array($section, $allowed, true), 404);
        $sections = $section !== null ? [$section] : $allowed;
        $rows = $this->enrolments($sections);
        $marks = EmergencyRollCall::where('alert_id', $alert->id)->whereIn('learner_id', $rows->pluck('learner_id'))->get()->keyBy('learner_id');
        $releases = EmergencyRelease::where('alert_id', $alert->id)->whereIn('learner_id', $rows->pluck('learner_id'))->get()->keyBy('learner_id');
        $names = ClassSection::with(['class', 'section'])->whereIn('id', $rows->pluck('class_section_id')->unique())->get()->keyBy('id');
        $learners = Students::with('user')->whereIn('id', $rows->pluck('learner_id'))->get()->keyBy('id');
        $classes = [];
        $totals = ['accounted' => 0, 'unaccounted' => 0, 'released' => 0];
        foreach ($rows->groupBy('class_section_id') as $sectionId => $enrolments) {
            $class = ['class_section_id' => (int) $sectionId, 'name' => trim((string) ($names[$sectionId]->full_name ?? '')), 'accounted' => 0, 'unaccounted' => 0, 'released' => 0, 'learners' => []];
            foreach ($enrolments as $enrolment) {
                $learner = $learners[$enrolment->learner_id] ?? null;
                if ($learner === null) {
                    continue;
                }
                $release = $releases[$learner->id] ?? null;
                $status = $release !== null ? 'released' : ($marks[$learner->id]->status ?? 'unaccounted');
                $class[$status]++;
                $totals[$status]++;
                $class['learners'][] = ['learner_id' => (int) $learner->id, 'name' => (string) $learner->user?->full_name, 'status' => $status,
                    'marked_at' => isset($marks[$learner->id]) ? $marks[$learner->id]->marked_at->toIso8601String() : null,
                    'released_at' => $release?->released_at->toIso8601String(), 'released_to' => $release?->released_to];
            }
            usort($class['learners'], fn ($a, $b) => strcmp($a['name'], $b['name']));
            $classes[] = $class;
        }
        usort($classes, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return ['alert_id' => $alert->id, 'drill' => $alert->is_drill, 'status' => $alert->status, 'classes' => $classes, 'totals' => $totals,
            'visitors_on_site' => $allowed === null ? count(app(EmergencyVisitors::class)->onSite()) : null];
    }

    public function mark(User $actor, string $alertId, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('mark'));
        $ids = array_values(array_unique(array_map('intval', $data['learner_ids'])));
        $authorize = function () use ($actor, $alertId, $ids) {
            $fresh = app(EmergencyAccess::class)->staff($actor, true);
            $this->openAlert($alertId);
            $allowed = $this->sectionsFor($fresh);
            $found = $this->enrolments($allowed)->whereIn('learner_id', $ids)->pluck('learner_id')->unique();
            abort_unless($found->count() === count($ids), 404);
        };

        return app(EmergencyOperations::class)->once($actor, 'roll_call.mark', ['alert_id' => $alertId] + $data, $authorize, function () use ($actor, $alertId, $ids, $data) {
            $alert = $this->openAlert($alertId);
            $released = EmergencyRelease::where('alert_id', $alert->id)->whereIn('learner_id', $ids)->pluck('learner_id')->map(fn ($id) => (int) $id)->all();
            $marked = [];
            foreach (array_diff($ids, $released) as $learnerId) {
                $row = EmergencyRollCall::firstOrNew(['alert_id' => $alert->id, 'learner_id' => $learnerId]);
                $row->forceFill(['status' => $data['status'], 'marked_by' => $actor->id, 'marked_at' => now()->utc(), 'revision' => (int) ($row->revision ?? 0) + 1])->save();
                $marked[] = $learnerId;
            }
            EmergencyOperations::event($actor, 'roll_call.marked', $alert->id, null, ['status' => $data['status'], 'learner_ids' => $marked]);

            return ['alert_id' => $alert->id, 'status' => $data['status'], 'learner_ids' => $marked, 'already_released' => array_values($released)];
        });
    }

    public function release(User $actor, string $alertId, array $input): array
    {
        $data = EmergencyOperations::input($input, self::rules('release'));
        $authorize = function () use ($actor, $alertId, $data) {
            app(EmergencyAccess::class)->responder($actor);
            $this->openAlert($alertId);
            $this->currentLearner($data['learner_id']);
        };

        return app(EmergencyOperations::class)->once($actor, 'release', ['alert_id' => $alertId] + $data, $authorize, function () use ($actor, $alertId, $data) {
            $alert = $this->openAlert($alertId);
            abort_if($alert->is_drill, 409, 'Children are not released during a drill.');
            $learner = $this->currentLearner($data['learner_id']);
            abort_if(EmergencyRelease::where('alert_id', $alert->id)->where('learner_id', $learner->id)->exists(), 409, 'This child has already been released.');
            [$guardian, $pass, $scan] = $data['released_to'] === 'guardian' ? $this->verifiedGuardian($learner, $data) : $this->authorisedCollector($learner, $data);
            $release = EmergencyRelease::create([
                'alert_id' => $alert->id, 'learner_id' => $learner->id, 'released_to' => $data['released_to'], 'guardian_user_id' => $guardian?->id,
                'gate_pass_id' => $pass?->id, 'collector_name' => $pass !== null ? $pass->collector : null, 'verification' => $data['verification'],
                'scan_id' => $scan, 'notes' => $data['notes'] ?? null, 'released_by' => $actor->id, 'released_at' => now()->utc(),
            ]);
            $row = EmergencyRollCall::firstOrNew(['alert_id' => $alert->id, 'learner_id' => $learner->id]);
            $row->forceFill(['status' => 'released', 'marked_by' => $actor->id, 'marked_at' => now()->utc(), 'revision' => (int) ($row->revision ?? 0) + 1])->save();
            $details = ['learner_id' => (int) $learner->id, 'released_to' => $release->released_to, 'verification' => $release->verification,
                'guardian_user_id' => $guardian?->id, 'gate_pass_id' => $pass?->id, 'released_by' => (int) $actor->id];
            EmergencyOperations::event($actor, 'child.released', $alert->id, $release->id, $details);
            Audit::record('emergency.child_released', $alert, [], $details + ['alert_id' => $alert->id]);

            return ['id' => $release->id, 'alert_id' => $alert->id, 'learner_id' => (int) $learner->id, 'released_to' => $release->released_to,
                'released_to_name' => $guardian?->full_name ?? (string) $release->collector_name, 'verification' => $release->verification,
                'released_at' => $release->released_at->toIso8601String(), 'released_by' => (int) $actor->id];
        });
    }

    /**
     * A guardian's own children and where each one is during the current
     * alert. Another child, even at the same school, is never included.
     */
    public function guardianStatus(User $actor, ?int $learnerId = null): array
    {
        $fresh = app(EmergencyAccess::class)->guardian($actor);
        $children = app(EmergencyAccess::class)->children($fresh);
        abort_if($learnerId !== null && ! in_array($learnerId, $children, true), 404);
        $ids = $learnerId !== null ? [$learnerId] : $children;
        $current = Students::with('user')->whereIn('id', $ids)->whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'))->orderBy('id')->get();
        $alert = $this->currentAlert(true);
        $marks = $alert === null ? collect() : EmergencyRollCall::where('alert_id', $alert->id)->whereIn('learner_id', $current->pluck('id'))->get()->keyBy('learner_id');
        $releases = $alert === null ? collect() : EmergencyRelease::where('alert_id', $alert->id)->whereIn('learner_id', $current->pluck('id'))->get()->keyBy('learner_id');
        $names = User::whereIn('id', $releases->pluck('guardian_user_id')->filter())->get()->keyBy('id');

        return $current->map(function (Students $child) use ($alert, $marks, $releases, $names) {
            $release = $releases[$child->id] ?? null;

            return ['learner_id' => (int) $child->id, 'name' => (string) $child->user?->full_name,
                'status' => $alert === null ? null : ($release !== null ? 'released' : ($marks[$child->id]->status ?? 'unaccounted')),
                'released_at' => $release?->released_at->toIso8601String(), 'released_to' => $release?->released_to,
                'released_to_name' => $release === null ? null : ($release->guardian_user_id !== null ? (string) ($names[$release->guardian_user_id]->full_name ?? '') : (string) $release->collector_name)];
        })->values()->all();
    }

    /** null: every class (respond permission); otherwise the person's own classes. */
    public function sectionsFor(User $fresh): ?array
    {
        $access = app(EmergencyAccess::class);
        if ($access->may($fresh, EmergencyAccess::RESPOND)) {
            return null;
        }
        $sections = $access->classSections($fresh);
        abort_if($sections === [], 403);

        return $sections;
    }

    /** Current learners' enrolments today, optionally in some classes only. */
    private function enrolments(?array $sections): Collection
    {
        return Enrolment::where('session_year_id', (int) schoolSetting('session_year'))->onDate(app(AcademicAuthority::class)->today())
            ->whereIn('learner_id', Students::whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'))->select('id'))
            ->when($sections !== null, fn ($q) => $q->whereIn('class_section_id', $sections))
            ->get(['learner_id', 'class_section_id'])->unique('learner_id')->values();
    }

    private function currentLearner(int $id): Students
    {
        $learner = Students::whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'))->findOrFail($id);
        abort_unless(Enrolment::where('learner_id', $id)->where('session_year_id', (int) schoolSetting('session_year'))->onDate(app(AcademicAuthority::class)->today())->exists(), 404);

        return $learner;
    }

    /** @return array{0: User, 1: null, 2: ?string} */
    private function verifiedGuardian(Students $learner, array $data): array
    {
        $id = (int) $data['guardian_user_id'];
        $isGuardian = app(GuardianService::class)->guardianUserIds($learner)->contains($id)
            && SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $id)->where('role', 'Parent')->exists();
        $guardian = $isGuardian ? User::where('status', 1)->find($id) : null;
        abort_if($guardian === null, 403, 'This person is not a guardian or authorised collector for this child. Do not release the child.');
        if ($data['verification'] === 'card_scan') {
            [$scan, , $person] = app(EmergencyScans::class)->guardianCard((string) $data['scan_id']);
            abort_unless((int) $person->id === $id, 403, 'The card scanned belongs to someone else. Do not release the child.');

            return [$guardian, null, $scan->id];
        }

        return [$guardian, null, null];
    }

    /** @return array{0: null, 1: GatePass, 2: null} */
    private function authorisedCollector(Students $learner, array $data): array
    {
        abort_unless($data['verification'] === 'id_document', 422, 'Check the collector\'s identity document.');
        $zone = (string) (app(CurrentSchool::class)->school()?->timezone ?: 'Africa/Johannesburg');
        $today = CarbonImmutable::now($zone);
        $pass = GatePass::where('learner_id', $learner->id)->where('status', 'approved')
            ->where('starts_at', '<', $today->endOfDay()->utc())->where('ends_at', '>', $today->startOfDay()->utc())->find($data['gate_pass_id']);
        $valid = false;
        if ($pass !== null) {
            try {
                // The guardian who asked and the staff member who approved must both still hold that authority.
                app(GatePasses::class)->validSource($pass);
                $valid = self::sameName((string) $pass->collector, (string) $data['collector_name']);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                $valid = false;
            }
        }
        abort_unless($valid, 403, 'This person is not a guardian or authorised collector for this child. Do not release the child.');

        return [null, $pass, null];
    }

    private static function sameName(string $a, string $b): bool
    {
        $normal = fn (string $name) => mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $name)));

        return $normal($a) !== '' && hash_equals($normal($a), $normal($b));
    }
}
