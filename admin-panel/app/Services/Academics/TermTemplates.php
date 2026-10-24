<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\SessionYear;
use App\Models\TermTemplate;
use App\Models\User;
use App\Policies\Academics\TermPolicy;
use App\Services\Platform\Audit\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TermTemplates
{
    public function save(User $actor, int $yearId, string $name): TermTemplate
    {
        app(TermPolicy::class)->authorise($actor, 'create');
        $name = Validator::make(['name' => $name], ['name' => ['required', 'string', 'max:120']])->validate()['name'];

        return DB::transaction(function () use ($actor, $yearId, $name) {
            $year = SessionYear::query()->lockForUpdate()->findOrFail($yearId);
            // Serialize template names within a school, even across source years.
            $year->school()->lockForUpdate()->firstOrFail();
            app(TermPolicy::class)->authorise($actor, 'create');
            if (TermTemplate::withTrashed()->where('name', $name)->exists()) {
                throw ValidationException::withMessages(['name' => 'That template name is already in use.']);
            }
            $rows = $year->terms()->orderBy('start_date')->orderBy('grade_from')->get()->map(fn ($term) => TermCalendar::data($term))->all();
            if ($rows === []) {
                throw ValidationException::withMessages(['session_year_id' => 'Add term dates before saving a template.']);
            }
            $calendar = app(TermCalendar::class);
            $rows = array_map(fn ($row) => $calendar->normalise($row), $rows);
            $calendar->validateRows($year, $rows);
            $template = TermTemplate::create(['name' => $name, 'base_year' => (int) CarbonImmutable::parse($year->start_date)->year, 'terms' => $rows]);
            Audit::record('academics.term_template_saved', $template, [], ['name' => $name, 'term_count' => count($rows)]);

            return $template;
        });
    }

    public function rows(TermTemplate $template, SessionYear $year): array
    {
        $shift = (int) CarbonImmutable::parse($year->start_date)->year - $template->base_year;
        $rows = [];
        foreach ($template->terms as $index => $row) {
            foreach (['starts_on', 'ends_on', 'marks_lock_on', 'reports_on'] as $key) {
                if (($row[$key] ?? null) === null) {
                    continue;
                }
                [$y, $m, $d] = array_map('intval', explode('-', $row[$key]));
                if (! checkdate($m, $d, $y + $shift)) {
                    throw ValidationException::withMessages(["terms.$index.$key" => 'A template date does not exist in the target year; review the calendar dates.']);
                }
                $row[$key] = sprintf('%04d-%02d-%02d', $y + $shift, $m, $d);
            }
            $rows[] = app(TermCalendar::class)->normalise($row);
        }

        return $rows;
    }

    public function apply(User $actor, int $id, int $yearId, bool $apply = false): array
    {
        app(TermPolicy::class)->authorise($actor, $apply ? 'create' : 'list');

        return DB::transaction(function () use ($actor, $id, $yearId, $apply) {
            $year = SessionYear::query()->lockForUpdate()->findOrFail($yearId);
            $template = TermTemplate::query()->lockForUpdate()->findOrFail($id);
            app(TermPolicy::class)->authorise($actor, $apply ? 'create' : 'list');
            $calendar = app(TermCalendar::class);
            $rows = $this->rows($template, $year);
            $existing = $year->terms()->lockForUpdate()->get()->map(fn ($term) => $calendar->normalise(TermCalendar::data($term)))->all();
            $new = array_values(array_filter($rows, fn ($row) => ! in_array($row, $existing, true)));
            $calendar->validateRows($year, $new, $existing);
            if ($apply && $new !== []) {
                foreach ($new as $row) {
                    $year->terms()->create($calendar->storage($row));
                }
                Audit::record('academics.term_template_applied', $template, [], ['session_year_id' => $yearId, 'term_count' => count($new)]);
            }

            return ['applied' => $apply, 'session_year_id' => $yearId, 'template_id' => $id,
                'created_count' => $apply ? count($new) : 0, 'would_create_count' => count($new),
                'skipped_count' => count($rows) - count($new), 'terms' => $rows];
        });
    }
}
