<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\GradeLevel;
use App\Models\Academics\SubjectStandard;
use App\Models\Library\LibraryFramework;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\Library\LibraryTopicPrerequisite;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The platform library's frameworks, sources and topic graph (Action Plan
 * 3.3), managed by Content staff in /saas/library. A framework's code is the
 * value the exam coach reads from a school's curriculum (`framework`, such as
 * CAPS), and a topic's subject code is a code from the published subject
 * standards catalogue for that framework, so a school's subject finds its
 * library topics without any school data reaching the library.
 * engineering/specs/learning/content-library.md.
 */
class LibraryCatalogue
{
    public const SOURCE_KINDS = ['original' => 'Written for Navigate Ed', 'dbe' => 'Department of Basic Education', 'ieb' => 'IEB', 'open_licence' => 'Openly licensed', 'licensed' => 'Licensed from a publisher'];

    /** Prerequisite paths are bounded, as the school mastery graph's are. */
    public const MAX_DEPTH = 100;

    public static function frameworkRules(): array
    {
        return ['code' => ['required', 'string', 'max:16', 'regex:/^[A-Z][A-Z0-9_-]{1,15}$/', Rule::unique('library_frameworks', 'code')],
            'name' => ['required', 'string', 'min:3', 'max:191']];
    }

    public static function sourceRules(): array
    {
        return ['title' => ['required', 'string', 'min:3', 'max:191'], 'kind' => ['required', Rule::in(array_keys(self::SOURCE_KINDS))],
            'licence' => ['required', 'string', 'min:2', 'max:191'], 'attribution' => ['required', 'string', 'min:2', 'max:500'],
            'url' => ['nullable', 'url:https', 'max:2048']];
    }

    public static function topicRules(): array
    {
        return ['subject_code' => ['required', 'string', 'max:32'], 'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
            'name' => ['required', 'string', 'min:2', 'max:191']];
    }

    public static function prerequisiteRules(): array
    {
        return ['prerequisite_ids' => ['present', 'array', 'max:50'], 'prerequisite_ids.*' => ['integer', 'distinct', 'min:1']];
    }

    public function createFramework(User $actor, array $input): LibraryFramework
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::frameworkRules())->validate();

        return DB::transaction(function () use ($actor, $data) {
            $framework = LibraryFramework::create(['code' => $data['code'], 'name' => trim($data['name']), 'created_by' => $actor->id]);
            Audit::record('library_framework_created', $framework, [], ['code' => $framework->code, 'changed_by' => $actor->id]);

            return $framework;
        });
    }

    public function createSource(User $actor, array $input): LibrarySource
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::sourceRules())->validate();

        return DB::transaction(function () use ($actor, $data) {
            $source = LibrarySource::create(['title' => trim($data['title']), 'kind' => $data['kind'], 'licence' => trim($data['licence']),
                'attribution' => trim($data['attribution']), 'url' => $data['url'] ?? null, 'created_by' => $actor->id]);
            Audit::record('library_source_created', $source, [], ['kind' => $source->kind, 'licence' => $source->licence, 'changed_by' => $actor->id]);

            return $source;
        });
    }

    public function createTopic(User $actor, int $frameworkId, array $input): LibraryTopic
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::topicRules())->validate();
        $framework = LibraryFramework::query()->findOrFail($frameworkId);
        $grade = GradeLevel::query()->findOrFail($data['grade_level_id']);
        if (! in_array($data['subject_code'], $this->subjectCodes($framework, (int) $grade->sort_order), true)) {
            throw ValidationException::withMessages(['subject_code' => 'Choose a subject code from the '.$framework->code.' subject catalogue that covers this grade.']);
        }
        $name = trim($data['name']);
        if (LibraryTopic::query()->where(['library_framework_id' => $framework->id, 'subject_code' => $data['subject_code'], 'grade_level_id' => $grade->id, 'name' => $name])->exists()) {
            throw ValidationException::withMessages(['name' => 'This subject and grade already has a topic with that name.']);
        }

        return DB::transaction(function () use ($actor, $framework, $grade, $data, $name) {
            $topic = LibraryTopic::create(['library_framework_id' => $framework->id, 'subject_code' => $data['subject_code'], 'grade_level_id' => $grade->id,
                'topic_key' => (string) Str::uuid(), 'name' => $name, 'created_by' => $actor->id]);
            Audit::record('library_topic_created', $topic, [], ['framework' => $framework->code, 'subject_code' => $topic->subject_code, 'grade_level_id' => $grade->id, 'changed_by' => $actor->id]);

            return $topic;
        });
    }

    /**
     * Replace a topic's prerequisites. They must be topics of the same framework
     * and subject, and the graph must stay acyclic: a prerequisite that would
     * make a topic (indirectly) its own prerequisite is refused.
     */
    public function setPrerequisites(User $actor, int $topicId, array $input): LibraryTopic
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::prerequisiteRules())->validate();

        return DB::transaction(function () use ($actor, $topicId, $data) {
            // One framework's graph changes one save at a time.
            $topic = LibraryTopic::query()->findOrFail($topicId);
            LibraryFramework::query()->lockForUpdate()->findOrFail($topic->library_framework_id);
            $ids = array_map('intval', $data['prerequisite_ids']);
            if (in_array((int) $topic->id, $ids, true)) {
                throw ValidationException::withMessages(['prerequisite_ids' => 'A topic cannot be its own prerequisite.']);
            }
            $siblings = LibraryTopic::query()->where('library_framework_id', $topic->library_framework_id)->where('subject_code', $topic->subject_code)->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (array_diff($ids, $siblings) !== []) {
                throw ValidationException::withMessages(['prerequisite_ids' => 'Prerequisites must be topics of the same framework and subject.']);
            }
            $edges = [];
            foreach (LibraryTopicPrerequisite::query()->whereIn('library_topic_id', $siblings)->where('library_topic_id', '!=', $topic->id)->get() as $edge) {
                $edges[$edge->library_topic_id][$edge->prerequisite_id] = true;
            }
            foreach ($ids as $id) {
                $edges[(int) $topic->id][$id] = true;
            }
            $this->acyclic($edges);
            $before = LibraryTopicPrerequisite::query()->where('library_topic_id', $topic->id)->orderBy('prerequisite_id')->pluck('prerequisite_id')->map(fn ($id) => (int) $id)->all();
            LibraryTopicPrerequisite::query()->where('library_topic_id', $topic->id)->get()->each->delete();
            sort($ids);
            foreach ($ids as $id) {
                LibraryTopicPrerequisite::create(['library_topic_id' => $topic->id, 'prerequisite_id' => $id, 'created_by' => $actor->id]);
            }
            Audit::record('library_prerequisites_changed', $topic, ['prerequisite_ids' => $before], ['prerequisite_ids' => $ids, 'changed_by' => $actor->id]);

            return $topic->load('prerequisites');
        });
    }

    /**
     * The published subject codes of a framework, optionally only those that
     * cover a grade (sort_order R=0 to 12).
     *
     * @return list<string>
     */
    public function subjectCodes(LibraryFramework $framework, ?int $grade = null): array
    {
        return SubjectStandard::query()->whereNotNull('published_at')->where('framework', $framework->code)
            ->when($grade !== null, fn ($q) => $q->where('grade_from', '<=', $grade)->where('grade_to', '>=', $grade))
            ->orderBy('code')->distinct()->pluck('code')->all();
    }

    /** @return array<string, string> code => name */
    public function subjectChoices(LibraryFramework $framework): array
    {
        return SubjectStandard::query()->whereNotNull('published_at')->where('framework', $framework->code)->orderBy('code')->orderByDesc('effective_on')
            ->get(['code', 'name', 'grade_from', 'grade_to'])->unique('code')
            ->mapWithKeys(fn ($s) => [$s->code => $s->code.' · '.$s->name])->all();
    }

    /** Depth from the topics with no prerequisite (1) along every prerequisite path. */
    public function depths(array $edges): array
    {
        $this->acyclic($edges);
        $depth = [];
        $visit = function (int $id) use (&$visit, &$depth, $edges): int {
            if (isset($depth[$id])) {
                return $depth[$id];
            }
            $max = 0;
            foreach (array_keys($edges[$id] ?? []) as $prerequisite) {
                $max = max($max, $visit((int) $prerequisite));
            }

            return $depth[$id] = $max + 1;
        };
        foreach (array_keys($edges) as $id) {
            $visit((int) $id);
        }

        return $depth;
    }

    /** $edges[topic][prerequisite] = true. Kahn's algorithm: every node must drain. */
    private function acyclic(array $edges): void
    {
        $indegree = [];
        foreach ($edges as $from => $targets) {
            $indegree[$from] ??= 0;
            foreach (array_keys($targets) as $to) {
                $indegree[$to] = ($indegree[$to] ?? 0) + 1;
            }
        }
        $queue = array_keys(array_filter($indegree, fn ($degree) => $degree === 0));
        $seen = 0;
        $depth = [];
        while ($queue !== []) {
            $id = array_pop($queue);
            $seen++;
            foreach (array_keys($edges[$id] ?? []) as $to) {
                $depth[$to] = max($depth[$to] ?? 1, ($depth[$id] ?? 1) + 1);
                if ($depth[$to] > self::MAX_DEPTH) {
                    throw ValidationException::withMessages(['prerequisite_ids' => 'Prerequisite paths must stay within 100 topics.']);
                }
                if (--$indegree[$to] === 0) {
                    $queue[] = $to;
                }
            }
        }
        if ($seen !== count($indegree)) {
            throw ValidationException::withMessages(['prerequisite_ids' => 'That would make a cycle: a topic would become its own prerequisite.']);
        }
    }
}
