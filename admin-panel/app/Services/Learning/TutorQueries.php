<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorSession;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\ReleasedMarks;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TutorQueries
{
    public function configuration(User $actor): array
    {
        $person = app(TutorAccess::class)->learner($actor);
        $groups = TeachingGroup::with('subject')->whereIn('id', app(ContentPolicy::class)->learnerGroups($actor, (int) $person->id))
            ->where('session_year_id', (int) schoolSetting('session_year'))->where('active', true)->orderBy('id')->limit(101)->get();
        abort_if($groups->count() > 100, 422);
        $items = [];
        foreach ($groups as $group) {
            app(TutorAccess::class)->group($actor, (int) $group->id);
            $versions = app(ContentPolicy::class)->readableVersions($actor, (int) $person->id)
                ->whereHas('item', fn ($q) => $q->where('subject_id', $group->subject_id)->where('session_year_id', $group->session_year_id)
                    ->whereHas('audienceGroups', fn ($g) => $g->where('teaching_group_id', $group->id)))
                ->latest('id')->limit(50)->get();
            $items[] = ['id' => (int) $group->id, 'name' => $group->name, 'subject_id' => (int) $group->subject_id, 'subject_name' => $group->subject?->name,
                'sources' => $versions->map(fn ($v) => ['id' => (int) $v->id, 'title' => $v->metadata['title']])->all()];
        }
        app(TutorAccess::class)->learner($actor);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'learner_id' => (int) $person->id,
            'session_year_id' => (int) schoolSetting('session_year'), 'groups' => $items,
            'inference_enabled' => config('ai.inference.enabled') === true && config('ai.inference.features.tutor.enabled') === true,
            'photo_requires_approved_crop' => true, 'feedback_is_provisional' => true];
    }

    public function sessions(User $actor, int $page = 1): array
    {
        $person = app(TutorAccess::class)->learner($actor);
        $rows = TutorSession::where('learner_id', $person->id)->where('user_id', $actor->id)->latest('created_at')->paginate(20, ['id'], 'page', max(1, $page));
        $items = [];
        foreach ($rows as $row) {
            // Retained sessions without a current subject/enrolment grant are not listed.
            try {
                $session = app(TutorAccess::class)->session($actor, $row->id);
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                continue;
            }
            $items[] = ['id' => $session->id, 'subject_id' => (int) $session->subject_id, 'state' => $session->state];
        }
        app(TutorAccess::class)->learner($actor);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'learner_id' => (int) $person->id,
            'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'sessions' => $items];
    }

    /** Approved learning metadata only: no raw chat, note, safety reason or flagged count. */
    public function parentSummary(User $actor, int $learner, int $subject): array
    {
        $fresh = app(TutorAccess::class)->actor($actor);
        abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 404);
        $person = app(ReleasedMarks::class)->learner($fresh, $learner);
        abort_unless(app(ApprovalService::class)->approved($person), 403);
        $subjectRow = Subject::findOrFail($subject);
        $sessions = TutorSession::where('learner_id', $learner)->where('subject_id', $subject)->where('session_year_id', (int) schoolSetting('session_year'))->select('id');
        $rows = TutorMessage::whereIn('tutor_session_id', $sessions)->where('state', 'completed')->whereNull('purged_at')
            ->whereIn('response_kind', ['hint', 'question', 'correction']);
        $turns = (clone $rows)->count();
        $count = (clone $rows)->distinct()->count('tutor_session_id');
        app(TutorAccess::class)->actor($fresh);
        app(ReleasedMarks::class)->learner($fresh, $learner);
        abort_unless(app(ApprovalService::class)->approved(Students::findOrFail($learner)), 403);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'learner_id' => $learner, 'subject_id' => $subject,
            'subject_name' => $subjectRow->name, 'learning_sessions' => $count, 'learning_steps' => $turns,
            'suggestion_key' => 'Ask your child to explain one worked example.', 'contains_raw_chat' => false];
    }
}
