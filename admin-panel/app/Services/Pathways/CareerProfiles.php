<?php

declare(strict_types=1);

namespace App\Services\Pathways;

use App\Models\Growth\MasteryTopic;
use App\Models\Pathways\AdmissionRequirement;
use App\Models\Pathways\CareerProfile;
use App\Models\Pathways\CareerProfileVersion;
use App\Models\Pathways\PathwayOperation;
use App\Models\Platform\School;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Career profiles in the school's own pathways catalogue (Action Plan 4.9).
 * Each revision cites a dated public source; each route cites its own source.
 * Spec: engineering/specs/community/pathways.md, "Career profiles".
 */
class CareerProfiles
{
    public const ROUTE_TYPES = ['university', 'tvet', 'learnership'];

    public const REQUIREMENTS = ['required', 'recommended'];

    public function save(User $actor, ?string $id, array $input): array
    {
        app(PathwayAccess::class)->manager($actor, true);
        $data = $this->input($input);
        $hash = hash('sha256', json_encode(['career_profile', $id, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $id, $data, $hash) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(PathwayAccess::class)->manager($actor, true);
            $old = PathwayOperation::where('actor_id', $actor->id)->where('operation_id', $data['operation_id'])->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This operation already contains another source change.');

                return $old->receipt;
            }
            $profile = $id === null ? null : CareerProfile::findOrFail($id);
            abort_unless(($profile?->revision ?? 0) === $data['revision'], 409, 'Refresh this career profile before changing it.');
            abort_if($profile === null && $data['status'] !== 'published', 422);
            $profile ??= CareerProfile::create(['status' => $data['status'], 'revision' => 0, 'expires_on' => $data['expires_on']]);
            $version = CareerProfileVersion::create(array_intersect_key($data, array_flip([
                'status', 'title', 'summary', 'source_title', 'source_url', 'source_year', 'reviewed_on', 'expires_on', 'details',
            ])) + ['profile_id' => $profile->id, 'revision' => $data['revision'] + 1, 'reviewed_by' => $actor->id]);
            $profile->update(['revision' => $version->revision, 'current_version_id' => $version->id, 'status' => $version->status, 'expires_on' => $version->expires_on]);
            app(PathwayAccess::class)->manager($actor, true);
            $result = $this->data($profile);
            PathwayOperation::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        }, 3);
    }

    /** The service validates again, whatever the caller already checked. */
    public function input(array $input): array
    {
        $today = app(AcademicAuthority::class)->today();
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'status' => ['required', 'in:published,withdrawn'],
            'title' => ['required', 'string', 'max:191'], 'summary' => ['required', 'string', 'max:3000'],
            'source_title' => ['required', 'string', 'max:240'], 'source_url' => ['required', 'url:https', 'max:2048'],
            'source_year' => ['required', 'integer', 'between:2000,2100'], 'reviewed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'expires_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:reviewed_on'],
            'details' => ['required', 'array:subjects,topic_ids,routes'],
            'details.subjects' => ['required', 'array', 'list', 'min:1', 'max:20'],
            'details.subjects.*' => ['required', 'array:subject_ids,minimum_bp,requirement'],
            'details.subjects.*.subject_ids' => ['required', 'array', 'list', 'min:1', 'max:5'], 'details.subjects.*.subject_ids.*' => ['integer', 'min:1'],
            'details.subjects.*.minimum_bp' => ['present', 'nullable', 'integer', 'between:0,10000'],
            'details.subjects.*.requirement' => ['required', 'in:'.implode(',', self::REQUIREMENTS)],
            'details.topic_ids' => ['present', 'array', 'list', 'max:50'], 'details.topic_ids.*' => ['integer', 'min:1', 'distinct'],
            'details.routes' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'details.routes.*' => ['required', 'array:type,title,provider,entry_note,source_title,source_url,programme_id'],
            'details.routes.*.type' => ['required', 'in:'.implode(',', self::ROUTE_TYPES)],
            'details.routes.*.title' => ['required', 'string', 'max:191'], 'details.routes.*.provider' => ['required', 'string', 'max:191'],
            'details.routes.*.entry_note' => ['required', 'string', 'max:1000'],
            'details.routes.*.source_title' => ['required', 'string', 'max:240'], 'details.routes.*.source_url' => ['required', 'url:https', 'max:2048'],
            'details.routes.*.programme_id' => ['present', 'nullable', 'uuid'],
        ])->validate();
        $references = app(PathwayReferences::class);
        $data = $references->sourced($data, ['title', 'source_title', 'summary']);
        $data['revision'] = (int) $data['revision'];
        $data['source_year'] = (int) $data['source_year'];
        $archived = $data['status'] === 'withdrawn';
        $subjects = [];
        $all = [];
        foreach ($data['details']['subjects'] as $row) {
            $ids = array_map('intval', $row['subject_ids']);
            abort_unless(count($ids) === count(array_unique($ids)) && array_intersect($ids, $all) === [], 422, 'List each subject once in this career profile.');
            $all = array_merge($all, $ids);
            $subjects[] = ['subject_ids' => $ids, 'minimum_bp' => in_array($row['minimum_bp'], [null, ''], true) ? null : (int) $row['minimum_bp'], 'requirement' => $row['requirement']];
        }
        abort_unless(($archived ? Subject::withTrashed() : Subject::query())->whereIn('id', $all)->count() === count($all), 422, 'Choose current subjects from this school.');
        $topics = array_map('intval', $data['details']['topic_ids']);
        if ($topics !== []) {
            $known = $archived ? MasteryTopic::query()->whereIn('subject_id', $all)->whereIn('id', $topics)->pluck('id')->all()
                : collect(array_unique($all))->flatMap(fn ($subject) => app(TopicGraph::class)->nodes((int) $subject)->pluck('id'))->all();
            abort_unless(array_diff($topics, array_map('intval', $known)) === [], 422, 'Link published curriculum topics of this profile\'s subjects.');
        }
        $routes = [];
        foreach ($data['details']['routes'] as $route) {
            foreach (['title', 'provider', 'entry_note', 'source_title'] as $key) {
                $route[$key] = trim((string) $route[$key]);
                abort_unless($route[$key] !== '' && mb_check_encoding($route[$key], 'UTF-8') && ! str_contains($route[$key], "\0"), 422);
            }
            $references->publicUrl($route['source_url']);
            if ($route['programme_id'] !== null) {
                abort_unless($route['type'] === 'university', 422, 'Only a university route links a programme source.');
                abort_unless(AdmissionRequirement::whereKey($route['programme_id'])->where('kind', 'programme')->exists(), 422, 'Link a programme source from this school.');
            }
            $routes[] = array_intersect_key($route, array_flip(['type', 'title', 'provider', 'entry_note', 'source_title', 'source_url', 'programme_id']));
        }
        $data['details'] = ['subjects' => $subjects, 'topic_ids' => $topics, 'routes' => $routes];

        return $data;
    }

    /** Managers see every profile; guidance readers see published ones, stale ones flagged. */
    public function current(string $id, bool $manager = false): CareerProfile
    {
        $row = CareerProfile::findOrFail($id);
        abort_unless($manager || $row->status === 'published', 409, 'This career profile was withdrawn.');

        return $row;
    }

    public function data(CareerProfile $row): array
    {
        $version = CareerProfileVersion::where('profile_id', $row->id)->findOrFail($row->current_version_id);

        return ['id' => $row->id, 'revision' => (int) $row->revision, 'status' => $row->status, 'title' => $version->title, 'summary' => $version->summary,
            'source_title' => $version->source_title, 'source_url' => $version->source_url, 'source_year' => (int) $version->source_year,
            'reviewed_on' => $version->reviewed_on->toDateString(), 'expires_on' => $version->expires_on->toDateString(),
            'stale' => $version->expires_on->toDateString() < app(AcademicAuthority::class)->today(), 'details' => $version->details];
    }

    public function read(User $actor, string $id, bool $manager): array
    {
        $manager ? app(PathwayAccess::class)->manager($actor) : app(CareerGuidance::class)->actor($actor);

        return $this->data($this->current($id, $manager));
    }

    /** The manager listing: 25 per page, every status, stale entries flagged. */
    public function catalogue(User $actor, int $page = 1, bool $manager = true): array
    {
        abort_unless($page > 0 && $page <= 100000, 422);
        $manager ? app(PathwayAccess::class)->manager($actor) : app(CareerGuidance::class)->actor($actor);
        $query = CareerProfile::query();
        if (! $manager) {
            $query->where('status', 'published');
        }
        $rows = $query->orderByDesc('updated_at')->orderBy('id')->offset(($page - 1) * 25)->limit(26)->get();
        $items = [];
        foreach ($rows->take(25) as $row) {
            try {
                $items[] = $this->data($row);
            } catch (ModelNotFoundException) {
                // A profile without a current revision is not shown.
            }
        }

        return ['items' => $items, 'next_page' => $rows->count() > 25 ? $page + 1 : null];
    }

    /** Published profiles for the guidance picker, bounded. */
    public function choices(User $actor): array
    {
        app(CareerGuidance::class)->actor($actor);
        $rows = CareerProfile::query()->where('status', 'published')->orderBy('id')->limit(501)->get();
        abort_if($rows->count() > 500, 422, 'Narrow the career profile catalogue.');
        $choices = [];
        foreach ($rows as $row) {
            $data = $this->data($row);
            $choices[$data['id']] = $data['title'].($data['stale'] ? ' ('.__('needs review').')' : '');
        }
        asort($choices);

        return $choices;
    }
}
