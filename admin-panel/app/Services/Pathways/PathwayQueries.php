<?php

declare(strict_types=1);

namespace App\Services\Pathways;

use App\Models\Parents;
use App\Models\Pathways\AdmissionRequirement;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\ReleasedMarks;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PathwayQueries
{
    public function configuration(User $actor): array
    {
        $access = app(PathwayAccess::class);
        $fresh = $access->actor($actor);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $manager = array_diff($roles, ['Parent', 'Student']) !== [] && app(ProfilePolicy::class)->permission($fresh, PathwayAccess::MANAGE);
        $children = [];
        $ids = [];
        if (in_array('Student', $roles, true)) {
            $ids = Students::where('user_id', $fresh->id)->pluck('id')->all();
        }
        if (in_array('Parent', $roles, true)) {
            $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);
            $rows = app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->orderBy('id')->limit(201)->pluck('id');
            abort_if($rows->count() > 200, 422, 'Narrow the family roster.');
            $ids = array_unique(array_merge($ids, $rows->all()));
        }
        foreach ($ids as $id) {
            try {
                $learner = $access->familyLearner($fresh, (int) $id);
                $children[] = ['id' => (int) $learner->id, 'name' => $learner->user->full_name];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // Retained or withdrawn guardian links are not current authority.
            }
        }
        abort_unless($manager || $children !== [], 403);
        $subjects = Subject::orderBy('name')->limit(501)->get();
        abort_if($subjects->count() > 500, 422, 'Narrow the school subject catalogue.');
        $access->actor($fresh);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $fresh->id,
            'writable' => app(ModuleGate::class)->writable('pathway'), 'can_manage' => $manager, 'children' => $children,
            'subjects' => $subjects->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->all(),
            'years' => SessionYear::orderByDesc('start_date')->limit(100)->get()->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name])->all(),
            'terms' => Term::where('session_year_id', schoolSetting('session_year'))->orderBy('start_date')->get()->map(fn ($t) => ['id' => (int) $t->id, 'name' => $t->name, 'session_year_id' => (int) $t->session_year_id])->all()];
    }

    public function catalogue(User $actor, int $page = 1, bool $manage = false): array
    {
        $configuration = $this->configuration($actor);
        abort_unless($page > 0 && $page <= 100000, 422);
        if ($manage) {
            app(PathwayAccess::class)->manager($actor);
        }
        $query = AdmissionRequirement::query();
        if (! $manage) {
            $query->where('status', 'published')->whereDate('expires_on', '>=', app(AcademicAuthority::class)->today());
        }
        $rows = $query->orderByDesc('updated_at')->orderBy('id')->offset(($page - 1) * 25)->limit(26)->get();
        $items = [];
        foreach ($rows->take(25) as $row) {
            try {
                $current = app(PathwayReferences::class)->current($row->id, $manage);
                $items[] = app(PathwayReferences::class)->data($current);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // A retired subject cannot silently produce an incomplete score.
            }
        }
        $this->configuration($actor);

        return ['school_id' => $configuration['school_id'], 'reader_id' => $configuration['reader_id'], 'items' => $items, 'next_page' => $rows->count() > 25 ? $page + 1 : null];
    }

    public function read(User $actor, string $id, bool $manage = false): array
    {
        $configuration = $this->configuration($actor);
        if ($manage) {
            app(PathwayAccess::class)->manager($actor);
        }
        $data = app(PathwayReferences::class)->data(app(PathwayReferences::class)->current($id, $manage));
        $this->configuration($actor);

        return ['school_id' => $configuration['school_id'], 'reader_id' => $configuration['reader_id']] + $data;
    }

    /** Read-only computation; submitted scenarios are never stored or logged here. */
    public function project(User $actor, string $id, array $input): array
    {
        $data = Validator::make($input, [
            'learner_id' => ['required', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:1'],
            'source' => ['required', 'in:scenario,released,selection'], 'session_year_id' => ['nullable', 'integer', 'min:1'], 'term_id' => ['nullable', 'integer', 'min:1'],
            'marks' => ['sometimes', 'array', 'list', 'max:50'], 'marks.*' => ['array:subject_id,percentage_bp'],
            'marks.*.subject_id' => ['required', 'integer', 'min:1', 'distinct'], 'marks.*.percentage_bp' => ['present', 'nullable', 'integer', 'between:0,10000'],
            'subject_ids' => ['sometimes', 'array', 'list', 'max:50'], 'subject_ids.*' => ['integer', 'min:1', 'distinct'],
        ])->validate();
        $learner = app(PathwayAccess::class)->familyLearner($actor, (int) $data['learner_id']);
        $reference = $this->read($actor, $id);
        abort_unless($reference['revision'] === (int) $data['revision'], 409, 'This pathway source changed. Reload its requirements.');
        $rules = $reference['rules'];
        $result = ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'learner_id' => (int) $learner->id,
            'reference_id' => $id, 'revision' => $reference['revision'], 'kind' => $reference['kind'], 'source' => $data['source'], 'score' => null, 'selection' => null, 'released_evidence' => []];
        if ($reference['kind'] === 'subject_choice_guide') {
            abort_unless($data['source'] === 'selection' && ! isset($data['marks']), 422);
            $selected = array_map('intval', $data['subject_ids'] ?? []);
            $allowed = array_merge($rules['compulsory_subject_ids'], ...array_column($rules['elective_groups'], 'subject_ids'));
            abort_unless(array_diff($selected, $allowed) === [], 422, 'Choose subjects from this published guide.');
            $missing = array_values(array_diff($rules['compulsory_subject_ids'], $selected));
            $groups = [];
            foreach ($rules['elective_groups'] as $group) {
                $count = count(array_intersect($selected, $group['subject_ids']));
                $groups[] = ['title' => $group['title'], 'choose' => $group['choose'], 'selected' => $count, 'valid' => $count === $group['choose']];
            }
            $result['selection'] = ['valid' => $missing === [] && ! in_array(false, array_column($groups, 'valid'), true),
                'selected_subject_ids' => $selected, 'missing_compulsory_subject_ids' => $missing, 'groups' => $groups];
        } else {
            abort_unless(in_array($data['source'], ['released', 'scenario'], true) && ! isset($data['subject_ids']), 422);
            $marks = [];
            if ($data['source'] === 'released') {
                abort_unless(isset($data['session_year_id']) && ! isset($data['marks']), 422);
                $released = app(ReleasedMarks::class)->list($actor, (int) $learner->id, (int) $data['session_year_id'], isset($data['term_id']) ? (int) $data['term_id'] : null);
                $evidence = collect($released['results'])->sortByDesc('released_at')->unique('subject_id')->values();
                $allowed = array_column($rules['subjects'], 'subject_id');
                $result['released_evidence'] = $evidence->whereIn('subject_id', $allowed)->map(fn ($r) => array_intersect_key($r, array_flip(['subject_id', 'percentage_bp', 'release_id', 'grade_name', 'provisional', 'coverage_bp', 'released_at'])))->values()->all();
                $marks = $evidence->map(fn ($r) => ['subject_id' => $r['subject_id'], 'percentage_bp' => $r['percentage_bp']])->all();
            } else {
                abort_unless(! isset($data['session_year_id']) && ! isset($data['term_id']), 422);
                $marks = array_map(fn ($m) => ['subject_id' => (int) $m['subject_id'], 'percentage_bp' => in_array($m['percentage_bp'], [null, ''], true) ? null : (int) $m['percentage_bp']], $data['marks'] ?? []);
                abort_unless(array_diff(array_column($marks, 'subject_id'), array_column($rules['subjects'], 'subject_id')) === [], 422, 'Use subjects from this programme source.');
            }
            $result['score'] = app(PathwayScore::class)->calculate($rules, $marks);
        }
        // A delayed read cannot restore a withdrawn child or replaced source.
        app(PathwayAccess::class)->familyLearner($actor, (int) $learner->id);
        $fresh = $this->read($actor, $id);
        abort_unless($fresh['revision'] === $reference['revision'], 409);

        return $result;
    }
}
