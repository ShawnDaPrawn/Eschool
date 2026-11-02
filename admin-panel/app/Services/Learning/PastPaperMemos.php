<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\PastPaper;
use App\Models\Learning\PastPaperMemo;
use App\Models\Learning\PastPaperVersion;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Academics\AcademicNumbers;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Structured memos (Action Plan 3.15): each question of an exam coach paper
 * version gets mark points (what earns the mark, its type, an optional rule
 * and the marks it is worth) that add up to the question's total. Teachers
 * who may change the paper capture them; a teacher then marks the memo as
 * reviewed. Only a reviewed memo is usable downstream (reviewed()); saving
 * again returns it to draft. Reads go through PastPaperLibrary, so the staff
 * group, the licence attestation and the topic references are checked first.
 * engineering/specs/ai/structured-memos.md.
 */
class PastPaperMemos
{
    public const TYPES = ['method', 'answer', 'accuracy', 'consistent_accuracy', 'explanation'];

    public const RULES = ['none', 'exact_value', 'units_required'];

    public const MAX_POINTS = 20;

    private const DECIMAL = '/^-?\d{1,12}(?:\.\d{1,8})?$/D';

    private const TOLERANCE = '/^\d{1,12}(?:\.\d{1,8})?$/D';

    /** Shared by ExamMemoRequest and save(). */
    public static function rules(): array
    {
        return [
            'expected_revision' => ['required', 'integer', 'min:0'],
            'questions' => ['required', 'array', 'min:1', 'max:20'],
            'questions.*' => ['required', 'array:number,points'],
            'questions.*.number' => ['required', 'string', 'regex:/^[A-Za-z0-9().-]{1,16}$/'],
            'questions.*.points' => ['present', 'array', 'max:'.self::MAX_POINTS],
            'questions.*.points.*' => ['required', 'array:earns,type,marks,rule_kind,rule_value,rule_tolerance,rule_unit'],
            'questions.*.points.*.earns' => ['required', 'string', 'min:2', 'max:500'],
            'questions.*.points.*.type' => ['required', Rule::in(self::TYPES)],
            'questions.*.points.*.marks' => ['required', 'string', 'regex:/^\d{1,5}(?:\.\d{1,2})?$/D'],
            'questions.*.points.*.rule_kind' => ['required', Rule::in(self::RULES)],
            'questions.*.points.*.rule_value' => ['nullable', 'string', 'regex:'.self::DECIMAL],
            'questions.*.points.*.rule_tolerance' => ['nullable', 'string', 'regex:'.self::TOLERANCE],
            'questions.*.points.*.rule_unit' => ['nullable', 'string', 'max:32'],
        ];
    }

    public static function reviewRules(): array
    {
        return ['expected_revision' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']];
    }

    /** The version, for a teacher who may change its paper (its author or a grade lead), while the paper is active and its rights current. */
    public function editable(User $actor, int $groupId, string $versionId): PastPaperVersion
    {
        $library = app(PastPaperLibrary::class);
        $version = $library->version($actor, $groupId, $versionId, true);
        [, $paper] = $library->editor($actor, $groupId, (int) $version->past_paper_id);
        abort_unless($paper->active && in_array($version->state, ['draft', 'published'], true), 409, __('This paper was withdrawn. Its memo can no longer change.'));

        return $version;
    }

    /**
     * Validate the input against the version's questions and return the mark points to store:
     * every question in the paper's order, each point with its marks in hundredths.
     */
    public function normalize(PastPaperVersion $version, array $input): array
    {
        $data = Validator::make($input, self::rules())->validate();
        $questions = $version->definition['questions'];
        $data['questions'] = array_values($data['questions']);
        if (array_column($data['questions'], 'number') !== array_map(fn ($q) => (string) $q['number'], $questions)) {
            throw ValidationException::withMessages(['questions' => __('Reload the paper: its questions changed.')]);
        }
        $result = [];
        $errors = [];
        foreach ($questions as $i => $question) {
            $points = [];
            $sum = 0;
            foreach ($data['questions'][$i]['points'] as $j => $point) {
                $field = 'questions.'.$i.'.points.'.$j;
                $units = AcademicNumbers::units($point['marks'], $field.'.marks', 100000);
                if ($units < 1) {
                    $errors[$field.'.marks'] = __('Question :number: each mark point is worth more than zero.', ['number' => $question['number']]);
                }
                [$rule, $problem] = $this->rule($point);
                if ($problem !== null) {
                    $errors[$field.'.rule_kind'] = __('Question :number: :problem', ['number' => $question['number'], 'problem' => $problem]);
                }
                $sum += $units;
                $points[] = ['earns' => trim($point['earns']), 'type' => $point['type'], 'mark_units' => $units, 'rule' => $rule];
            }
            if ($points !== [] && $sum !== (int) $question['max_units']) {
                $errors['questions.'.$i.'.points'] = $this->sumMessage((string) $question['number'], $sum, (int) $question['max_units']);
            }
            $result[] = ['number' => (string) $question['number'], 'points' => $points];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $result;
    }

    /** @return array{0: ?array, 1: ?string} the stored rule and any problem with it */
    private function rule(array $point): array
    {
        $value = trim((string) ($point['rule_value'] ?? ''));
        $tolerance = trim((string) ($point['rule_tolerance'] ?? ''));
        $unit = trim((string) ($point['rule_unit'] ?? ''));

        return match ($point['rule_kind']) {
            'exact_value' => $value === '' ? [null, __('an exact value rule needs the value.')]
                : [['kind' => 'exact_value', 'value' => $value, 'tolerance' => $tolerance === '' ? '0' : $tolerance, 'unit' => $unit === '' ? null : $unit], null],
            'units_required' => $unit === '' || $value !== '' || $tolerance !== '' ? [null, __('a units rule needs the unit only.')]
                : [['kind' => 'units_required', 'unit' => $unit], null],
            default => $value !== '' || $tolerance !== '' || $unit !== '' ? [null, __('choose a rule for the value or unit, or clear them.')] : [null, null],
        };
    }

    private function sumMessage(string $number, int $sum, int $total): string
    {
        return __('Question :number: the mark points add up to :sum, but the question is worth :total.', [
            'number' => $number, 'sum' => AcademicNumbers::decimal($sum), 'total' => AcademicNumbers::decimal($total)]);
    }

    /** Problems that stop a memo being reviewed: a question without mark points, or points that do not add up. */
    public function problems(PastPaperVersion $version, array $stored): array
    {
        $byNumber = array_column($stored, 'points', 'number');
        $problems = [];
        foreach ($version->definition['questions'] as $question) {
            $points = $byNumber[(string) $question['number']] ?? [];
            if ($points === []) {
                $problems[] = __('Question :number has no mark points yet.', ['number' => $question['number']]);

                continue;
            }
            $sum = array_sum(array_column($points, 'mark_units'));
            if ($sum !== (int) $question['max_units']) {
                $problems[] = $this->sumMessage((string) $question['number'], $sum, (int) $question['max_units']);
            }
        }

        return $problems;
    }

    public function save(User $actor, int $groupId, string $versionId, array $input): array
    {
        $version = $this->editable($actor, $groupId, $versionId);
        abort_unless(app(ModuleGate::class)->writable('exam_coach'), 403);
        $points = $this->normalize($version, $input);
        $expected = (int) $input['expected_revision'];
        $school = app(CurrentSchool::class)->id();

        return DB::transaction(function () use ($actor, $groupId, $versionId, $points, $expected, $school) {
            School::lockForUpdate()->findOrFail($school);
            $this->editable($actor, $groupId, $versionId);
            $memo = PastPaperMemo::where('past_paper_version_id', $versionId)->lockForUpdate()->first();
            abort_unless(($memo === null ? 0 : (int) $memo->revision) === $expected, 409, __('Someone changed this memo. Reload it and try again.'));
            $before = $memo === null ? [] : ['state' => $memo->state, 'revision' => (int) $memo->revision];
            if ($memo === null) {
                $memo = PastPaperMemo::create(['past_paper_version_id' => $versionId, 'state' => 'draft', 'revision' => 1,
                    'mark_points' => $points, 'updated_by' => $actor->id]);
            } else {
                $memo->update(['state' => 'draft', 'revision' => $memo->revision + 1, 'mark_points' => $points,
                    'updated_by' => $actor->id, 'reviewed_by' => null, 'reviewed_at' => null]);
            }
            Audit::record('exam_coach.memo_saved', $memo, $before, ['state' => 'draft', 'revision' => (int) $memo->revision,
                'version_id' => $versionId, 'mark_points' => array_sum(array_map(fn ($q) => count($q['points']), $points))]);

            return ['id' => (int) $memo->id, 'version_id' => $versionId, 'state' => $memo->state, 'revision' => (int) $memo->revision];
        });
    }

    public function review(User $actor, int $groupId, string $versionId, array $input): array
    {
        $data = Validator::make($input, self::reviewRules())->validate();
        $this->editable($actor, $groupId, $versionId);
        abort_unless(app(ModuleGate::class)->writable('exam_coach'), 403);
        $school = app(CurrentSchool::class)->id();

        return DB::transaction(function () use ($actor, $groupId, $versionId, $data, $school) {
            School::lockForUpdate()->findOrFail($school);
            $version = $this->editable($actor, $groupId, $versionId);
            $memo = PastPaperMemo::where('past_paper_version_id', $versionId)->lockForUpdate()->first();
            abort_unless($memo !== null && (int) $memo->revision === (int) $data['expected_revision'] && $memo->state === 'draft', 409,
                __('Someone changed this memo. Reload it and try again.'));
            $problems = $this->problems($version, $memo->mark_points);
            if ($problems !== []) {
                throw ValidationException::withMessages(['memo' => $problems]);
            }
            $memo->update(['state' => 'reviewed', 'revision' => $memo->revision + 1, 'reviewed_by' => $actor->id, 'reviewed_at' => now('UTC')]);
            Audit::record('exam_coach.memo_reviewed', $memo, ['state' => 'draft', 'revision' => (int) $data['expected_revision']],
                ['state' => 'reviewed', 'revision' => (int) $memo->revision, 'version_id' => $versionId]);

            return ['id' => (int) $memo->id, 'version_id' => $versionId, 'state' => $memo->state, 'revision' => (int) $memo->revision];
        });
    }

    /**
     * The mark points downstream features (marking, Action Plan 3.16) may use: only from a
     * reviewed memo whose points still add up to each question's total; otherwise null.
     * The reader's access to the version is checked as for the paper itself.
     */
    public function reviewed(User $actor, int $groupId, string $versionId, bool $staff = false): ?array
    {
        $version = app(PastPaperLibrary::class)->version($actor, $groupId, $versionId, $staff);
        $memo = PastPaperMemo::where('past_paper_version_id', $version->id)->first();
        if ($memo === null || $memo->state !== 'reviewed' || $this->problems($version, $memo->mark_points) !== []) {
            return null;
        }
        $maxima = array_column($version->definition['questions'], 'max_units', 'number');
        $questions = [];
        foreach ($memo->mark_points as $question) {
            $questions[] = ['number' => $question['number'], 'max_units' => (int) $maxima[$question['number']], 'points' => $question['points']];
        }

        return ['version_id' => $version->id, 'revision' => (int) $memo->revision, 'questions' => $questions];
    }

    /** The memo page: the staff paper with each question's mark points and the memo's state. */
    public function view(User $actor, int $groupId, string $versionId): array
    {
        $library = app(PastPaperLibrary::class);
        $paper = $library->data($actor, $groupId, $versionId, true);
        $version = $library->version($actor, $groupId, $versionId, true);
        $memo = PastPaperMemo::where('past_paper_version_id', $versionId)->first();
        $stored = $memo === null ? [] : array_column($memo->mark_points, 'points', 'number');
        $questions = [];
        foreach ($paper['questions'] as $question) {
            $points = $stored[(string) $question['number']] ?? [];
            $sum = array_sum(array_column($points, 'mark_units'));
            $questions[] = $question + ['points' => $points, 'sum_units' => $sum, 'complete' => $points !== [] && $sum === (int) $question['max_units']];
        }
        $reviewer = $memo?->reviewed_by === null ? null : User::find($memo->reviewed_by);
        $active = (bool) PastPaper::whereKey($version->past_paper_id)->value('active');

        return ['paper' => array_diff_key($paper, ['questions' => true]), 'questions' => $questions, 'group_id' => $groupId,
            'memo' => ['state' => $memo?->state ?? 'none', 'revision' => $memo === null ? 0 : (int) $memo->revision,
                'reviewed_by' => $reviewer === null ? null : trim($reviewer->first_name.' '.$reviewer->last_name),
                'reviewed_at' => $memo?->reviewed_at?->toDateTimeString(),
                'problems' => $memo === null ? [] : $this->problems($version, $memo->mark_points)],
            'can_edit' => ($paper['can_edit'] ?? false) && $active && in_array($version->state, ['draft', 'published'], true) && app(ModuleGate::class)->writable('exam_coach')];
    }

    /** The teacher's exam coach groups and, for the chosen group, its papers with each memo's state. */
    public function listing(User $actor, ?int $groupId, int $page = 1): array
    {
        $queries = app(ExamCoachQueries::class);
        $groups = $queries->configuration($actor)['staff_groups'];
        $groupId ??= $groups[0]['id'] ?? null;
        if ($groupId === null) {
            return ['groups' => [], 'group_id' => null, 'papers' => [], 'page' => 1, 'has_more' => false];
        }
        abort_unless(in_array($groupId, array_column($groups, 'id'), true), 404);
        $listing = $queries->papers($actor, $groupId, true, $page);
        $states = PastPaperMemo::whereIn('past_paper_version_id', array_column($listing['papers'], 'version_id'))->pluck('state', 'past_paper_version_id')->all();
        $papers = array_map(fn ($paper) => $paper + ['memo_state' => $states[$paper['version_id']] ?? 'none'], $listing['papers']);

        return ['groups' => $groups, 'group_id' => $groupId, 'papers' => $papers, 'page' => $page, 'has_more' => $listing['has_more']];
    }
}
