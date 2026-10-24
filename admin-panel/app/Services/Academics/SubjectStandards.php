<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\SubjectStandard;
use App\Models\Mediums;
use App\Models\Platform\School;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Academics\SubjectStandardPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubjectStandards
{
    public function catalogue(User $actor, array $filters): LengthAwarePaginator
    {
        app(SubjectStandardPolicy::class)->authorise($actor);

        return SubjectStandard::query()->whereNotNull('published_at')
            ->when(isset($filters['framework']), fn ($q) => $q->where('framework', $filters['framework']))
            ->when(isset($filters['grade']), fn ($q) => $q->where('grade_from', '<=', $filters['grade'])->where('grade_to', '>=', $filters['grade']))
            ->when(! empty($filters['q']), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$filters['q'].'%')->orWhere('code', 'like', '%'.$filters['q'].'%')))
            ->orderBy('framework')->orderBy('name')->orderBy('grade_from')->orderByDesc('effective_on')->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 30));
    }

    public function mapping(User $actor, int $id): Subject
    {
        app(SubjectStandardPolicy::class)->authorise($actor, 'list');

        return Subject::query()->with('standard')->findOrFail($id);
    }

    public function map(User $actor, int $id, ?int $standardId): Subject
    {
        app(SubjectStandardPolicy::class)->authorise($actor, 'edit');

        return DB::transaction(function () use ($actor, $id, $standardId) {
            $subject = Subject::query()->lockForUpdate()->findOrFail($id);
            $standard = $standardId === null ? null : SubjectStandard::query()->whereNotNull('published_at')->findOrFail($standardId);
            app(SubjectStandardPolicy::class)->authorise($actor, 'edit');
            if ((string) $subject->standard_id !== (string) $standard?->id) {
                $before = $subject->standard_id;
                $subject->standard_id = $standard?->id;
                $subject->save();
                Audit::record('academics.subject_standard_mapped', $subject, ['standard_id' => $before], ['standard_id' => $subject->standard_id]);
            }

            return $subject->load('standard');
        });
    }

    public function createSubject(User $actor, int $standardId, int $mediumId, string $type, string $bgColor): Subject
    {
        app(SubjectStandardPolicy::class)->authorise($actor, 'create');

        return DB::transaction(function () use ($actor, $standardId, $mediumId, $type, $bgColor) {
            // Serialize duplicate creation within one school without trusting a payload school.
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $standard = SubjectStandard::query()->whereNotNull('published_at')->findOrFail($standardId);
            Mediums::query()->findOrFail($mediumId);
            app(SubjectStandardPolicy::class)->authorise($actor, 'create');
            $existing = Subject::query()->where('standard_id', $standardId)->where('medium_id', $mediumId)->where('type', $type)->first();
            if ($existing) {
                return $existing->load('standard');
            }
            if (Subject::query()->where('medium_id', $mediumId)->where('type', $type)
                ->where(fn ($q) => $q->where('name', $standard->name)->orWhere('code', $standard->code))->exists()) {
                throw ValidationException::withMessages(['standard_id' => __('Map the existing school subject instead of creating a duplicate.')]);
            }
            $subject = new Subject;
            $subject->name = $standard->name;
            $subject->code = $standard->code;
            $subject->type = $type;
            $subject->medium_id = $mediumId;
            $subject->standard_id = $standardId;
            $subject->bg_color = $bgColor;
            $subject->image = '';
            $subject->save();
            Audit::record('academics.subject_created_from_standard', $subject, [], ['standard_id' => $standardId, 'medium_id' => $mediumId, 'type' => $type]);

            return $subject->load('standard');
        });
    }

    /** Trusted platform operator path. Never exposed as a school HTTP write. */
    public function import(array $input, bool $publish = false): array
    {
        $validated = Validator::make(['standards' => $input], ['standards' => ['required', 'array', 'min:1', 'max:5000'],
            'standards.*.framework' => ['required', 'in:CAPS,IEB'], 'standards.*.code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9][A-Za-z0-9._ -]*$/'],
            'standards.*.name' => ['required', 'string', 'max:191'], 'standards.*.phase' => ['required', 'in:Foundation,Intermediate,Senior,FET'],
            'standards.*.grade_from' => ['required', 'integer', 'between:0,12'], 'standards.*.grade_to' => ['required', 'integer', 'between:0,12'],
            'standards.*.version' => ['required', 'string', 'max:40'], 'standards.*.source_url' => ['required', 'url:https', 'max:2048'],
            'standards.*.effective_on' => ['required', 'date_format:Y-m-d'], 'standards.*.component_weights' => ['sometimes', 'nullable', 'array', 'min:1', 'max:8'],
            'standards.*.component_weights.*' => ['integer', 'between:0,10000']])->validate()['standards'];
        $rows = [];
        foreach ($validated as $index => $row) {
            $row = array_intersect_key($row, array_flip(['framework', 'code', 'name', 'phase', 'grade_from', 'grade_to', 'version', 'source_url', 'effective_on', 'component_weights']));
            $row['grade_from'] = (int) $row['grade_from'];
            $row['grade_to'] = (int) $row['grade_to'];
            $phase = fn (int $g) => match (true) {
                $g <= 3 => 'Foundation', $g <= 6 => 'Intermediate', $g <= 9 => 'Senior', default => 'FET'
            };
            if ($row['grade_from'] > $row['grade_to'] || $phase($row['grade_from']) !== $row['phase'] || $phase($row['grade_to']) !== $row['phase']) {
                throw ValidationException::withMessages(["standards.$index.grade_from" => 'A standard must cover an ordered grade range within its phase.']);
            }
            $weights = $row['component_weights'] ?? null;
            if ($weights !== null) {
                foreach ($weights as $key => $weight) {
                    if (! preg_match('/^[a-z][a-z0-9_]{0,23}$/', (string) $key)) {
                        throw ValidationException::withMessages(["standards.$index.component_weights" => 'Use named assessment components.']);
                    }
                    $weights[$key] = (int) $weight;
                }
                ksort($weights);
                if (array_sum($weights) !== 10000) {
                    throw ValidationException::withMessages(["standards.$index.component_weights" => 'Assessment component weights must total 10000 basis points.']);
                }
            }
            $row['component_weights'] = $weights;
            ksort($row);
            $row['fingerprint'] = hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
            $rows[] = $row;
        }

        return DB::transaction(function () use ($rows, $publish) {
            $created = $published = 0;
            foreach ($rows as $row) {
                $key = array_intersect_key($row, array_flip(['framework', 'code', 'grade_from', 'grade_to', 'version']));
                $record = SubjectStandard::query()->where($key)->lockForUpdate()->first();
                if ($record !== null && $record->fingerprint !== $row['fingerprint']) {
                    throw ValidationException::withMessages(['standards' => 'An existing standard revision differs. Import a new version instead of rewriting it.']);
                }
                if ($record === null) {
                    $record = SubjectStandard::query()->create($row);
                    $created++;
                    Audit::record('academics.subject_standard_imported', $record, [], $key);
                }
                if ($publish && $record->published_at === null) {
                    $record->published_at = now();
                    $record->save();
                    $published++;
                    Audit::record('academics.subject_standard_published', $record, [], $key);
                }
            }

            return ['created' => $created, 'published' => $published, 'processed' => count($rows)];
        });
    }
}
