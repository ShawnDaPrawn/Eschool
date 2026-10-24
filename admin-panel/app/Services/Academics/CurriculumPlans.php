<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Actions\Transfers\YearIdMap;
use App\Http\Resources\Academics\SubjectStandardResource;
use App\Models\Academics\AssessmentTask;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\SubjectStandard;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Policies\Academics\CurriculumPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CurriculumPlans
{
    public static function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'min:1'], 'grade_level_id' => ['required', 'integer', 'min:1'],
            'session_year_id' => ['required', 'integer', 'min:1'], 'standard_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:191'], 'revision' => ['sometimes', 'integer', 'min:1'],
            'annual_components' => ['sometimes', 'nullable', 'array', 'max:8'], 'annual_components.*' => ['integer', 'between:0,10000'],
            'rounding' => ['required', 'in:half_up,half_even,floor'], 'absent_policy' => ['required', 'in:zero,incomplete'],
            'topics' => ['present', 'array', 'max:500'], 'topics.*.key' => ['sometimes', 'uuid'],
            'topics.*.term_id' => ['required', 'integer', 'min:1'], 'topics.*.name' => ['required', 'string', 'max:191'],
            'topics.*.sort_order' => ['sometimes', 'integer', 'between:0,10000'],
            'topics.*.week_from' => ['sometimes', 'nullable', 'integer', 'between:1,53'], 'topics.*.week_to' => ['sometimes', 'nullable', 'integer', 'between:1,53'],
            'assessments' => ['present', 'array', 'max:20'], 'assessments.*.term_id' => ['required', 'integer', 'min:1'],
            'assessments.*.components' => ['present', 'array', 'max:8'], 'assessments.*.components.*' => ['integer', 'between:0,10000'],
            'assessments.*.tasks' => ['present', 'array', 'max:100'], 'assessments.*.tasks.*.key' => ['sometimes', 'uuid'],
            'assessments.*.tasks.*.name' => ['required', 'string', 'max:191'], 'assessments.*.tasks.*.type' => ['required', 'string', 'max:40'],
            'assessments.*.tasks.*.component' => ['required', 'regex:/^[a-z][a-z0-9_]{0,23}$/'],
            'assessments.*.tasks.*.annual_component' => ['sometimes', 'nullable', 'regex:/^[a-z][a-z0-9_]{0,23}$/'],
            'assessments.*.tasks.*.weight_bp' => ['required', 'integer', 'between:0,10000'],
            'assessments.*.tasks.*.annual_weight_bp' => ['sometimes', 'integer', 'between:0,10000'],
            'assessments.*.tasks.*.max_mark' => ['required'], 'assessments.*.tasks.*.due_on' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'assessments.*.tasks.*.topic_keys' => ['present', 'array', 'max:50'], 'assessments.*.tasks.*.topic_keys.*' => ['uuid'],
            'assessments.*.tasks.*.questions' => ['present', 'array', 'max:100'],
            'assessments.*.tasks.*.questions.*.number' => ['required', 'string', 'max:24'],
            'assessments.*.tasks.*.questions.*.max_mark' => ['required'],
            'assessments.*.tasks.*.questions.*.topic_keys' => ['present', 'array', 'max:50'],
            'assessments.*.tasks.*.questions.*.topic_keys.*' => ['uuid'],
        ];
    }

    public function list(User $actor, array $filters): LengthAwarePaginator
    {
        $query = app(AcademicAccess::class)->constrain($actor, CurriculumPlan::query());
        foreach (['subject_id', 'grade_level_id', 'session_year_id', 'state'] as $key) {
            if (isset($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }

        return $query->with('year')->orderByDesc('id')->paginate((int) ($filters['per_page'] ?? 30));
    }

    public function get(User $actor, int $id): CurriculumPlan
    {
        app(CurriculumPolicy::class)->authorise($actor, 'read');
        $plan = CurriculumPlan::query()->findOrFail($id);
        app(CurriculumPolicy::class)->authorise($actor, 'read', $plan);

        return $plan->load('topics', 'assessments.tasks.questions');
    }

    public function save(User $actor, array $input, ?int $id = null): CurriculumPlan
    {
        return $this->persist($actor, $input, $id, null);
    }

    private function persist(User $actor, array $input, ?int $id, ?CurriculumPlan $source): CurriculumPlan
    {
        app(CurriculumPolicy::class)->authorise($actor, 'manage');
        $data = Validator::make($input, self::rules())->validate();

        return DB::transaction(function () use ($actor, $data, $id, $source) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $subject = Subject::query()->with('standard')->findOrFail($data['subject_id']);
            $grade = GradeLevel::query()->findOrFail($data['grade_level_id']);
            $year = SessionYear::query()->findOrFail($data['session_year_id']);
            app(AcademicAccess::class)->context($actor, 'curriculum-manage', (int) $subject->id, (int) $grade->id, (int) $year->id);
            $plan = $id === null ? new CurriculumPlan : CurriculumPlan::query()->lockForUpdate()->findOrFail($id);
            if ($plan->exists) {
                app(CurriculumPolicy::class)->authorise($actor, 'manage', $plan);
                abort_if($plan->state !== 'draft', 409, __('Published definitions are immutable. Copy the plan to a new draft version.'));
                abort_if((int) ($data['revision'] ?? 0) !== (int) $plan->revision, 409, __('This plan changed. Reload it before saving.'));
                foreach (['subject_id', 'grade_level_id', 'session_year_id'] as $field) {
                    if ((int) $data[$field] !== (int) $plan->$field) {
                        throw ValidationException::withMessages([$field => __('Copy the plan to change its subject, grade or school year.')]);
                    }
                }
            }
            $row = $this->normalise($data, $grade, $year);
            $snapshot = $this->reference($row, $subject, $grade);
            $fingerprint = hash('sha256', json_encode([$row, $snapshot], JSON_THROW_ON_ERROR));
            if ($plan->exists && $plan->fingerprint === $fingerprint) {
                return $plan->load('topics', 'assessments.tasks.questions');
            }
            $before = $plan->exists ? $this->data($plan->load('topics', 'assessments.tasks.questions')) : [];
            if (! $plan->exists) {
                $plan->version = 1 + (int) CurriculumPlan::withTrashed()->where('subject_id', $subject->id)->where('grade_level_id', $grade->id)->where('session_year_id', $year->id)->max('version');
                $plan->created_by = $actor->id;
                if ($source !== null) {
                    $plan->source_plan_id = $source->id;
                    $plan->version = max((int) $plan->version, (int) $source->version + 1);
                }
            } else {
                $plan->revision++;
                foreach ($plan->assessments as $assessment) {
                    foreach ($assessment->tasks as $task) {
                        $task->questions()->delete();
                    }
                    $assessment->tasks()->delete();
                }
                $plan->assessments()->delete();
                $plan->topics()->delete();
            }
            $plan->fill(array_intersect_key($row, array_flip(['title', 'subject_id', 'grade_level_id', 'session_year_id', 'annual_components', 'rounding', 'absent_policy'])));
            $plan->standard_id = $snapshot['id'] ?? null;
            $plan->standard_snapshot = $snapshot;
            $plan->fingerprint = $fingerprint;
            $plan->save();
            $this->children($plan, $row);
            $plan->load('topics', 'assessments.tasks.questions');
            Audit::record('academics.curriculum_saved', $plan, $before, $this->data($plan));

            return $plan;
        }, 3);
    }

    private function reference(array $row, Subject $subject, GradeLevel $grade): ?array
    {
        $id = array_key_exists('standard_id', $row) ? $row['standard_id'] : $subject->standard_id;
        if ($id === null) {
            return null;
        }
        $reference = SubjectStandard::query()->whereNotNull('published_at')->findOrFail($id);
        if ($reference->grade_from > $grade->sort_order || $reference->grade_to < $grade->sort_order) {
            if (! array_key_exists('standard_id', $row)) {
                return null; // A school subject can span grades; never reuse another grade's code.
            }
            throw ValidationException::withMessages(['standard_id' => __('Choose a reference that covers this grade.')]);
        }

        return (new SubjectStandardResource($reference))->resolve();
    }

    public function normalise(array $data, GradeLevel $grade, SessionYear $year): array
    {
        $row = array_intersect_key($data, array_flip(['subject_id', 'grade_level_id', 'session_year_id', 'standard_id', 'title', 'annual_components', 'rounding', 'absent_policy', 'topics', 'assessments']));
        foreach (['subject_id', 'grade_level_id', 'session_year_id'] as $key) {
            $row[$key] = (int) $row[$key];
        }
        if (array_key_exists('standard_id', $row)) {
            $row['standard_id'] = $row['standard_id'] === null ? null : (int) $row['standard_id'];
        }
        $row['annual_components'] = isset($row['annual_components']) ? $this->weights($row['annual_components'], 'annual_components', false) : null;
        $topics = [];
        $keys = [];
        $terms = [];
        $term = function (int $id) use (&$terms, $grade, $year): Term {
            if (! isset($terms[$id])) {
                $value = Term::query()->where('session_year_id', $year->id)->findOrFail($id);
                if (! $value->coversGrade((int) $grade->sort_order)) {
                    throw ValidationException::withMessages(['term_id' => __('The term does not cover this grade.')]);
                }
                $terms[$id] = $value;
            }

            return $terms[$id];
        };
        foreach ($row['topics'] as $i => $topic) {
            $t = $term((int) $topic['term_id']);
            $key = $topic['key'] ?? (string) Str::uuid();
            if (isset($keys[$key]) || (($topic['week_from'] ?? null) !== null && ($topic['week_to'] ?? null) !== null && $topic['week_from'] > $topic['week_to'])) {
                throw ValidationException::withMessages(["topics.$i" => __('Topic keys must be unique and week ranges in order.')]);
            }
            $keys[$key] = (int) $t->id;
            $topics[] = ['key' => $key, 'term_id' => (int) $t->id, 'name' => $topic['name'], 'sort_order' => (int) ($topic['sort_order'] ?? $i),
                'week_from' => isset($topic['week_from']) ? (int) $topic['week_from'] : null, 'week_to' => isset($topic['week_to']) ? (int) $topic['week_to'] : null];
        }
        $row['topics'] = $topics;
        $seenTerms = $taskKeys = [];
        $count = $questionCount = 0;
        foreach ($row['assessments'] as $a => &$assessment) {
            $t = $term((int) $assessment['term_id']);
            if (isset($seenTerms[$t->id])) {
                throw ValidationException::withMessages(["assessments.$a.term_id" => __('Choose one assessment plan per term in this version.')]);
            }
            $seenTerms[$t->id] = true;
            $assessment = array_intersect_key($assessment, array_flip(['term_id', 'components', 'tasks']));
            $assessment['term_id'] = (int) $t->id;
            $assessment['components'] = $this->weights($assessment['components'], "assessments.$a.components", false);
            foreach ($assessment['tasks'] as $b => &$task) {
                $count++;
                $field = "assessments.$a.tasks.$b";
                $task = array_intersect_key($task, array_flip(['key', 'name', 'type', 'component', 'annual_component', 'weight_bp', 'annual_weight_bp', 'max_mark', 'due_on', 'topic_keys', 'questions']));
                $task['key'] = $task['key'] ?? (string) Str::uuid();
                if (isset($taskKeys[$task['key']])) {
                    throw ValidationException::withMessages([$field => __('Task keys must be unique within the curriculum.')]);
                }
                $taskKeys[$task['key']] = true;
                $task['weight_bp'] = (int) $task['weight_bp'];
                $task['annual_weight_bp'] = (int) ($task['annual_weight_bp'] ?? 0);
                $task['annual_component'] = $task['annual_component'] ?? null;
                $task['due_on'] = $task['due_on'] ?? null;
                $task['max_mark_units'] = AcademicNumbers::units($task['max_mark'], $field.'.max_mark');
                if ($task['max_mark_units'] === 0) {
                    throw ValidationException::withMessages([$field.'.max_mark' => __('Maximum marks must be greater than zero.')]);
                }
                $task['max_mark'] = AcademicNumbers::decimal($task['max_mark_units']);
                if ($task['due_on'] !== null && ($task['due_on'] < substr((string) $year->start_date, 0, 10) || $task['due_on'] > substr((string) $year->end_date, 0, 10))) {
                    throw ValidationException::withMessages([$field.'.due_on' => __('Choose a due date within the school year.')]);
                }
                $task['topic_keys'] = $this->topicKeys($task['topic_keys'], $keys, (int) $t->id, $field.'.topic_keys');
                $numbers = [];
                foreach ($task['questions'] as $c => &$question) {
                    $questionCount++;
                    $question = array_intersect_key($question, array_flip(['number', 'max_mark', 'topic_keys']));
                    if (isset($numbers[$question['number']])) {
                        throw ValidationException::withMessages([$field.'.questions' => __('Question numbers must be unique within a task.')]);
                    }
                    $numbers[$question['number']] = true;
                    $question['max_mark_units'] = AcademicNumbers::units($question['max_mark'], $field.".questions.$c.max_mark");
                    if ($question['max_mark_units'] === 0) {
                        throw ValidationException::withMessages([$field.'.questions' => __('Maximum marks must be greater than zero.')]);
                    }
                    $question['max_mark'] = AcademicNumbers::decimal($question['max_mark_units']);
                    $question['topic_keys'] = $this->topicKeys($question['topic_keys'], $keys, (int) $t->id, $field.".questions.$c.topic_keys");
                }
                unset($question);
            }
            unset($task);
        }
        unset($assessment);
        if ($count > 500 || $questionCount > 5000) {
            throw ValidationException::withMessages(['assessments' => __('Use at most 500 tasks and 5000 questions per curriculum version.')]);
        }

        return $row;
    }

    public function weights(array $input, string $field, bool $complete): array
    {
        $weights = [];
        foreach ($input as $key => $value) {
            if (! preg_match('/^[a-z][a-z0-9_]{0,23}$/D', (string) $key) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0 || (int) $value > 10000) {
                throw ValidationException::withMessages([$field => __('Use named components and percentages between zero and 100.')]);
            }
            $weights[$key] = (int) $value;
        }
        ksort($weights);
        if (($complete && array_sum($weights) !== 10000) || array_sum($weights) > 10000) {
            throw ValidationException::withMessages([$field => __('Published assessment components must total 100%.')]);
        }

        return $weights;
    }

    private function topicKeys(array $values, array $keys, int $termId, string $field): array
    {
        $values = array_values(array_unique($values));
        foreach ($values as $key) {
            if (($keys[$key] ?? null) !== $termId) {
                throw ValidationException::withMessages([$field => __('Choose topics from this curriculum and term.')]);
            }
        }
        sort($values);

        return $values;
    }

    private function children(CurriculumPlan $plan, array $row): void
    {
        $sources = collect();
        if ($plan->source_plan_id !== null) {
            $source = CurriculumPlan::query()->findOrFail($plan->source_plan_id);
            if ((int) $source->session_year_id === (int) $plan->session_year_id) {
                $sources = AssessmentTask::query()->whereHas('assessment', fn ($q) => $q->where('curriculum_plan_id', $source->id))
                    ->with('questions', 'assessment')->get()->keyBy('key');
            }
        }
        foreach ($row['topics'] as $topic) {
            $plan->topics()->create($topic);
        }
        foreach ($row['assessments'] as $assessment) {
            $saved = $plan->assessments()->create(['term_id' => $assessment['term_id'], 'components' => $assessment['components']]);
            foreach ($assessment['tasks'] as $task) {
                $questions = $task['questions'];
                unset($task['questions'], $task['max_mark']);
                $origin = $sources->get($task['key']);
                if ($origin !== null) {
                    $oldQuestions = $origin->questions->mapWithKeys(fn ($q) => [$q->number => $q->max_mark_units])->all();
                    $newQuestions = collect($questions)->mapWithKeys(fn ($q) => [$q['number'] => $q['max_mark_units']])->all();
                    ksort($oldQuestions);
                    ksort($newQuestions);
                    if ((int) $origin->assessment->term_id !== (int) $saved->term_id || $origin->max_mark_units !== $task['max_mark_units'] || $oldQuestions !== $newQuestions) {
                        throw ValidationException::withMessages(['tasks' => __('Use a new task key for a changed term or mark structure.')]);
                    }
                    $task['source_task_id'] = $origin->source_task_id ?? $origin->id;
                }
                $stored = $saved->tasks()->create($task);
                foreach ($questions as $question) {
                    unset($question['max_mark']);
                    $stored->questions()->create($question);
                }
            }
        }
    }

    public function publish(User $actor, int $planId, int $assessmentId, int $revision): CurriculumPlan
    {
        app(CurriculumPolicy::class)->authorise($actor, 'publish');

        return DB::transaction(function () use ($actor, $planId, $assessmentId, $revision) {
            $plan = CurriculumPlan::query()->lockForUpdate()->findOrFail($planId);
            app(CurriculumPolicy::class)->authorise($actor, 'publish', $plan);
            $assessment = $plan->assessments()->with('tasks.questions', 'term')->lockForUpdate()->findOrFail($assessmentId);
            if ($assessment->state === 'published') {
                return $plan->load('topics', 'assessments.tasks.questions');
            }
            abort_if((int) $plan->revision !== $revision, 409, __('This plan changed. Reload it before saving.'));
            $grade = GradeLevel::query()->findOrFail($plan->grade_level_id);
            $term = Term::query()->where('session_year_id', $plan->session_year_id)->forGrade((int) $grade->sort_order)->findOrFail($assessment->term_id);
            $today = CarbonImmutable::now(app(CurrentSchool::class)->school()?->timezone ?? 'Africa/Johannesburg')->toDateString();
            abort_if($term->marks_lock_on !== null && $today >= $term->marks_lock_on->toDateString(), 409, __('This term is locked. Review its lock date before publishing new assessment definitions.'));
            $components = $this->weights($assessment->components, 'components', true);
            $sums = array_fill_keys(array_keys($components), 0);
            foreach ($assessment->tasks as $task) {
                if (! array_key_exists($task->component, $sums)) {
                    throw ValidationException::withMessages(['tasks' => __('Every task needs a component from the assessment plan.')]);
                }
                if ($task->annual_weight_bp > 0 && ($task->annual_component === null
                    || ($plan->annual_components !== null && ! array_key_exists($task->annual_component, $plan->annual_components)))) {
                    throw ValidationException::withMessages(['annual_component' => __('Choose a valid annual component for tasks with an annual weight.')]);
                }
                $sums[$task->component] += $task->weight_bp;
                if ($task->questions->isNotEmpty() && $task->questions->sum('max_mark_units') !== $task->max_mark_units) {
                    throw ValidationException::withMessages(['questions' => __('Question maxima must total the task maximum before publication.')]);
                }
            }
            foreach ($components as $key => $weight) {
                if (($weight > 0 && $sums[$key] !== 10000) || ($weight === 0 && $sums[$key] !== 0)) {
                    throw ValidationException::withMessages(['tasks' => __('Task weights must total 100% within each included component.')]);
                }
            }
            $assessment->state = 'published';
            $assessment->published_by = $actor->id;
            $assessment->published_at = now();
            $assessment->save();
            $plan->state = 'published';
            $plan->revision++;
            $plan->save();
            Audit::record('academics.assessment_plan_published', $assessment, ['state' => 'draft'], ['state' => 'published', 'curriculum_plan_id' => $planId, 'version' => $plan->version]);

            return $plan->load('topics', 'assessments.tasks.questions');
        });
    }

    public function copy(User $actor, int $sourceId, int $yearId): CurriculumPlan
    {
        $source = $this->get($actor, $sourceId);
        app(CurriculumPolicy::class)->authorise($actor, 'manage', $source);
        $target = SessionYear::query()->findOrFail($yearId);
        app(AcademicAccess::class)->context($actor, 'curriculum-manage', (int) $source->subject_id, (int) $source->grade_level_id, $yearId);

        return DB::transaction(function () use ($actor, $source, $target, $yearId) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $source = CurriculumPlan::query()->with('topics', 'assessments.tasks.questions')->lockForUpdate()->findOrFail($source->id);
            app(CurriculumPolicy::class)->authorise($actor, 'manage', $source);
            app(AcademicAccess::class)->context($actor, 'curriculum-manage', (int) $source->subject_id, (int) $source->grade_level_id, $yearId);
            $existing = CurriculumPlan::query()->where('source_plan_id', $source->id)->where('session_year_id', $yearId)->where('state', 'draft')->first();
            if ($existing) {
                return $this->get($actor, (int) $existing->id);
            }
            $row = $this->data($source);
            $map = (int) $source->session_year_id === $yearId ? null : YearIdMap::terms((int) $source->session_year_id, $yearId);
            $shift = CarbonImmutable::parse($target->start_date)->year - CarbonImmutable::parse($source->year->start_date)->year;
            $row['session_year_id'] = $yearId;
            foreach ($row['topics'] as &$topic) {
                $topic['term_id'] = $map === null ? $topic['term_id'] : YearIdMap::term($topic['term_id'], $map);
            }
            unset($topic);
            foreach ($row['assessments'] as &$assessment) {
                $assessment['term_id'] = $map === null ? $assessment['term_id'] : YearIdMap::term($assessment['term_id'], $map);
                foreach ($assessment['tasks'] as &$task) {
                    if ($task['due_on'] !== null) {
                        [$y, $m, $d] = array_map('intval', explode('-', $task['due_on']));
                        if (! checkdate($m, $d, $y + $shift)) {
                            throw ValidationException::withMessages(['due_on' => __('A copied due date does not exist in the target year. Review the dates.')]);
                        }
                        $task['due_on'] = sprintf('%04d-%02d-%02d', $y + $shift, $m, $d);
                    }
                }
                unset($task);
            }
            unset($assessment);
            $copy = $this->persist($actor, $row, null, $source);
            Audit::record('academics.curriculum_copied', $copy, [], ['source_plan_id' => $source->id, 'session_year_id' => $yearId]);

            return $copy->load('topics', 'assessments.tasks.questions');
        }, 3);
    }

    public function data(CurriculumPlan $plan): array
    {
        return ['id' => (int) $plan->id, 'subject_id' => (int) $plan->subject_id, 'grade_level_id' => (int) $plan->grade_level_id,
            'session_year_id' => (int) $plan->session_year_id, 'standard_id' => $plan->standard_id === null ? null : (int) $plan->standard_id,
            'title' => $plan->title, 'version' => (int) $plan->version, 'revision' => (int) $plan->revision, 'state' => $plan->state,
            'source_plan_id' => $plan->source_plan_id === null ? null : (int) $plan->source_plan_id, 'standard_snapshot' => $plan->standard_snapshot,
            'annual_components' => $plan->annual_components, 'rounding' => $plan->rounding, 'absent_policy' => $plan->absent_policy,
            'topics' => $plan->topics->map(fn ($t) => ['id' => (int) $t->id, 'key' => $t->key, 'term_id' => (int) $t->term_id,
                'name' => $t->name, 'sort_order' => $t->sort_order, 'week_from' => $t->week_from, 'week_to' => $t->week_to])->all(),
            'assessments' => $plan->assessments->map(fn ($a) => ['id' => (int) $a->id, 'term_id' => (int) $a->term_id, 'state' => $a->state,
                'published_at' => $a->published_at?->toISOString(), 'components' => $a->components,
                'tasks' => $a->tasks->map(fn ($t) => ['id' => (int) $t->id, 'key' => $t->key, 'name' => $t->name, 'type' => $t->type,
                    'source_task_id' => $t->source_task_id === null ? null : (int) $t->source_task_id,
                    'component' => $t->component, 'annual_component' => $t->annual_component, 'weight_bp' => $t->weight_bp,
                    'annual_weight_bp' => $t->annual_weight_bp, 'max_mark' => AcademicNumbers::decimal($t->max_mark_units),
                    'due_on' => $t->due_on?->toDateString(), 'topic_keys' => $t->topic_keys,
                    'questions' => $t->questions->map(fn ($q) => ['id' => (int) $q->id, 'number' => $q->number,
                        'max_mark' => AcademicNumbers::decimal($q->max_mark_units), 'topic_keys' => $q->topic_keys])->all()])->all()])->all()];
    }
}
