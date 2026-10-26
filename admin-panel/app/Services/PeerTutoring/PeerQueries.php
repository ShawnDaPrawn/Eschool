<?php

declare(strict_types=1);

namespace App\Services\PeerTutoring;

use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryTopic;
use App\Models\Parents;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\PeerTutoring\ServiceHour;
use App\Models\Recognition\HousePoint;
use App\Models\Recognition\PointCategory;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PeerQueries
{
    public function configuration(User $actor, string $persona): array
    {
        $fresh = app(PeerAccess::class)->actor($actor);
        $groups = [];
        $children = [];
        if ($persona === 'staff') {
            $candidates = app(AcademicAuthority::class)->groups($fresh)->where('active', true)->where('session_year_id', schoolSetting('session_year'))->orderBy('id')->limit(200)->get();
            foreach ($candidates as $group) {
                try {
                    app(PeerAccess::class)->group($fresh, (int) $group->id);
                    $groups[] = ['id' => (int) $group->id, 'name' => $group->name];
                } catch (ModelNotFoundException|HttpExceptionInterface) {
                    // A retained/future allocation does not grant current supervision.
                }
            }
        } else {
            abort_unless(in_array($persona, ['parent', 'student'], true), 403);
            $students = Students::whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'));
            if ($persona === 'student') {
                abort_unless(in_array('Student', app(ProfilePolicy::class)->roles($fresh), true), 403);
                $students->where('user_id', $fresh->id);
            } else {
                abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 403);
                $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);
                $students->whereIn('id', app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->select('id'));
            }
            foreach ($students->orderBy('id')->limit(200)->get() as $student) {
                try {
                    app(PeerAccess::class)->familyLearner($fresh, (int) $student->id, $persona);
                    $children[] = ['id' => (int) $student->id, 'name' => $student->user->full_name];
                } catch (ModelNotFoundException|HttpExceptionInterface) {
                    continue;
                }
                if (count($children) >= 200) {
                    break;
                }
            }
        }
        app(PeerAccess::class)->actor($actor);
        $gate = app(ModuleGate::class);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'persona' => $persona,
            'timezone' => app(CurrentSchool::class)->school()->timezone, 'writable' => $persona === 'staff' && $gate->writable('peer_tutoring'),
            'groups' => $groups, 'children' => $children,
            'can_award_points' => $persona === 'staff' && $gate->writable('house_points'),
            'point_categories' => $persona === 'staff' && $gate->allows('house_points')
                ? PointCategory::where('active', true)->where('maximum_points', '>=', 1)->orderBy('id')->limit(200)->get()->map(fn ($category) => ['id' => (int) $category->id, 'name' => $category->name, 'minimum_points' => (int) $category->minimum_points,
                    'maximum_points' => (int) $category->maximum_points, 'default_points' => (int) $category->default_points])->all()
                : []];
    }

    public function roster(User $actor, int $group): array
    {
        app(PeerAccess::class)->group($actor, $group);
        $today = app(AcademicAuthority::class)->today();
        $ids = TeachingGroupMembership::where('teaching_group_id', $group)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('learner_id');
        $students = Students::whereIn('id', $ids)->orderBy('id')->limit(200)->get();
        $rows = [];
        foreach ($students as $student) {
            try {
                app(PeerMatches::class)->participant($actor, $group, (int) $student->id);
                $rows[] = ['id' => (int) $student->id, 'name' => $student->user->full_name];
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                continue;
            }
        }
        app(PeerAccess::class)->group($actor, $group);

        return $rows;
    }

    public function read(User $actor, string $id, string $persona): array
    {
        app(PeerAccess::class)->actor($actor);
        $row = PeerTutoringSession::findOrFail($id);
        $this->authorize($actor, $row, $persona);
        $result = $this->data($actor, $row, $persona);
        $this->authorize($actor, $row->fresh(), $persona);

        return $result;
    }

    private function authorize(User $actor, PeerTutoringSession $row, string $persona): void
    {
        if ($persona === 'staff') {
            app(PeerAccess::class)->staffSession($actor, $row);

            return;
        }
        foreach ([$row->tutor_learner_id, $row->tutee_learner_id] as $id) {
            try {
                app(PeerAccess::class)->familyLearner($actor, (int) $id, $persona);

                return;
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                continue;
            }
        }
        abort(404);
    }

    public function listing(User $actor, string $persona, int $page = 1): array
    {
        abort_unless($page >= 1 && $page <= 100000, 422);
        $config = $this->configuration($actor, $persona);
        $query = PeerTutoringSession::query();
        if ($persona === 'staff') {
            $groupIds = array_column($config['groups'], 'id');
            $query->whereIn('teaching_group_id', $groupIds);
            $a = app(AcademicAuthority::class);
            if (! $a->manages($actor)) {
                $leadIds = TeachingGroup::whereIn('id', $groupIds)->get()->filter(fn ($group) => $a->leads($actor, 'grade_head', (int) $group->grade_level_id, (int) $group->session_year_id)
                    || $a->leads($actor, 'principal', (int) $group->grade_level_id, (int) $group->session_year_id))->pluck('id');
                $query->where(fn ($q) => $q->where('supervisor_user_id', $actor->id)->orWhereIn('teaching_group_id', $leadIds));
            }
        } else {
            $ids = array_column($config['children'], 'id');
            $query->where(fn ($q) => $q->whereIn('tutor_learner_id', $ids)->orWhereIn('tutee_learner_id', $ids));
        }
        $rows = $query->orderByDesc('starts_at')->orderBy('id')->skip(($page - 1) * 20)->limit(21)->get();
        $items = [];
        foreach ($rows->take(20) as $row) {
            try {
                $items[] = $this->read($actor, $row->id, $persona);
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                continue;
            }
        }
        app(PeerAccess::class)->actor($actor);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'items' => $items, 'next_page' => $rows->count() > 20 ? $page + 1 : null];
    }

    private function data(User $actor, PeerTutoringSession $row, string $persona): array
    {
        $names = [];
        foreach ([$row->tutor_learner_id, $row->tutee_learner_id] as $id) {
            $student = Students::find($id);
            $names[] = $student !== null && app(ApprovalService::class)->approved($student) ? $student->user->full_name : null;
        }
        $staff = $persona === 'staff';
        $ownService = $staff;
        if (! $staff) {
            try {
                app(PeerAccess::class)->familyLearner($actor, (int) $row->tutor_learner_id, $persona);
                $ownService = true;
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                // A tutee's family cannot read the other learner's service/points.
            }
        }
        $writable = $staff && app(ModuleGate::class)->writable('peer_tutoring');

        return ['id' => $row->id, 'revision' => $row->revision, 'teaching_group_id' => (int) $row->teaching_group_id,
            'topic_id' => (int) $row->topic_id, 'topic_name' => MasteryTopic::findOrFail($row->topic_id)->name,
            'tutor_learner_id' => (int) $row->tutor_learner_id, 'tutor_name' => $names[0], 'tutee_learner_id' => (int) $row->tutee_learner_id, 'tutee_name' => $names[1],
            'supervisor_user_id' => (int) $row->supervisor_user_id, 'supervisor_name' => User::findOrFail($row->supervisor_user_id)->full_name,
            'starts_at' => $row->starts_at->toIso8601String(), 'ends_at' => $row->ends_at->toIso8601String(), 'venue' => $row->venue, 'status' => $row->status,
            'verified_minutes' => $row->verified_minutes, 'service_minutes' => $ownService ? (int) ServiceHour::where('peer_tutoring_session_id', $row->id)->sum('minutes') : null, 'service_for_reader' => $ownService,
            'house_point_id' => ! $ownService || $row->house_point_id === null ? null : (int) $row->house_point_id,
            'can_complete' => $writable && $row->status === 'scheduled' && $row->ends_at->lte(now()),
            'can_cancel' => $staff && $row->status === 'scheduled', 'can_reverse' => $writable && $row->status === 'completed' && ($row->house_point_id === null
                || app(ModuleGate::class)->writable('house_points')
                || HousePoint::where('reverses_id', $row->house_point_id)->exists())];
    }
}
