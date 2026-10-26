<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Learning\PastPaper;
use App\Models\Learning\PastPaperVersion;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\AcademicAuthority;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** School-reviewed, licensed question/memo versions; no automatic rights claims. */
class PastPaperLibrary
{
    private function editor(User $actor, int $groupId, ?int $paperId, bool $write = true): array
    {
        $group = app(ExamCoachAccess::class)->staff($actor, $groupId, $write);
        $paper = $paperId === null ? null : PastPaper::where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)->findOrFail($paperId);
        abort_if($paper !== null && (int) $paper->created_by !== (int) $actor->id && ! app(ContentPolicy::class)->leads($actor, (int) $group->grade_level_id), 403);

        return [$group, $paper];
    }

    private function input(TeachingGroup $group, array $input): array
    {
        $data = Validator::make($input, [
            'operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:191'], 'framework' => ['required', Rule::in(app(ExamCoachAccess::class)->frameworks($group))],
            'paper_year' => ['required', 'integer', 'between:1850,2100'], 'paper_code' => ['required', 'string', 'max:64'],
            'group_ids' => ['required', 'array', 'min:1', 'max:20'], 'group_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'licence' => ['required', 'array:kind,reference,statement,confirmed,expires_on'],
            'licence.kind' => ['required', Rule::in(['school_owned', 'open_licence', 'permission'])],
            'licence.reference' => ['required', 'string', 'min:3', 'max:1000'],
            'licence.statement' => ['required', 'string', 'min:20', 'max:2000'], 'licence.confirmed' => ['required', 'boolean', 'accepted'],
            'licence.expires_on' => ['nullable', 'date_format:Y-m-d'],
            'questions' => ['required', 'array', 'min:1', 'max:20'],
            'questions.*' => ['required', 'array:number,prompt,memo,max_units,topic_ids'],
            'questions.*.number' => ['required', 'string', 'regex:/^[A-Za-z0-9().-]{1,16}$/', 'distinct'],
            'questions.*.prompt' => ['required', 'string', 'max:4000'], 'questions.*.memo' => ['required', 'string', 'max:8000'],
            'questions.*.max_units' => ['required', 'integer', 'between:1,100000'],
            'questions.*.topic_ids' => ['required', 'array', 'min:1', 'max:10'], 'questions.*.topic_ids.*' => ['required', 'integer', 'min:1'],
        ])->validate();
        $data['licence']['confirmed'] = true;
        $data['group_ids'] = array_map(intval(...), $data['group_ids']);
        sort($data['group_ids']);
        foreach ($data['questions'] as &$question) {
            $question['max_units'] = (int) $question['max_units'];
            $question['topic_ids'] = array_map(intval(...), $question['topic_ids']);
            abort_unless(count($question['topic_ids']) === count(array_unique($question['topic_ids'])), 422);
            sort($question['topic_ids']);
        }
        unset($question);
        abort_unless(in_array((int) $group->id, $data['group_ids'], true), 422);
        abort_if(strlen(json_encode($data, JSON_THROW_ON_ERROR)) > 250000, 422, 'Use a smaller paper version.');

        return $data;
    }

    private function references(User $actor, TeachingGroup $base, array $data): void
    {
        foreach ($data['group_ids'] as $id) {
            $group = app(ExamCoachAccess::class)->staff($actor, $id, true);
            abort_unless((int) $group->subject_id === (int) $base->subject_id && (int) $group->grade_level_id === (int) $base->grade_level_id, 422);
            $topics = app(ExamCoachAccess::class)->topics($group);
            foreach ($data['questions'] as $question) {
                abort_unless(array_diff($question['topic_ids'], array_keys($topics)) === [], 422, 'Choose current published topics for this paper.');
            }
        }
    }

    public function draft(User $actor, int $groupId, ?int $paperId, array $input): array
    {
        [$group] = $this->editor($actor, $groupId, $paperId);
        $data = $this->input($group, $input);
        $authorize = function () use ($actor, $groupId, $paperId, $data) {
            [$group, $paper] = $this->editor($actor, $groupId, $paperId);
            abort_if($paper !== null && (! $paper->active || $paper->framework !== $data['framework']), 409);
            $this->references($actor, $group, $data);
        };

        return app(ExamCoachOperations::class)->run($actor, 'paper.draft', ['group_id' => $groupId, 'paper_id' => $paperId] + $data, $authorize,
            function () use ($actor, $groupId, $paperId, $data) {
                [$group, $paper] = $this->editor($actor, $groupId, $paperId);
                if ($paper === null) {
                    abort_unless((int) $data['expected_revision'] === 0, 409);
                    $paper = PastPaper::create(['subject_id' => $group->subject_id, 'grade_level_id' => $group->grade_level_id,
                        'framework' => $data['framework'], 'created_by' => $actor->id]);
                } else {
                    abort_unless((int) $paper->revision === (int) $data['expected_revision'], 409, 'Reload the current paper revision.');
                    $paper->increment('revision');
                }
                $number = ((int) PastPaperVersion::where('past_paper_id', $paper->id)->max('number')) + 1;
                abort_if($number > 100, 422, 'Create a fresh library paper.');
                $definition = array_intersect_key($data, array_flip(['title', 'licence', 'questions']));
                $version = PastPaperVersion::create(['id' => (string) Str::uuid(), 'past_paper_id' => $paper->id, 'number' => $number,
                    'paper_year' => $data['paper_year'], 'paper_code' => $data['paper_code'], 'group_ids' => $data['group_ids'], 'created_by' => $actor->id,
                    'definition' => $definition, 'licence_expires_on' => $data['licence']['expires_on'] ?? null,
                    'fingerprint' => hash_hmac('sha256', json_encode([$paper->id, $data], JSON_THROW_ON_ERROR), config('app.key'))]);

                return ['id' => (int) $paper->id, 'version_id' => $version->id, 'revision' => (int) $paper->revision, 'state' => 'draft'];
            });
    }

    public function publish(User $actor, int $groupId, int $paperId, string $versionId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1']])->validate();
        $authorize = function () use ($actor, $groupId, $paperId, $versionId) {
            [$group, $paper] = $this->editor($actor, $groupId, $paperId);
            abort_unless($paper->active, 409);
            $version = PastPaperVersion::where('past_paper_id', $paperId)->findOrFail($versionId);
            $this->licence($version);
            $this->references($actor, $group, ['group_ids' => $version->group_ids, 'questions' => $version->definition['questions']]);
        };

        return app(ExamCoachOperations::class)->run($actor, 'paper.publish', ['group_id' => $groupId, 'paper_id' => $paperId, 'version_id' => $versionId] + $data, $authorize,
            function () use ($actor, $paperId, $versionId, $data) {
                $paper = PastPaper::findOrFail($paperId);
                $version = PastPaperVersion::where('past_paper_id', $paperId)->findOrFail($versionId);
                abort_unless((int) $paper->revision === (int) $data['expected_revision'] && $version->state === 'draft', 409);
                $version->update(['state' => 'published', 'published_by' => $actor->id, 'published_at' => now('UTC')]);
                $paper->update(['published_version_id' => $version->id, 'revision' => $paper->revision + 1]);

                return ['id' => (int) $paper->id, 'version_id' => $version->id, 'revision' => (int) $paper->revision, 'state' => 'published'];
            });
    }

    private function licence(PastPaperVersion $version): void
    {
        $licence = $version->definition['licence'];
        abort_unless(($licence['confirmed'] ?? false) === true || ($licence['confirmed'] ?? null) === 1, 409);
        abort_if($version->licence_expires_on !== null && $version->licence_expires_on->toDateString() < app(AcademicAuthority::class)->today(), 409, 'This paper licence expired.');
    }

    public function withdraw(User $actor, int $groupId, int $paperId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'min:3', 'max:1000']])->validate();
        $authorize = fn () => $this->editor($actor, $groupId, $paperId);

        return app(ExamCoachOperations::class)->run($actor, 'paper.withdraw', ['group_id' => $groupId, 'paper_id' => $paperId] + $data, $authorize,
            function () use ($actor, $paperId, $data) {
                $paper = PastPaper::findOrFail($paperId);
                abort_unless($paper->active && (int) $paper->revision === (int) $data['expected_revision'], 409);
                if ($paper->published_version_id !== null) {
                    PastPaperVersion::findOrFail($paper->published_version_id)->update(['state' => 'withdrawn']);
                }
                $paper->update(['active' => false, 'revision' => $paper->revision + 1, 'withdrawal_reason' => $data['reason'],
                    'withdrawn_by' => $actor->id, 'withdrawn_at' => now('UTC')]);

                return ['id' => (int) $paper->id, 'version_id' => $paper->published_version_id, 'revision' => (int) $paper->revision, 'state' => 'withdrawn'];
            });
    }

    /** Authorize the audience before loading the encrypted memo/rights body. */
    public function version(User $actor, int $groupId, string $versionId, bool $staff = false): PastPaperVersion
    {
        $access = app(ExamCoachAccess::class);
        $group = $staff ? $access->staff($actor, $groupId) : $access->learnerGroup($actor, $groupId);
        $meta = PastPaperVersion::select('id', 'school_id', 'past_paper_id', 'state', 'group_ids')->findOrFail($versionId);
        $paper = PastPaper::where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)->findOrFail($meta->past_paper_id);
        abort_unless(in_array($groupId, $meta->group_ids, true) && in_array($paper->framework, $access->frameworks($group), true), 404);
        if (! $staff) {
            abort_unless($paper->active && $paper->published_version_id === $versionId && $meta->state === 'published', 404);
        } elseif ($meta->state !== 'published') {
            $this->editor($actor, $groupId, (int) $paper->id, false);
        }
        $version = PastPaperVersion::findOrFail($versionId);
        $this->licence($version);
        $topics = $access->topics($group);
        foreach ($version->definition['questions'] as $question) {
            abort_unless(array_diff($question['topic_ids'], array_keys($topics)) === [], 409, 'Published topic references changed.');
        }

        return $version;
    }

    public function data(User $actor, int $groupId, string $versionId, bool $staff = false): array
    {
        $version = $this->version($actor, $groupId, $versionId, $staff);
        $definition = $version->definition;
        $paper = PastPaper::findOrFail($version->past_paper_id);
        $questions = array_map(fn ($question) => $staff ? $question : array_diff_key($question, ['memo' => true]), $definition['questions']);

        return ['id' => (int) $version->past_paper_id, 'version_id' => $version->id, 'number' => (int) $version->number,
            'title' => $definition['title'], 'framework' => $paper->framework, 'paper_year' => (int) $version->paper_year, 'paper_code' => $version->paper_code,
            'state' => $version->state, 'licence_kind' => $definition['licence']['kind'], 'licence_reference' => $definition['licence']['reference'],
            'questions' => $questions] + ($staff ? ['licence' => $definition['licence'], 'revision' => (int) $paper->revision, 'group_ids' => $version->group_ids,
                'can_edit' => $paper->active && ((int) $paper->created_by === (int) $actor->id || app(ContentPolicy::class)->leads($actor, (int) $paper->grade_level_id))] : []);
    }

    public function document(User $actor, int $groupId, string $versionId, bool $memo = false, bool $staff = false): string
    {
        $data = $this->data($actor, $groupId, $versionId, $memo || $staff);
        $pdf = Pdf::setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'defaultFont' => 'DejaVu Sans'])
            ->loadView('learning.exam-paper', ['paper' => $data, 'memo' => $memo])->output();
        $this->version($actor, $groupId, $versionId, $memo || $staff);

        return $pdf;
    }
}
