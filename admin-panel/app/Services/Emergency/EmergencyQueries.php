<?php

declare(strict_types=1);

namespace App\Services\Emergency;

use App\Models\Emergency\EmergencyAlert;
use App\Models\FamilyVisits\GatePass;
use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

/** What the panel page and the app's status call show. */
class EmergencyQueries
{
    /**
     * The current alert and, for a guardian, where each of their own children
     * is. Staff also see what they may do. Learners are refused: alerts are
     * for the adults responsible for them.
     */
    public function status(User $actor, ?int $learnerId = null): array
    {
        $access = app(EmergencyAccess::class);
        $fresh = $access->actor($actor);
        $abilities = $access->abilities($fresh);
        abort_unless($abilities['staff'] || $abilities['guardian'], 403);
        abort_if($learnerId !== null && ! $abilities['guardian'], 404);
        $reunification = app(EmergencyReunification::class);
        $alert = $reunification->currentAlert(! $abilities['staff']);

        return [
            'alert' => $alert === null ? null : $this->public($alert),
            'children' => $abilities['guardian'] ? $reunification->guardianStatus($fresh, $learnerId) : [],
            'can_alert' => $abilities['can_alert'], 'can_respond' => $abilities['can_respond'],
            'takes_roll_call' => $abilities['can_respond'] || $abilities['class_sections'] !== [],
        ];
    }

    /** What everyone told may see: never who prepared it or how many people were told. */
    public function public(EmergencyAlert $alert): array
    {
        $alerts = app(EmergencyAlerts::class);

        return ['id' => $alert->id, 'kind' => $alert->kind, 'drill' => $alert->is_drill, 'status' => $alert->status,
            'title' => $alerts->title($alert, $alert->title), 'message' => $alerts->body($alert, $alert->message),
            'activated_at' => $alert->activated_at?->toIso8601String(), 'all_clear_at' => $alert->all_clear_at?->toIso8601String(),
            'all_clear_message' => $alert->all_clear_message === null ? null : $alerts->body($alert, $alert->all_clear_message)];
    }

    /** The panel page for a member of staff. */
    public function panel(User $actor): array
    {
        $access = app(EmergencyAccess::class);
        $fresh = $access->staff($actor);
        $abilities = $access->abilities($fresh);
        $alerts = app(EmergencyAlerts::class);
        $reunification = app(EmergencyReunification::class);
        $current = $reunification->currentAlert();
        $rollCall = null;
        if ($current !== null && ($abilities['can_respond'] || $abilities['class_sections'] !== [])) {
            $rollCall = $reunification->rollCall($fresh, $current->id);
        }
        $pending = $abilities['can_alert'] ? EmergencyAlert::where('status', 'pending')->where('prepared_at', '>=', now()->utc()->subMinutes(EmergencyAlerts::CONFIRM_MINUTES))
            ->orderByDesc('prepared_at')->limit(5)->get()->map(fn ($alert) => $alerts->summary($alert))->all() : [];
        $history = EmergencyAlert::whereIn('status', ['active', 'all_clear', 'cancelled'])->orderByDesc('prepared_at')->limit(10)->get();
        $people = User::whereIn('id', $history->flatMap(fn ($alert) => [$alert->prepared_by, $alert->confirmed_by, $alert->all_clear_by])->filter()->unique())->get()->keyBy('id');
        $drills = app(EmergencyDrills::class);
        $visitors = app(EmergencyVisitors::class);

        return [
            'abilities' => $abilities,
            'current' => $current === null ? null : $alerts->summary($current) + ['shown' => $this->public($current)],
            'pending' => $pending,
            'roll_call' => $rollCall,
            'release_options' => $rollCall !== null && $abilities['can_respond'] && ! $rollCall['drill'] ? $this->releaseOptions($rollCall) : [],
            'history' => $history->map(fn ($alert) => $alerts->summary($alert) + [
                'prepared_by_name' => (string) ($people[$alert->prepared_by]->full_name ?? ''),
                'confirmed_by_name' => $alert->confirmed_by === null ? null : (string) ($people[$alert->confirmed_by]->full_name ?? ''),
                'all_clear_by_name' => $alert->all_clear_by === null ? null : (string) ($people[$alert->all_clear_by]->full_name ?? ''),
            ])->all(),
            'visitors' => $abilities['can_respond'] ? $visitors->onSite() : [],
            'cards' => $visitors->cardsAvailable(),
            'drills' => $abilities['can_manage'] || $abilities['can_alert'] ? $drills->list() : [],
            'settings' => $visitors->settings(),
            'timezone' => (string) (app(CurrentSchool::class)->school()?->timezone ?: 'Africa/Johannesburg'),
        ];
    }

    /**
     * For the release form: each unreleased learner's guardians and today's
     * approved collectors. Worked out in a few queries for the whole school;
     * the release itself checks the guardian or collector again, one by one.
     *
     * @return array{guardians: array<int, string>, passes: array<string, string>}
     */
    private function releaseOptions(array $rollCall): array
    {
        $learners = [];
        foreach ($rollCall['classes'] as $class) {
            foreach ($class['learners'] as $learner) {
                if ($learner['status'] !== 'released') {
                    $learners[$learner['learner_id']] = $learner['name'];
                }
            }
        }
        $ids = array_keys($learners);
        $links = GuardianLink::whereIn('learner_id', $ids)->get(['learner_id', 'guardian_user_id', 'status']);
        $pairs = [];
        foreach ($links->where('status', 'active') as $link) {
            $pairs[] = [(int) $link->learner_id, (int) $link->guardian_user_id];
        }
        $linked = $links->map(fn ($link) => $link->learner_id.':'.$link->guardian_user_id)->all();
        $students = Students::whereIn('id', $ids)->get(['id', 'father_id', 'mother_id', 'guardian_id']);
        $parents = Parents::whereIn('id', $students->flatMap(fn ($s) => [$s->father_id, $s->mother_id, $s->guardian_id])->filter()->unique())->pluck('user_id', 'id');
        foreach ($students as $student) {
            foreach ([$student->father_id, $student->mother_id, $student->guardian_id] as $parent) {
                $user = (int) ($parents[$parent] ?? 0);
                if ($user > 0 && ! in_array($student->id.':'.$user, $linked, true)) {
                    $pairs[] = [(int) $student->id, $user];
                }
            }
        }
        $names = User::where('status', 1)->whereIn('id', array_unique(array_column($pairs, 1)))->get()->keyBy('id');
        $children = [];
        foreach ($pairs as [$learner, $user]) {
            if (isset($names[$user])) {
                $children[$user][$learner] = $learners[$learner];
            }
        }
        $guardians = [];
        foreach ($children as $user => $of) {
            $guardians[$user] = __(':guardian, for :learner', ['guardian' => $names[$user]->full_name, 'learner' => implode(', ', $of)]);
        }
        $today = CarbonImmutable::now((string) (app(CurrentSchool::class)->school()?->timezone ?: 'Africa/Johannesburg'));
        $passes = [];
        foreach (GatePass::whereIn('learner_id', $ids)->where('status', 'approved')->where('starts_at', '<', $today->endOfDay()->utc())->where('ends_at', '>', $today->startOfDay()->utc())->get() as $pass) {
            $passes[$pass->id] = __(':collector, for :learner', ['collector' => $pass->collector, 'learner' => $learners[(int) $pass->learner_id]]);
        }
        asort($guardians);
        asort($passes);

        return ['guardians' => $guardians, 'passes' => $passes];
    }
}
