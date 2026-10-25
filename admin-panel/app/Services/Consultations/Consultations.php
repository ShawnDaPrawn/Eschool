<?php

declare(strict_types=1);

namespace App\Services\Consultations;

use App\Events\Consultations\ConsultationCountsChanged;
use App\Events\Consultations\ConsultationOpened;
use App\Models\Consultations\Candidate;
use App\Models\Consultations\Consultation;
use App\Models\Consultations\Option;
use App\Models\Consultations\Participation;
use App\Models\Consultations\Question;
use App\Models\Consultations\Response;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class Consultations
{
    public function __construct(private ConsultationAccess $access, private ConsultationOperations $operations) {}

    private function revision(Consultation $row, array $input): void
    {
        abort_unless((int) $row->revision === (int) $input['revision'], 409, 'The consultation changed. Refresh and review it before saving.');
    }

    private function receipt(Consultation $row): array
    {
        return ['id' => (int) $row->id, 'revision' => (int) $row->revision, 'status' => $row->status];
    }

    public function save(User $actor, ?int $id, array $input): array
    {
        $this->access->manager($actor, true);
        $data = Validator::make($input, ConsultationRules::configuration())->validate();
        $this->access->year((int) $data['session_year_id'], true);
        foreach ($data['questions'] as $question) {
            $choices = $question['options'] ?? [];
            abort_unless($question['kind'] === 'text' ? $choices === [] : count($choices) >= 2, 422, 'Choice questions need at least two options; text questions have no options.');
            abort_if($question['kind'] === 'multiple' && (int) ($question['max_choices'] ?? 1) > count($choices), 422);
            abort_if($data['type'] === 'election' && $question['kind'] === 'text', 422, 'An election ballot needs candidate choices.');
            foreach ($choices as $choice) {
                abort_if($data['type'] !== 'election' && ! empty($choice['statement']), 422);
            }
        }

        return $this->operations->once($actor, 'configuration', ['id' => $id] + $data, function () use ($actor, $data) {
            $this->access->manager($actor, true);
            $this->access->year((int) $data['session_year_id'], true);
        }, function () use ($actor, $id, $data) {
            $row = $id === null ? new Consultation : Consultation::findOrFail($id);
            if ($id !== null) {
                $this->access->year((int) $row->session_year_id, true);
            }
            abort_unless((int) ($row->revision ?? 0) === (int) $data['revision'] && ($id === null || $row->status === 'draft'), 409, 'Published configuration is retained. Create a new consultation or refresh the draft.');
            $audience = $this->access->audience($actor, $data['audience'], (int) $data['session_year_id']);
            $row->fill(['session_year_id' => $data['session_year_id'], 'created_by' => $row->created_by ?? $actor->id,
                'type' => $data['type'], 'title' => trim($data['title']), 'instructions' => trim($data['instructions'] ?? ''),
                'audience' => $audience, 'anonymous' => $data['anonymous'], 'results_visibility' => $data['results_visibility'],
                'opens_at' => CarbonImmutable::parse($data['opens_at'])->utc(), 'closes_at' => CarbonImmutable::parse($data['closes_at'])->utc(),
                'status' => 'draft', 'revision' => (int) ($row->revision ?? 0) + 1])->save();
            Candidate::where('consultation_id', $row->id)->delete();
            Option::where('consultation_id', $row->id)->delete();
            Question::where('consultation_id', $row->id)->delete();
            foreach ($data['questions'] as $position => $question) {
                $q = Question::create(['consultation_id' => $row->id, 'position' => $position, 'kind' => $question['kind'], 'label' => trim($question['label']),
                    'required' => $question['required'], 'max_choices' => $question['kind'] === 'single' ? 1 : (int) ($question['max_choices'] ?? 1)]);
                foreach ($question['options'] ?? [] as $index => $choice) {
                    $option = Option::create(['consultation_id' => $row->id, 'question_id' => $q->id, 'position' => $index, 'label' => trim($choice['label'])]);
                    if ($data['type'] === 'election') {
                        Candidate::create(['consultation_id' => $row->id, 'option_id' => $option->id, 'statement' => trim($choice['statement'] ?? '')]);
                    }
                }
            }
            $this->operations->event($actor, $row, 'saved', null, $this->questions($row));

            return $this->receipt($row);
        });
    }

    public function publish(User $actor, int $id, array $input): array
    {
        $this->access->manager($actor, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']])->validate();

        return $this->operations->once($actor, 'publish', ['id' => $id] + $data, function () use ($actor, $id) {
            $this->access->manager($actor, true);
            $this->access->year((int) Consultation::findOrFail($id)->session_year_id, true);
        }, function () use ($actor, $id, $data) {
            $row = Consultation::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->revision($row, $data);
            abort_unless($row->status === 'draft' && $row->closes_at->isFuture(), 409, 'Only an unexpired draft can be published.');
            abort_unless(Question::where('consultation_id', $id)->exists(), 422);
            // Resolve now to reject a missing/inactive audience, never freeze a voter list.
            abort_unless($row->opens_at->isFuture() || $this->access->recipients($row) !== [], 422, 'The consultation needs a current eligible audience.');
            $row->status = $row->opens_at->isFuture() ? 'scheduled' : 'open';
            $row->revision++;
            $row->save();
            $this->operations->event($actor, $row, 'published', null, $this->questions($row));
            if ($row->status === 'open') {
                Outbox::record(new ConsultationOpened((int) $row->school_id, (int) $row->id, (int) $row->revision));
            }

            return $this->receipt($row);
        });
    }

    public function end(User $actor, int $id, string $action, array $input): array
    {
        abort_unless(in_array($action, ['close', 'cancel'], true), 422);
        $this->access->manager($actor, true);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:2000']])->validate();

        return $this->operations->once($actor, $action, ['id' => $id] + $data, function () use ($actor, $id) {
            $this->access->manager($actor, true);
            $this->access->year((int) Consultation::findOrFail($id)->session_year_id, true);
        }, function () use ($actor, $id, $action, $data) {
            $row = Consultation::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->revision($row, $data);
            abort_if(in_array($row->status, ['closed', 'cancelled'], true), 409, 'A closed or cancelled consultation cannot be reopened.');
            $row->status = $action === 'close' ? 'closed' : 'cancelled';
            $row->ended_at = now();
            $row->revision++;
            $row->save();
            $this->operations->event($actor, $row, $action, trim($data['reason']));

            return $this->receipt($row);
        });
    }

    private function questions(Consultation $row): array
    {
        $options = Option::where('consultation_id', $row->id)->orderBy('position')->get()->groupBy('question_id');
        $candidates = Candidate::where('consultation_id', $row->id)->get()->keyBy('option_id');

        return Question::where('consultation_id', $row->id)->orderBy('position')->get()->map(fn ($q) => [
            'id' => (int) $q->id, 'kind' => $q->kind, 'label' => $q->label, 'required' => $q->required, 'max_choices' => $q->max_choices,
            'options' => ($options->get($q->id) ?? collect())->map(fn ($o) => ['id' => (int) $o->id, 'label' => $o->label, 'statement' => $candidates->get($o->id)?->statement])->all(),
        ])->all();
    }

    private function summary(Consultation $row): array
    {
        return $this->receipt($row) + ['type' => $row->type, 'title' => $row->title, 'instructions' => $row->instructions,
            'session_year_id' => (int) $row->session_year_id, 'anonymous' => $row->anonymous, 'results_visibility' => $row->results_visibility,
            'opens_at' => $row->opens_at->toISOString(), 'closes_at' => $row->closes_at->toISOString(), 'ended_at' => $row->ended_at?->toISOString(),
            'writable' => app(ModuleGate::class)->writable('consultations') && (int) $row->session_year_id === (int) schoolSetting('session_year')];
    }

    public function listingItem(User $actor, Consultation $row, bool $managed): array
    {
        abort_unless((int) $row->school_id === app(CurrentSchool::class)->id(), 404);
        $managed ? $this->access->manager($actor) : $this->access->voter($actor, $row);

        return $this->summary($row);
    }

    public function configuration(User $actor, int $id): array
    {
        $this->access->manager($actor);
        $row = Consultation::findOrFail($id);

        return $this->summary($row) + ['audience' => $row->audience, 'questions' => $this->questions($row)];
    }

    public function ballot(User $actor, int $id): array
    {
        $this->access->actor($actor);
        $row = Consultation::findOrFail($id);
        $this->access->voter($actor, $row);
        $open = in_array($row->status, ['open', 'scheduled'], true) && $row->opens_at->lessThanOrEqualTo(now()) && $row->closes_at->isFuture();

        return $this->summary($row) + ['open' => $open, 'participated' => Participation::where('consultation_id', $id)->where('user_id', $actor->id)->exists(),
            'questions' => $this->questions($row)];
    }

    private function answers(Consultation $row, array $answers): array
    {
        $data = Validator::make(['answers' => $answers], [
            'answers' => ['present', 'array', 'max:20'], 'answers.*' => ['required', 'array:question_id,option_ids,text'],
            'answers.*.question_id' => ['required', 'integer', 'min:1', 'distinct'],
            'answers.*.option_ids' => ['sometimes', 'array', 'max:50'], 'answers.*.option_ids.*' => ['integer', 'min:1', 'distinct'],
            'answers.*.text' => ['nullable', 'string', 'max:2000'],
        ])->validate()['answers'];
        $byQuestion = collect($data)->keyBy('question_id');
        $questions = Question::where('consultation_id', $row->id)->get();
        abort_unless(array_diff($byQuestion->keys()->all(), $questions->pluck('id')->all()) === [], 422);
        $result = [];
        foreach ($questions as $q) {
            $input = $byQuestion->get($q->id, []);
            $ids = array_map('intval', $input['option_ids'] ?? []);
            $text = trim($input['text'] ?? '');
            if ($q->kind === 'text') {
                abort_if($ids !== [] || ($q->required && $text === ''), 422, 'Complete each required question.');
                if ($text !== '') {
                    $result[(string) $q->id] = ['text' => $text];
                }
            } else {
                abort_if($text !== '' || ($q->required && $ids === []) || count($ids) > $q->max_choices, 422, 'Choose the permitted number of options.');
                $valid = Option::where('consultation_id', $row->id)->where('question_id', $q->id)->pluck('id')->all();
                abort_unless(array_diff($ids, $valid) === [], 422, 'Choose options from this question.');
                if ($ids !== []) {
                    sort($ids);
                    $result[(string) $q->id] = ['option_ids' => array_values($ids)];
                }
            }
        }

        return $result;
    }

    /** No actor-linked operation receipt, timestamp or audit is written for a vote. */
    public function vote(User $actor, int $id, array $answers): array
    {
        $this->access->actor($actor, true);

        return DB::transaction(function () use ($actor, $id, $answers) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $row = Consultation::whereKey($id)->lockForUpdate()->firstOrFail();
            $fresh = $this->access->voter($actor, $row, true);
            abort_if(Participation::where('consultation_id', $id)->where('user_id', $fresh->id)->exists(), 409, 'Your response is already recorded. You can participate only once.');
            abort_unless(in_array($row->status, ['scheduled', 'open'], true) && $row->opens_at->lessThanOrEqualTo(now()) && $row->closes_at->isFuture(), 409, 'This consultation is not open for responses.');
            $valid = $this->answers($row, $answers);
            Participation::create(['consultation_id' => $id, 'user_id' => $fresh->id]);
            Response::create(['id' => (string) Str::uuid(), 'consultation_id' => $id, 'respondent_user_id' => $row->anonymous ? null : $fresh->id, 'answers' => $valid]);
            if ($row->results_visibility === 'public_live') {
                Outbox::record(new ConsultationCountsChanged((int) $row->school_id, $id, Response::where('consultation_id', $id)->count()));
            }

            return ['participated' => true];
        }, 3);
    }

    /** Trusted school-context clock, also usable for outbox/scheduler recovery. */
    public function advance(): int
    {
        $context = app(CurrentSchool::class);
        $context->school()?->refresh();
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating() && $context->school()?->isActive(), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        if (! $gate->writable('consultations')) {
            return 0;
        }
        $changed = 0;
        Consultation::whereIn('status', ['scheduled', 'open'])->orderBy('id')->chunkById(100, function ($rows) use (&$changed) {
            foreach ($rows as $candidate) {
                $changed += DB::transaction(function () use ($candidate) {
                    School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
                    $row = Consultation::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                    if (! in_array($row->status, ['scheduled', 'open'], true)) {
                        return 0;
                    }
                    if ($row->closes_at->lessThanOrEqualTo(now())) {
                        $row->status = 'closed';
                        $row->ended_at = $row->closes_at;
                        $row->revision++;
                        $row->save();
                        $this->operations->event(null, $row, 'clock_closed');

                        return 1;
                    }
                    if ($row->status === 'scheduled' && (int) $row->session_year_id === (int) schoolSetting('session_year') && $row->opens_at->lessThanOrEqualTo(now())) {
                        $row->status = 'open';
                        $row->revision++;
                        $row->save();
                        $this->operations->event(null, $row, 'clock_opened');
                        Outbox::record(new ConsultationOpened((int) $row->school_id, (int) $row->id, (int) $row->revision));

                        return 1;
                    }

                    return 0;
                }, 3);
            }
        });

        return $changed;
    }

    public function responses(User $actor, int $id, int $page = 1): array
    {
        $this->access->manager($actor);
        abort_unless($page >= 1 && $page <= 100000, 422);
        $row = Consultation::findOrFail($id);
        abort_unless($row->status === 'closed' || (! in_array($row->status, ['draft', 'cancelled'], true) && $row->closes_at->lessThanOrEqualTo(now())), 403, 'Individual responses are available only after close.');
        $rows = Response::where('consultation_id', $id)->orderBy('id')->paginate(50, ['id', 'respondent_user_id', 'answers'], 'page', $page);
        $people = $row->anonymous ? collect() : User::whereIn('id', $rows->pluck('respondent_user_id')->filter()->all())->get(['id', 'first_name', 'last_name'])->keyBy('id');

        return ['items' => $rows->map(fn ($response) => [
            'respondent_user_id' => $row->anonymous ? null : (int) $response->respondent_user_id,
            'respondent_name' => $row->anonymous ? null : ($people->get($response->respondent_user_id)?->full_name ?? 'Former participant'),
            'answers' => collect($response->answers)->map(fn ($answer, $question) => ['question_id' => (int) $question,
                'option_ids' => array_values($answer['option_ids'] ?? []), 'text' => $answer['text'] ?? null])->values()->all(),
        ])->all(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function results(User $actor, int $id, bool $manager = false): array
    {
        return DB::transaction(fn () => $this->resultProjection($actor, $id, $manager));
    }

    private function resultProjection(User $actor, int $id, bool $manager): array
    {
        $manager ? $this->access->manager($actor) : $this->access->actor($actor);
        $row = Consultation::whereKey($id)->sharedLock()->firstOrFail();
        if (! $manager) {
            $this->access->voter($actor, $row);
        }
        abort_if(in_array($row->status, ['draft', 'cancelled'], true), 404);
        $closed = $row->status === 'closed' || $row->closes_at->lessThanOrEqualTo(now());
        abort_unless($closed || $row->results_visibility === 'public_live', 403, 'Private results are available after the consultation closes.');
        $questions = $this->questions($row);
        $count = Response::where('consultation_id', $id)->count();
        abort_unless($count === Participation::where('consultation_id', $id)->count(), 409, 'The retained ballot totals need review.');
        $totals = [];
        foreach ($questions as $q) {
            $totals[$q['id']] = ['answered' => 0, 'options' => array_fill_keys(array_column($q['options'], 'id'), 0)];
        }
        foreach (Response::where('consultation_id', $id)->orderBy('id')->cursor() as $response) {
            foreach ($response->answers as $qid => $answer) {
                abort_unless(isset($totals[$qid]), 409, 'Stored ballot integrity could not be verified.');
                $totals[$qid]['answered']++;
                foreach ($answer['option_ids'] ?? [] as $option) {
                    abort_unless(array_key_exists($option, $totals[$qid]['options']), 409, 'Stored ballot integrity could not be verified.');
                    $totals[$qid]['options'][$option]++;
                }
                // Free text is never included in a participant/public results projection.
                // Individual text/named answers use the separate paged manager reader after close.
            }
        }

        return ['id' => $id, 'closed' => $closed, 'anonymous' => $row->anonymous, 'response_count' => $count,
            'questions' => array_map(fn ($q) => $q + ['answered' => $totals[$q['id']]['answered'],
                'counts' => array_map(fn ($option) => ['option_id' => $option['id'], 'count' => $totals[$q['id']]['options'][$option['id']]], $q['options'])], $questions)];
    }
}
