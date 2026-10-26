<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Event;
use App\Models\Holiday;
use App\Models\People\Enrolment;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Attendance\RegisterQueries;
use App\Services\Academics\ReleasedMarks;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

/** Fixed sources only: approved school documents, calendar, own released facts. */
class AssistanceContext
{
    public function minimize(User $actor, int $learner, string $text): string
    {
        $person = app(AssistanceAccess::class)->family($actor, $learner);
        $child = User::findOrFail($person->user_id);
        $values = [];
        foreach ([$actor->fresh(), $child] as $user) {
            foreach ([$user->first_name, $user->last_name, trim($user->first_name.' '.$user->last_name), $user->email, $user->mobile] as $value) {
                if (is_string($value) && mb_strlen($value) >= 3) {
                    $values[] = $value;
                }
            }
        }
        usort($values, fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach (array_unique($values) as $value) {
            $text = preg_replace('/(?<![\pL\pN])'.preg_quote($value, '/').'(?![\pL\pN])/iu', '[person]', $text);
        }

        return $text;
    }

    private function hash(array $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public function answer(User $actor, int $learner, array $input): array
    {
        $data = Validator::make($input, ['documents' => ['present', 'array', 'max:5'], 'documents.*' => ['uuid', 'distinct'],
            'include_calendar' => ['required', 'boolean'], 'include_records' => ['required', 'boolean'],
            'language' => ['required', 'string', 'in:'.implode(',', config('assistance.languages'))],
            'question' => ['required', 'string', 'max:2000']])->validate();
        app(AssistanceAccess::class)->family($actor, $learner);
        abort_if(trim($data['question']) === '' || ! mb_check_encoding($data['question'], 'UTF-8') || str_contains($data['question'], "\0"), 422);
        $manifest = [];
        $sources = [];
        $citations = [];
        $add = function (string $kind, array $locator, array $body, string $title, ?array $providerBody = null) use (&$manifest, &$sources, &$citations, $actor, $learner): void {
            $token = 's'.(count($manifest) + 1);
            $manifest[] = ['kind' => $kind, 'locator' => $locator, 'sha256' => $this->hash($body)];
            $sources[] = ['source' => $token, 'text' => $this->minimize($actor, $learner, json_encode($providerBody ?? $body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))];
            $citations[] = ['token' => $token, 'kind' => $kind, 'title' => $title, 'locator' => $locator];
        };
        foreach ($data['documents'] as $id) {
            $doc = app(KnowledgeDocuments::class)->familyRead($actor, $learner, $id);
            $add('document', ['id' => $id], ['title' => $doc->title, 'markdown' => $doc->markdown, 'reference' => $doc->source_reference], $doc->title);
        }
        $year = (int) schoolSetting('session_year');
        $today = app(AcademicAuthority::class)->today();
        if ($data['include_calendar']) {
            $add('calendar', ['year' => $year, 'on_date' => $today], $this->calendar($learner, $year, $today), 'School calendar');
        }
        if ($data['include_records']) {
            $released = app(ReleasedMarks::class);
            $periods = array_values(array_filter($released->periods($actor, $learner)['periods'], fn ($period) => $period['session_year_id'] === $year));
            abort_if(count($periods) > 12, 422, 'Choose a smaller academic record selection.');
            foreach ($periods as $period) {
                $marks = $released->list($actor, $learner, $year, $period['term_id']);
                abort_if(count($marks['results']) > 20, 422, 'Choose a smaller academic record selection.');
                // Internal release/learner/grade IDs and audit timestamps remain in
                // the immutable source hash; only the reviewed facts leave it.
                $facts = array_map(fn ($row) => array_intersect_key($row, array_flip(['subject_name', 'provisional', 'percentage_bp'])), $marks['results']);
                $add('released_marks', ['year' => $year, 'term_id' => $period['term_id']], ['results' => $marks['results']], 'Your child’s released results: '.($period['term_name'] ?? $period['year_name']), ['results' => $facts]);
            }
            $attendance = app(RegisterQueries::class)->today($actor, $learner);
            $add('attendance', ['on_date' => $today], ['on_date' => $today, 'entries' => array_map(fn ($row) => ['kind' => $row['kind'], 'state' => $row['state']], $attendance['entries'])], 'Your child’s attendance today');
        }
        abort_if($sources === [], 422, 'Select an approved school source.');
        $prepared = json_encode(['task' => 'answer', 'language' => $data['language'], 'question' => $this->minimize($actor, $learner, trim($data['question'])),
            'sources' => $sources], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        abort_if(strlen($prepared) > 24000, 422, 'Select fewer or shorter school documents.');
        app(AssistanceAccess::class)->family($actor, $learner);

        return ['request' => $data, 'manifest' => $manifest, 'input' => $prepared, 'citations' => $citations];
    }

    private function calendar(int $learner, int $year, string $today): array
    {
        $enrolment = Enrolment::where('learner_id', $learner)->where('session_year_id', $year)->onDate($today)->with('homeroom.class')->orderByDesc('starts_on')->firstOrFail();
        $grade = $enrolment->homeroom?->class?->grade_level_id;
        abort_unless($grade !== null, 404);
        $until = CarbonImmutable::parse($today)->addDays(30)->toDateString();

        return ['terms' => Term::where('session_year_id', $year)->forGrade((int) $grade)->orderBy('start_date')->limit(12)->get()->map(fn ($row) => ['name' => $row->name, 'starts_on' => $row->start_date?->toDateString(), 'ends_on' => $row->end_date?->toDateString()])->all(),
            'holidays' => Holiday::where('session_year_id', $year)->whereBetween('date', [$today, $until])->orderBy('date')->limit(30)->get(['title', 'date'])->toArray(),
            'events' => Event::where('session_year_id', $year)->whereDoesntHave('multipleEvent')->whereBetween('start_date', [$today, $until])->orderBy('start_date')->limit(30)->get(['title', 'start_date'])->toArray()];
    }

    public function translation(User $actor, array $input, bool $office): array
    {
        $data = Validator::make($input, ['message_id' => ['required', 'uuid'], 'language' => ['required', 'string', 'in:'.implode(',', config('assistance.languages'))]])->validate();
        $message = app(SchoolSupportThreads::class)->message($actor, $data['message_id'], $office);
        $thread = app(AssistanceAccess::class)->thread($actor, $message->thread_id, false, $office);
        // The authorised family supplies minimisation context even for an assigned office reader.
        $parent = User::findOrFail($thread->parent_user_id);
        $text = $this->minimize($parent, (int) $thread->learner_id, $message->text);
        // The exact authorised original author can differ from this reader.
        $author = User::findOrFail($message->author_id);
        foreach ([$actor->first_name, $actor->last_name, $actor->email, $author->first_name, $author->last_name, $author->email, $author->mobile] as $value) {
            if (is_string($value) && strlen($value) >= 3) {
                $text = preg_replace('/(?<![\pL\pN])'.preg_quote($value, '/').'(?![\pL\pN])/iu', '[person]', $text);
            }
        }

        return ['learner' => (int) $thread->learner_id, 'request' => $data,
            'manifest' => [['kind' => 'support_message', 'locator' => ['id' => $message->id], 'sha256' => $message->content_hash]],
            'input' => json_encode(['task' => 'translate', 'language' => $data['language'], 'source' => 's1', 'original_language' => $message->language, 'text' => $text], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'citations' => [['token' => 's1', 'kind' => 'support_message', 'title' => 'Original message', 'locator' => ['id' => $message->id]]]];
    }
}
