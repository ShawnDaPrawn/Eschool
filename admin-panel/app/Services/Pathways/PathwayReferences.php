<?php

declare(strict_types=1);

namespace App\Services\Pathways;

use App\Models\Pathways\AdmissionRequirement;
use App\Models\Pathways\AdmissionRequirementVersion;
use App\Models\Pathways\PathwayOperation;
use App\Models\Platform\School;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PathwayReferences
{
    public function save(User $actor, ?string $id, array $input): array
    {
        app(PathwayAccess::class)->manager($actor, true);
        $data = $this->input($input);
        $hash = hash('sha256', json_encode([$id, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $id, $data, $hash) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(PathwayAccess::class)->manager($actor, true);
            $old = PathwayOperation::where('actor_id', $actor->id)->where('operation_id', $data['operation_id'])->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This operation already contains another source change.');

                return $old->receipt;
            }
            $reference = $id === null ? null : AdmissionRequirement::findOrFail($id);
            abort_unless(($reference?->revision ?? 0) === $data['revision'], 409, 'Refresh this pathway source before changing it.');
            abort_unless($reference === null || $reference->kind === $data['kind'], 422, 'A source kind cannot change between revisions.');
            abort_if($reference === null && $data['status'] !== 'published', 422);
            $reference ??= AdmissionRequirement::create(['kind' => $data['kind'], 'status' => $data['status'], 'revision' => 0, 'expires_on' => $data['expires_on']]);
            $version = AdmissionRequirementVersion::create(array_intersect_key($data, array_flip([
                'status', 'title', 'institution', 'source_title', 'source_url', 'source_year', 'reviewed_on', 'expires_on', 'guidance', 'rules',
            ])) + ['requirement_id' => $reference->id, 'revision' => $data['revision'] + 1, 'reviewed_by' => $actor->id]);
            $reference->update(['revision' => $version->revision, 'current_version_id' => $version->id, 'status' => $version->status, 'expires_on' => $version->expires_on]);
            app(PathwayAccess::class)->manager($actor, true);
            $result = $this->data($reference);
            PathwayOperation::create(['actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        }, 3);
    }

    /** Both JSON and labelled HTML forms use the same bounded source policy. */
    public function input(array $input): array
    {
        $today = app(AcademicAuthority::class)->today();
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'kind' => ['required', 'in:programme,subject_choice_guide'], 'status' => ['required', 'in:published,withdrawn'],
            'title' => ['required', 'string', 'max:191'], 'institution' => ['nullable', 'string', 'max:191'],
            'source_title' => ['required', 'string', 'max:240'], 'source_url' => ['required', 'url:https', 'max:2048'],
            'source_year' => ['required', 'integer', 'between:2000,2100'], 'reviewed_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'expires_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:reviewed_on'],
            'guidance' => ['required', 'string', 'max:6000'], 'rules' => ['required', 'array'],
        ])->validate();
        $url = parse_url($data['source_url']);
        abort_unless(($url['scheme'] ?? '') === 'https' && ! isset($url['user']) && ! isset($url['pass'])
            && isset($url['host']) && str_contains($url['host'], '.') && filter_var($url['host'], FILTER_VALIDATE_IP) === false, 422, 'Use the public HTTPS source address without credentials.');
        foreach (['title', 'source_title', 'guidance'] as $key) {
            $data[$key] = trim($data[$key]);
            abort_unless($data[$key] !== '' && mb_check_encoding($data[$key], 'UTF-8') && ! str_contains($data[$key], "\0"), 422);
        }
        if ($data['status'] === 'published') {
            abort_unless($data['expires_on'] >= $today && $data['expires_on'] <= CarbonImmutable::parse($data['reviewed_on'])->addDays(366)->toDateString(), 422, 'Review current sources at least annually.');
            abort_unless($data['reviewed_on'] >= now(app(CurrentSchool::class)->school()->timezone)->subDays(366)->toDateString(), 422, 'Review the source before publishing it again.');
        }
        $data['revision'] = (int) $data['revision'];
        $data['source_year'] = (int) $data['source_year'];
        $data['institution'] = isset($data['institution']) ? trim($data['institution']) : null;
        if ($data['kind'] === 'programme') {
            abort_unless($data['institution'] !== null && $data['institution'] !== '', 422, 'Name the institution in this programme source.');
            $data['rules'] = $this->programme($data['rules'], $data['status'] === 'withdrawn');
        } else {
            $data['rules'] = $this->guide($data['rules'], $data['status'] === 'withdrawn');
        }

        return $data;
    }

    private function subjects(array $ids, bool $archived = false): void
    {
        abort_unless(count($ids) === count(array_unique($ids)) && ($archived ? Subject::withTrashed() : Subject::query())->whereIn('id', $ids)->count() === count($ids), 422, 'Choose current subjects from this school.');
    }

    private function programme(array $input, bool $archived): array
    {
        $rules = Validator::make(['rules' => $input], [
            'rules' => ['required', 'array:subjects,bands,best_n,minimum_score'],
            'rules.subjects' => ['required', 'array', 'list', 'min:1', 'max:50'],
            'rules.subjects.*' => ['required', 'array:subject_id,minimum_bp,counts_for_score'],
            'rules.subjects.*.subject_id' => ['required', 'integer', 'min:1', 'distinct'],
            'rules.subjects.*.minimum_bp' => ['present', 'nullable', 'integer', 'between:0,10000'],
            'rules.subjects.*.counts_for_score' => ['required', 'boolean'],
            'rules.bands' => ['required', 'array', 'list', 'min:1', 'max:20'],
            'rules.bands.*' => ['required', 'array:minimum_bp,points'],
            'rules.bands.*.minimum_bp' => ['required', 'integer', 'between:0,10000', 'distinct'],
            'rules.bands.*.points' => ['required', 'integer', 'between:0,20'],
            'rules.best_n' => ['required', 'integer', 'between:1,20'], 'rules.minimum_score' => ['required', 'integer', 'between:0,400'],
        ])->validate()['rules'];
        $rules['subjects'] = array_map(fn ($s) => ['subject_id' => (int) $s['subject_id'], 'minimum_bp' => in_array($s['minimum_bp'], [null, ''], true) ? null : (int) $s['minimum_bp'], 'counts_for_score' => (bool) $s['counts_for_score']], $rules['subjects']);
        $this->subjects(array_column($rules['subjects'], 'subject_id'), $archived);
        $rules['bands'] = array_map(fn ($b) => ['minimum_bp' => (int) $b['minimum_bp'], 'points' => (int) $b['points']], $rules['bands']);
        usort($rules['bands'], fn ($a, $b) => $b['minimum_bp'] <=> $a['minimum_bp']);
        abort_unless($rules['bands'][count($rules['bands']) - 1]['minimum_bp'] === 0, 422, 'The source score bands must cover zero through one hundred percent.');
        $last = 21;
        foreach ($rules['bands'] as $band) {
            abort_unless($band['points'] <= $last, 422, 'Higher marks cannot receive fewer points.');
            $last = $band['points'];
        }
        $rules['best_n'] = (int) $rules['best_n'];
        $rules['minimum_score'] = (int) $rules['minimum_score'];
        abort_unless(count(array_filter($rules['subjects'], fn ($s) => $s['counts_for_score'])) >= $rules['best_n'], 422, 'Choose enough score-bearing subjects.');
        abort_unless($rules['minimum_score'] <= $rules['best_n'] * $rules['bands'][0]['points'], 422, 'The minimum score exceeds this source scale.');

        return $rules;
    }

    private function guide(array $input, bool $archived): array
    {
        $rules = Validator::make(['rules' => $input], [
            'rules' => ['required', 'array:compulsory_subject_ids,elective_groups'],
            'rules.compulsory_subject_ids' => ['present', 'array', 'list', 'max:20'], 'rules.compulsory_subject_ids.*' => ['integer', 'min:1', 'distinct'],
            'rules.elective_groups' => ['present', 'array', 'list', 'max:10'], 'rules.elective_groups.*' => ['array:title,choose,subject_ids'],
            'rules.elective_groups.*.title' => ['required', 'string', 'max:120'], 'rules.elective_groups.*.choose' => ['required', 'integer', 'between:1,20'],
            'rules.elective_groups.*.subject_ids' => ['required', 'array', 'list', 'min:1', 'max:30'], 'rules.elective_groups.*.subject_ids.*' => ['integer', 'min:1'],
        ])->validate()['rules'];
        $rules['compulsory_subject_ids'] = array_map('intval', $rules['compulsory_subject_ids']);
        $ids = $rules['compulsory_subject_ids'];
        foreach ($rules['elective_groups'] as &$group) {
            $group['choose'] = (int) $group['choose'];
            $group['subject_ids'] = array_map('intval', $group['subject_ids']);
            abort_unless(count(array_unique($group['subject_ids'])) === count($group['subject_ids']) && count($group['subject_ids']) >= $group['choose'], 422);
            $ids = array_merge($ids, $group['subject_ids']);
        }
        unset($group);
        abort_unless($ids !== [], 422, 'Publish a guide with at least one subject choice.');
        // Disjoint groups make an explicit selection count unambiguous.
        $this->subjects($ids, $archived);

        return $rules;
    }

    public function current(string $id, bool $manager = false): AdmissionRequirement
    {
        $row = AdmissionRequirement::findOrFail($id);
        if (! $manager) {
            abort_unless($row->status === 'published' && $row->expires_on->toDateString() >= app(AcademicAuthority::class)->today(), 409, 'This pathway source changed or needs review.');
        }
        $version = AdmissionRequirementVersion::where('requirement_id', $row->id)->findOrFail($row->current_version_id);
        $ids = $row->kind === 'programme' ? array_column($version->rules['subjects'], 'subject_id') : array_merge($version->rules['compulsory_subject_ids'], ...array_column($version->rules['elective_groups'], 'subject_ids'));
        abort_unless($manager || Subject::whereIn('id', $ids)->count() === count($ids), 409, 'The referenced school subjects changed. Review this source.');

        return $row;
    }

    public function data(AdmissionRequirement $row): array
    {
        $version = AdmissionRequirementVersion::where('requirement_id', $row->id)->findOrFail($row->current_version_id);

        return ['id' => $row->id, 'revision' => (int) $row->revision, 'kind' => $row->kind, 'status' => $row->status,
            'title' => $version->title, 'institution' => $version->institution, 'source_title' => $version->source_title, 'source_url' => $version->source_url,
            'source_year' => (int) $version->source_year, 'reviewed_on' => $version->reviewed_on->toDateString(), 'expires_on' => $version->expires_on->toDateString(),
            'guidance' => $version->guidance, 'rules' => $version->rules];
    }
}
