<?php

declare(strict_types=1);

namespace App\Services\People;

use App\Imports\SchoolOnboarding\CsvSheet;
use App\Imports\SchoolOnboarding\HomeroomImport;
use App\Imports\SchoolOnboarding\LearnerImport;
use App\Imports\SchoolOnboarding\OnboardingImport;
use App\Models\Category;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * School onboarding imports (Action Plan 0.11): a CSV is read, its columns
 * matched to NavigateEd's fields (suggested from the header names, changeable),
 * every row checked, and then written in one go. Writing is refused if
 * anything differs from the check the school saw. Rows already in the school
 * are left alone, so the same file can be imported again.
 * engineering/specs/people/school-imports.md.
 */
class SchoolImports
{
    /** @return array<string, OnboardingImport> */
    public function kinds(): array
    {
        return ['homerooms' => new HomeroomImport, 'learners' => new LearnerImport];
    }

    public function kind(string $key): OnboardingImport
    {
        return $this->kinds()[$key] ?? throw ValidationException::withMessages(['kind' => __('Choose what to import.')]);
    }

    /** The kinds this person may import. */
    public function allowed(User $actor): array
    {
        return array_filter($this->kinds(), fn (OnboardingImport $import) => $actor->can($import->permission()));
    }

    /**
     * Matches each field to the header that names it, ignoring case, spaces,
     * underscores and dots.
     *
     * @param  list<string>  $headers
     * @return array<string, string> field => header
     */
    public function suggest(OnboardingImport $import, array $headers): array
    {
        $byName = [];
        foreach ($headers as $header) {
            $byName[self::plain($header)] ??= $header;
        }
        $mapping = [];
        foreach ($import->fields() as $field => $definition) {
            foreach ([$field, $definition['label'], ...$definition['aliases']] as $name) {
                $header = $byName[self::plain($name)] ?? null;
                if ($header !== null && ! in_array($header, $mapping, true)) {
                    $mapping[$field] = $header;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Reads and checks a file. With no mapping, the suggested one is used.
     *
     * @param  array<string, string>|null  $mapping
     * @param  array<string, mixed>  $options
     * @return array{headers: list<string>, mapping: array<string, string>, options: array<string, mixed>, rows: list<array<string, mixed>>, counts: array<string, int>, fingerprint: string}
     */
    public function check(User $actor, string $kind, string $csv, ?array $mapping, array $options): array
    {
        $import = $this->authorised($actor, $kind);
        $csv = self::normalise($csv);
        $sheet = CsvSheet::read($csv);
        $mapping = $mapping === null ? $this->suggest($import, $sheet->headers) : $this->mapping($import, $sheet, $mapping);
        $options = $this->options($import, $options);
        $missing = array_keys(array_filter($import->fields(), fn (array $field, string $key) => $field['required'] && ! isset($mapping[$key]), ARRAY_FILTER_USE_BOTH));
        $rows = $missing === [] ? $import->plan($sheet, $mapping, $options) : [];
        $counts = array_fill_keys(['create', 'exists', 'transfer', 'error'], 0);
        foreach ($rows as $row) {
            $counts[$row['status']]++;
        }

        return [
            'headers' => $sheet->headers, 'mapping' => $mapping, 'options' => $options, 'rows' => $rows, 'counts' => $counts,
            'missing' => array_map(fn (string $field) => $import->fields()[$field]['label'], $missing),
            'fingerprint' => $this->fingerprint($actor, $kind, $csv, $mapping, $options, $rows),
        ];
    }

    /**
     * Writes the rows the check marked "create", in one transaction.
     *
     * @param  array<string, string>  $mapping
     * @param  array<string, mixed>  $options
     * @return array<string, int>
     */
    public function commit(User $actor, string $kind, string $csv, array $mapping, array $options, string $fingerprint): array
    {
        $import = $this->authorised($actor, $kind);
        $csv = self::normalise($csv);

        return DB::transaction(function () use ($actor, $kind, $import, $csv, $mapping, $options, $fingerprint): array {
            // One import at a time per school, so two people cannot create the same learners.
            School::query()->whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $check = $this->check($actor, $kind, $csv, $mapping, $options);
            if (! hash_equals($check['fingerprint'], $fingerprint)) {
                throw ValidationException::withMessages(['fingerprint' => __('The school\'s records changed since this check. Check the file again, then import.')]);
            }
            if ($check['missing'] !== [] || $check['counts']['error'] > 0) {
                throw ValidationException::withMessages(['rows' => __('Fix the rows with problems first, or remove them from the file.')]);
            }
            $counts = $import->commit($actor, $check['rows'], $check['options']);
            Audit::record('people.onboarding_import_committed', null, [], [
                'kind' => $kind, 'file_sha256' => hash('sha256', $csv), 'rows' => count($check['rows']),
                'skipped' => $check['counts']['exists'] + $check['counts']['transfer'], 'counts' => $counts, 'by' => $actor->id,
            ]);
            if ($kind === 'learners' && ($counts['created'] ?? 0) > 0) {
                Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'academics.learner_changed'));
            }

            return $counts;
        });
    }

    /** The header row for a kind's template file. */
    public function template(User $actor, string $kind): string
    {
        $import = $this->authorised($actor, $kind);

        return implode(',', array_map(fn (array $field) => '"'.str_replace('"', '""', $field['label']).'"', $import->fields()))."\n";
    }

    private function authorised(User $actor, string $kind): OnboardingImport
    {
        $import = $this->kind($kind);
        abort_unless($actor->can($import->permission()), 403);

        return $import;
    }

    /**
     * @param  array<string, string>  $mapping
     * @return array<string, string>
     */
    private function mapping(OnboardingImport $import, CsvSheet $sheet, array $mapping): array
    {
        $chosen = [];
        foreach ($import->fields() as $field => $definition) {
            $header = $mapping[$field] ?? '';
            if ($header === '') {
                continue;
            }
            if (! in_array($header, $sheet->headers, true)) {
                throw ValidationException::withMessages(['mapping' => __('The file has no column ":column".', ['column' => $header])]);
            }
            if (in_array($header, $chosen, true)) {
                throw ValidationException::withMessages(['mapping' => __('Column ":column" is chosen for two fields.', ['column' => $header])]);
            }
            $chosen[$field] = $header;
        }

        return $chosen;
    }

    /**
     * The import's settings, with defaults: learners go into the current year
     * from today (or its first day), in the school's first category.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function options(OnboardingImport $import, array $options): array
    {
        if ($import instanceof HomeroomImport) {
            return isset($options['medium_id']) && $options['medium_id'] !== '' ? ['medium_id' => (int) $options['medium_id']] : [];
        }
        $year = isset($options['session_year_id']) && $options['session_year_id'] !== ''
            ? SessionYear::query()->find((int) $options['session_year_id'])
            : SessionYear::query()->where('default', 1)->first();
        if ($year === null) {
            throw ValidationException::withMessages(['session_year_id' => __('Set up the school year first.')]);
        }
        $category = isset($options['category_id']) && $options['category_id'] !== ''
            ? Category::query()->find((int) $options['category_id'])
            : Category::query()->orderBy('id')->first();
        if ($category === null) {
            throw ValidationException::withMessages(['category_id' => __('Add a learner category first.')]);
        }
        $first = CarbonImmutable::parse($year->start_date)->toDateString();
        $last = CarbonImmutable::parse($year->end_date)->toDateString();
        $startsOn = (string) ($options['starts_on'] ?? '') !== '' ? (string) $options['starts_on'] : min(max(now()->toDateString(), $first), $last);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $startsOn) || $startsOn < $first || $startsOn > $last) {
            throw ValidationException::withMessages(['starts_on' => __('Choose a start date within :year.', ['year' => $year->name])]);
        }

        return ['session_year_id' => (int) $year->id, 'category_id' => (int) $category->id, 'starts_on' => $startsOn, 'invite' => ! empty($options['invite'])];
    }

    /**
     * @param  array<string, string>  $mapping
     * @param  array<string, mixed>  $options
     * @param  list<array<string, mixed>>  $rows
     */
    private function fingerprint(User $actor, string $kind, string $csv, array $mapping, array $options, array $rows): string
    {
        $state = array_map(fn (array $row) => [$row['line'], $row['status'], $row['data'] ?? null], $rows);

        return hash_hmac('sha256', json_encode([$actor->id, app(CurrentSchool::class)->id(), $kind, hash('sha256', $csv), $mapping, $options, $state], JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    /**
     * The file as the page holds it and the browser sends it back: UTF-8
     * (spreadsheet programs often save Windows-1252), LF line ends (forms send
     * CRLF) and trimmed (requests are), so none of these changes the check.
     */
    public static function normalise(string $csv): string
    {
        if (str_starts_with($csv, "\xEF\xBB\xBF")) {
            $csv = substr($csv, 3);
        }
        if (! mb_check_encoding($csv, 'UTF-8')) {
            $csv = mb_convert_encoding($csv, 'UTF-8', 'Windows-1252');
        }

        return trim(str_replace(["\r\n", "\r"], "\n", $csv));
    }

    private static function plain(string $name): string
    {
        return mb_strtolower((string) preg_replace('/[\s_.\-]+/', '', $name));
    }
}
