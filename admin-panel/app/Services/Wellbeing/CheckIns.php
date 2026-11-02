<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\Growth\RiskCaseload;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Models\Wellbeing\CheckIn;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\RiskAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Short private wellbeing check-ins (Action Plan 4.11): one age-appropriate
 * question, a five-point answer and an optional note.
 *
 * Authorised pastoral staff for a learner are exactly the restricted
 * professionals (WellbeingAccess::professional: a fresh Counsellor or
 * Safeguarding role with pastoral-sensitive-access, the wellbeing module, no
 * platform identity or impersonation) who also hold a current dated risk
 * caseload grant for that learner in the current year. Nobody else, teacher,
 * grade head, principal or administrator, schedules, reads or records one.
 * Answers never feed a risk score, are never shown to other learners and are
 * never used to rank anyone. engineering/specs/growth/check-ins-and-intervention-library.md.
 */
class CheckIns
{
    public const PROMPTS = [
        'week' => 'How has your week been?',
        'today' => 'How are you feeling today?',
        'school' => 'How is school going for you at the moment?',
        'friends' => 'How are things with your friends?',
    ];

    /** Plain words for every age; 1 is the lowest. */
    public const SCALE = [1 => 'Not good', 2 => 'A bit low', 3 => 'Okay', 4 => 'Good', 5 => 'Great'];

    public const PER_GROUP = 40;

    public const META = ['id', 'learner_id', 'session_year_id', 'scheduled_by', 'group_id', 'prompt', 'due_on', 'status', 'answer_source', 'answered_by', 'answered_at', 'revision'];

    /** The restricted professional, in a school with wellbeing and early warning. */
    public function professional(User $actor, bool $write = false): User
    {
        $fresh = app(WellbeingAccess::class)->professional($actor, $write);
        // The caseload grant that names a learner's pastoral professional lives in early warning.
        app(RiskAuthority::class)->enabled($write);

        return $fresh;
    }

    public function available(User $actor): bool
    {
        try {
            $this->professional($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** Learners whose current-year risk caseload this professional holds today. */
    public function learnerIds(User $actor): array
    {
        $fresh = $this->professional($actor);
        $today = app(AcademicAuthority::class)->today();

        return RiskCaseload::where('professional_id', $fresh->id)->where('session_year_id', (int) schoolSetting('session_year'))->whereNull('revoked_at')
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->orderBy('learner_id')->limit(500)
            ->pluck('learner_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function forLearner(User $actor, int $learner, bool $write = false): User
    {
        $fresh = $this->professional($actor, $write);
        Students::findOrFail($learner);
        abort_unless(in_array($learner, $this->learnerIds($fresh), true), 404);

        return $fresh;
    }

    /** Metadata first; the answer is selected only after the reader is authorised for this learner. */
    public function authorized(User $actor, int $id, bool $write = false): CheckIn
    {
        $this->professional($actor, $write);
        $row = CheckIn::select(self::META)->findOrFail($id);
        $this->forLearner($actor, (int) $row->learner_id, $write);
        abort_unless((int) $row->session_year_id === (int) schoolSetting('session_year'), 404);

        return $row;
    }

    public function list(User $actor, int $page = 1): array
    {
        $fresh = $this->professional($actor);
        $learners = $this->learnerIds($fresh);
        $rows = CheckIn::select(self::META)->whereIn('learner_id', $learners)->where('session_year_id', (int) schoolSetting('session_year'))
            ->orderByRaw("CASE WHEN status = 'scheduled' THEN 0 ELSE 1 END")->orderByDesc('due_on')->orderByDesc('id')
            ->paginate(50, ['*'], 'page', max(1, $page));
        $answers = CheckIn::whereIn('id', $rows->pluck('id'))->whereIn('learner_id', $learners)->get(['id', 'answer'])->keyBy('id');
        $names = $this->names(array_merge($learners, $rows->pluck('learner_id')->all()));
        // Recheck after reading: a grant withdrawn meanwhile returns nothing.
        $still = $this->learnerIds($fresh);

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(),
            'learners' => collect($still)->map(fn ($id) => ['learner_id' => $id, 'name' => $names[$id] ?? __('Archived learner')])->sortBy('name')->values()->all(),
            'check_ins' => $rows->filter(fn ($row) => in_array((int) $row->learner_id, $still, true))
                ->map(fn ($row) => $this->present($row, $answers->get($row->id)?->answer, $names))->values()->all()];
    }

    /** One learner, or a group chosen from the professional's own caseload, all or nothing. */
    public function schedule(User $actor, array $input): array
    {
        $this->professional($actor, true);
        $today = app(AcademicAuthority::class)->today();
        $data = Validator::make($input, ['learner_ids' => ['required', 'array', 'min:1', 'max:'.self::PER_GROUP], 'learner_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'prompt' => ['required', 'in:'.implode(',', array_keys(self::PROMPTS))],
            'due_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$today, 'before_or_equal:'.Carbon::parse($today)->addDays(60)->toDateString()]])->validate();
        $learners = array_values(array_unique(array_map('intval', $data['learner_ids'])));

        return DB::transaction(function () use ($actor, $data, $learners) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $fresh = $this->professional($actor, true);
            $year = (int) schoolSetting('session_year');
            foreach ($learners as $learner) {
                $this->forLearner($fresh, $learner, true);
                app(WellbeingAccess::class)->learner($learner);
            }
            if (CheckIn::whereIn('learner_id', $learners)->where('due_on', $data['due_on'])->where('prompt', $data['prompt'])->where('status', '!=', 'cancelled')->exists()) {
                throw ValidationException::withMessages(['learner_ids' => __('A chosen learner already has this check-in on that day.')]);
            }
            $group = count($learners) > 1 ? (string) Str::uuid() : null;
            $ids = [];
            foreach ($learners as $learner) {
                $row = CheckIn::create(['learner_id' => $learner, 'session_year_id' => $year, 'scheduled_by' => $fresh->id, 'group_id' => $group,
                    'prompt' => $data['prompt'], 'due_on' => $data['due_on'], 'status' => 'scheduled', 'revision' => 1]);
                Audit::record('wellbeing.check_in_scheduled', $row, [], ['revision' => 1]);
                $ids[] = (int) $row->id;
            }

            return ['ids' => $ids, 'group_id' => $group];
        });
    }

    /** A staff-recorded answer: the learner told their pastoral professional in person or on paper. */
    public function record(User $actor, int $id, array $input): array
    {
        $this->authorized($actor, $id, true);
        $data = $this->answer($input, ['expected_revision' => ['required', 'integer', 'min:1']]);

        return DB::transaction(function () use ($actor, $id, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $meta = $this->authorized($actor, $id, true);
            abort_unless($meta->revision === (int) $data['expected_revision'], 409, 'This check-in changed. Reload before saving.');

            return $this->store($meta, $actor, 'staff', $data);
        });
    }

    public function cancel(User $actor, int $id, int $revision): array
    {
        $this->authorized($actor, $id, true);

        return DB::transaction(function () use ($actor, $id, $revision) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $meta = $this->authorized($actor, $id, true);
            abort_unless($meta->revision === $revision && $meta->status === 'scheduled', 409, 'This check-in changed. Reload before saving.');
            $row = CheckIn::lockForUpdate()->findOrFail($id);
            $row->forceFill(['status' => 'cancelled', 'revision' => $row->revision + 1])->save();
            Audit::record('wellbeing.check_in_cancelled', $row, ['revision' => $revision], ['revision' => $row->revision]);

            return ['id' => (int) $row->id, 'status' => 'cancelled', 'revision' => $row->revision];
        });
    }

    /**
     * The learner's own due check-ins, prompts only. Server side of the
     * learner answer path; the app screen and API are a later step.
     */
    public function dueForLearner(User $actor): array
    {
        $learner = $this->learner($actor);

        return CheckIn::select(['id', 'prompt', 'due_on', 'revision'])->where('learner_id', $learner->id)->where('session_year_id', (int) schoolSetting('session_year'))
            ->where('status', 'scheduled')->whereDate('due_on', '<=', app(AcademicAuthority::class)->today())->orderBy('due_on')->limit(10)->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'prompt' => self::PROMPTS[$row->prompt], 'due_on' => $row->due_on->toDateString(),
                'scale' => self::SCALE, 'revision' => $row->revision])->all();
    }

    /** The learner answers their own due check-in once; they never see anyone else's. */
    public function answerAsLearner(User $actor, int $id, array $input): array
    {
        $this->learner($actor, true);
        $data = $this->answer($input);

        return DB::transaction(function () use ($actor, $id, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $learner = $this->learner($actor, true);
            $meta = CheckIn::select(self::META)->where('learner_id', $learner->id)->where('session_year_id', (int) schoolSetting('session_year'))
                ->whereDate('due_on', '<=', app(AcademicAuthority::class)->today())->findOrFail($id);

            return $this->store($meta, $actor, 'learner', $data);
        });
    }

    private function learner(User $actor, bool $write = false): Students
    {
        $fresh = app(WellbeingAccess::class)->actor($actor, $write);
        abort_unless(in_array('Student', app(ProfilePolicy::class)->roles($fresh), true), 403);
        $learner = Students::where('user_id', $fresh->id)->firstOrFail();
        abort_unless(Enrolment::where('learner_id', $learner->id)->where('session_year_id', (int) schoolSetting('session_year'))
            ->onDate(app(AcademicAuthority::class)->today())->exists(), 403);

        return $learner;
    }

    private function answer(array $input, array $extra = []): array
    {
        return Validator::make($input, ['scale' => ['required', 'integer', 'between:1,5'], 'note' => ['sometimes', 'nullable', 'string', 'max:500']] + $extra)->validate();
    }

    private function store(CheckIn $meta, User $actor, string $source, array $data): array
    {
        abort_unless($meta->status === 'scheduled', 409, 'This check-in has already been answered or cancelled.');
        $row = CheckIn::lockForUpdate()->findOrFail($meta->id);
        $note = isset($data['note']) && trim((string) $data['note']) !== '' ? trim((string) $data['note']) : null;
        $row->forceFill(['status' => 'answered', 'answer' => ['scale' => (int) $data['scale'], 'note' => $note], 'answer_source' => $source,
            'answered_by' => $actor->id, 'answered_at' => now(), 'revision' => $row->revision + 1])->save();
        // Ids and revisions only: the answer never enters the audit log.
        Audit::record('wellbeing.check_in_answered', $row, ['revision' => $row->revision - 1], ['revision' => $row->revision, 'answer_source' => $source]);

        return ['id' => (int) $row->id, 'status' => 'answered', 'revision' => $row->revision];
    }

    private function names(array $ids): array
    {
        return Students::whereIn('id', array_unique($ids))->with('user')->get()
            ->mapWithKeys(fn ($learner) => [(int) $learner->id => trim(($learner->user?->first_name ?? '').' '.($learner->user?->last_name ?? ''))])->all();
    }

    private function present(CheckIn $row, ?array $answer, array $names): array
    {
        return ['id' => (int) $row->id, 'learner_id' => (int) $row->learner_id, 'learner_name' => $names[(int) $row->learner_id] ?? __('Archived learner'),
            'prompt' => $row->prompt, 'due_on' => $row->due_on->toDateString(), 'status' => $row->status, 'group' => $row->group_id !== null,
            'answer_source' => $row->answer_source, 'answered_on' => $row->answered_at?->toDateString(),
            'scale' => $answer['scale'] ?? null, 'note' => $answer['note'] ?? null, 'revision' => $row->revision];
    }
}
