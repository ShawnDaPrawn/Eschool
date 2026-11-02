<?php

declare(strict_types=1);

namespace App\Services\EarlyYears;

use App\Models\EarlyYears\DiaryEntry;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The early years daily diary (Action Plan 4.3): per learner per day, meals,
 * rest, mood, toileting and a short note, entered for a whole class at once.
 * Only staff allocated to the class write it; the note is encrypted.
 */
class DailyDiary
{
    public const CHOICES = [
        'meals' => ['all' => 'Ate everything', 'most' => 'Ate most', 'some' => 'Ate a little', 'none' => 'Did not eat'],
        'rest' => ['slept' => 'Slept', 'rested' => 'Rested quietly', 'none' => 'Did not rest'],
        'mood' => ['happy' => 'Happy', 'settled' => 'Settled', 'quiet' => 'Quiet', 'unsettled' => 'Unsettled'],
        'toileting' => ['independent' => 'Went on their own', 'helped' => 'Needed help or a reminder', 'nappy' => 'Nappy changed', 'accident' => 'Had an accident'],
    ];

    public const NOTE_MAX = 500;

    public function __construct(private EarlyYearsAuthority $authority) {}

    private function date(string $date): string
    {
        $year = $this->authority->year();
        $from = substr((string) $year->start_date, 0, 10);
        $to = substr((string) $year->end_date, 0, 10);
        abort_unless($date <= $this->authority->today(), 422, __('The diary cannot be filled in for a day that has not happened yet.'));
        abort_unless($year->start_date !== null && $date >= $from && $date <= $to, 422, __('Choose a day in the current school year.'));

        return $date;
    }

    /** The class's diary for one day: every learner enrolled today, with their entry if there is one. */
    public function sheet(User $actor, int $sectionId, string $date): array
    {
        $section = $this->authority->classRead($actor, $sectionId);
        Validator::make(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();
        $date = $this->date($date);
        $learners = $this->authority->roster($sectionId);
        $entries = DiaryEntry::whereIn('learner_id', $learners->pluck('id'))->whereDate('entry_date', $date)->get()->keyBy('learner_id');
        $rows = $learners->map(function (Students $learner) use ($entries): array {
            $entry = $entries->get($learner->id);

            return ['learner_id' => (int) $learner->id, 'name' => (string) $learner->user?->full_name, 'meals' => $entry?->meals, 'rest' => $entry?->rest,
                'mood' => $entry?->mood, 'toileting' => $entry?->toileting, 'note' => $entry?->note, 'revision' => (int) ($entry->revision ?? 0)];
        })->all();
        $this->authority->classRead($actor, $sectionId);

        return ['section' => $section, 'date' => $date, 'rows' => $rows];
    }

    /**
     * Save a class's diary for one day. The "everyone" choices fill each
     * learner's blank fields, so a whole class can be done in one go. A row
     * whose entry changed since it was opened stops the whole save.
     *
     * @return array{saved: int}
     */
    public function save(User $actor, int $sectionId, array $input): array
    {
        $this->authority->classWrite($actor, $sectionId);
        $choice = fn (string $field) => ['nullable', 'string', Rule::in(array_keys(self::CHOICES[$field]))];
        $data = Validator::make($input, [
            'date' => ['required', 'date_format:Y-m-d'],
            'everyone' => ['sometimes', 'array:meals,rest,mood,toileting'],
            'everyone.meals' => $choice('meals'), 'everyone.rest' => $choice('rest'), 'everyone.mood' => $choice('mood'), 'everyone.toileting' => $choice('toileting'),
            'rows' => ['required', 'array', 'min:1', 'max:200'],
            'rows.*' => ['array:learner_id,meals,rest,mood,toileting,note,revision'],
            'rows.*.learner_id' => ['required', 'integer', 'min:1', 'distinct'],
            'rows.*.meals' => $choice('meals'), 'rows.*.rest' => $choice('rest'), 'rows.*.mood' => $choice('mood'), 'rows.*.toileting' => $choice('toileting'),
            'rows.*.note' => ['nullable', 'string', 'max:'.self::NOTE_MAX],
            'rows.*.revision' => ['required', 'integer', 'min:0'],
        ])->validate();
        $date = $this->date($data['date']);
        $everyone = array_filter($data['everyone'] ?? [], fn ($value) => $value !== null && $value !== '');

        return DB::transaction(function () use ($actor, $sectionId, $data, $date, $everyone): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $this->authority->classWrite($actor, $sectionId);
            $roster = $this->authority->roster($sectionId)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $year = (int) $this->authority->year()->id;
            $saved = 0;
            foreach ($data['rows'] as $row) {
                $learner = (int) $row['learner_id'];
                abort_unless(in_array($learner, $roster, true), 404);
                $values = [];
                foreach (array_keys(self::CHOICES) as $field) {
                    $value = ($row[$field] ?? null) === '' ? null : ($row[$field] ?? null);
                    $values[$field] = $value ?? ($everyone[$field] ?? null);
                }
                $note = trim((string) ($row['note'] ?? ''));
                $values['note'] = $note === '' ? null : $note;
                $entry = DiaryEntry::where('learner_id', $learner)->whereDate('entry_date', $date)->lockForUpdate()->first();
                abort_unless((int) ($entry->revision ?? 0) === (int) $row['revision'], 409, __('Someone else changed this diary while you were filling it in. Reload the page and try again.'));
                $blank = array_filter($values, fn ($value) => $value !== null) === [];
                if ($entry === null && $blank) {
                    continue;
                }
                if ($entry !== null && array_intersect_key($entry->only(array_keys($values)), $values) == $values) {
                    continue;
                }
                $entry ??= new DiaryEntry(['learner_id' => $learner, 'entry_date' => $date, 'recorded_by' => $actor->id, 'revision' => 0]);
                $entry->fill($values + ['class_section_id' => $sectionId, 'session_year_id' => $year, 'updated_by' => $actor->id, 'revision' => (int) $entry->revision + 1])->save();
                Audit::record('early_years.diary_saved', $entry, [], ['learner_id' => $learner, 'entry_date' => $date, 'revision' => $entry->revision]);
                $saved++;
            }
            $this->authority->classWrite($actor, $sectionId);

            return ['saved' => $saved];
        });
    }
}
