<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\CurriculumTopic;
use App\Models\Growth\MasterySettings;
use App\Models\Growth\MasteryTopic;
use App\Models\Growth\TopicLink;
use App\Models\Platform\School;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\AcademicAccess;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TopicGraph
{
    /** Derived anchors retain stable topic UUIDs across published plan versions/years. */
    public function sync(int $subjectId): void
    {
        Subject::query()->findOrFail($subjectId);
        $sources = CurriculumTopic::query()->with('curriculum')->whereHas('curriculum', fn ($q) => $q->where('subject_id', $subjectId)->where('state', 'published'))
            ->orderByDesc('id')->limit(5001)->get();
        abort_if($sources->count() > 5000, 422, 'Mastery subject source exceeds its bounded catalogue.');
        foreach ($sources->unique('key') as $source) {
            MasteryTopic::updateOrCreate(['subject_id' => $subjectId, 'topic_key' => $source->key],
                ['curriculum_topic_id' => $source->id, 'grade_level_id' => $source->curriculum->grade_level_id, 'name' => $source->name]);
        }
    }

    public function nodes(int $subjectId): Collection
    {
        return MasteryTopic::query()->where('subject_id', $subjectId)->whereHas('source', fn ($q) => $q->whereNull('curriculum_topics.deleted_at')
            ->whereHas('curriculum', fn ($c) => $c->where('state', 'published')->where('subject_id', $subjectId)))->orderBy('id')->limit(5001)->get();
    }

    public function settings(): array
    {
        $setting = MasterySettings::query()->first();

        return ['family_marks_enabled' => $setting?->family_marks_enabled ?? false, 'revision' => (int) ($setting?->revision ?? 0), 'policy_version' => MasteryScore::VERSION];
    }

    public function save(User $actor, int $subjectId, array $input): array
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->writable('mastery'), 403, 'The mastery module is disabled.');
        app(AcademicAccess::class)->actor($actor, 'curriculum-publish');
        $data = Validator::make($input, ['expected_revision' => ['required', 'integer', 'min:0'],
            'family_marks_enabled' => ['required', 'boolean'], 'links' => ['required', 'array', 'max:10000'],
            'links.*.topic_id' => ['required', 'integer', 'min:1'], 'links.*.prerequisite_id' => ['required', 'integer', 'min:1'],
            'links.*.source' => ['required', 'string', 'min:3', 'max:1000']])->validate();

        return DB::transaction(function () use ($actor, $subjectId, $data) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $gate = app(ModuleGate::class);
            $gate->forget();
            abort_unless($gate->writable('mastery'), 403, 'The mastery module is disabled.');
            app(AcademicAccess::class)->actor($actor, 'curriculum-publish');
            $setting = MasterySettings::query()->first() ?? new MasterySettings(['family_marks_enabled' => false, 'revision' => 0]);
            abort_unless((int) $setting->revision === (int) $data['expected_revision'], 409, 'Mastery configuration changed; reload before saving.');
            $this->sync($subjectId);
            $nodes = $this->nodes($subjectId)->keyBy('id');
            $edges = [];
            foreach ($data['links'] as $link) {
                $from = (int) $link['topic_id'];
                $to = (int) $link['prerequisite_id'];
                abort_unless($nodes->has($from) && $nodes->has($to), 404);
                if ($from === $to || isset($edges[$from][$to])) {
                    throw ValidationException::withMessages(['links' => 'Each prerequisite must be distinct from itself and from duplicate edges.']);
                }
                $edges[$from][$to] = true;
            }
            $this->acyclic($edges);
            $beforeLinks = TopicLink::query()->where('subject_id', $subjectId)->get(['topic_id', 'prerequisite_id', 'source'])->toArray();
            TopicLink::query()->where('subject_id', $subjectId)->delete();
            foreach ($data['links'] as $link) {
                TopicLink::create(['subject_id' => $subjectId, 'topic_id' => $link['topic_id'], 'prerequisite_id' => $link['prerequisite_id'],
                    'source' => trim($link['source']), 'recorded_by' => $actor->id]);
            }
            $before = $this->settings();
            $setting->fill(['family_marks_enabled' => (bool) $data['family_marks_enabled'], 'revision' => (int) $setting->revision + 1])->save();
            Audit::record('growth.mastery_graph_changed', $setting, $before + ['links' => $beforeLinks], ['links' => $data['links'], 'subject_id' => $subjectId, 'revision' => $setting->revision,
                'family_marks_enabled' => $setting->family_marks_enabled, 'edge_count' => count($data['links'])]);

            return $this->data($subjectId);
        });
    }

    public function data(int $subjectId): array
    {
        Subject::query()->findOrFail($subjectId);
        $nodes = $this->nodes($subjectId);
        $allowed = $nodes->pluck('id')->all();

        return ['subject_id' => $subjectId, 'settings' => $this->settings(), 'nodes' => $nodes->map(fn ($n) => ['id' => (int) $n->id, 'topic_key' => $n->topic_key,
            'name' => $n->name, 'grade_level_id' => (int) $n->grade_level_id])->all(),
            'links' => TopicLink::query()->where('subject_id', $subjectId)->whereIn('topic_id', $allowed)->whereIn('prerequisite_id', $allowed)->orderBy('id')->get()
                ->map(fn ($l) => ['topic_id' => (int) $l->topic_id, 'prerequisite_id' => (int) $l->prerequisite_id, 'source' => $l->source])->all()];
    }

    private function acyclic(array $edges): void
    {
        $indegree = [];
        foreach ($edges as $from => $targets) {
            $indegree[$from] ??= 0;
            foreach ($targets as $to => $_) {
                $indegree[$to] = ($indegree[$to] ?? 0) + 1;
            }
        }
        $queue = array_keys(array_filter($indegree, fn ($degree) => $degree === 0));
        $seen = 0;
        $depth = [];
        while ($queue !== []) {
            $id = array_pop($queue);
            $seen++;
            foreach ($edges[$id] ?? [] as $to => $_) {
                $depth[$to] = max($depth[$to] ?? 1, ($depth[$id] ?? 1) + 1);
                if ($depth[$to] > 100) {
                    throw ValidationException::withMessages(['links' => 'Prerequisite paths must fit the bounded 100-topic traversal.']);
                }
                if (--$indegree[$to] === 0) {
                    $queue[] = $to;
                }
            }
        }
        if ($seen !== count($indegree)) {
            throw ValidationException::withMessages(['links' => 'Prerequisites must form a directed acyclic graph.']);
        }
    }
}
