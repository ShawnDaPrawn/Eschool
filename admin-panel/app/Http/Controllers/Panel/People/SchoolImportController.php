<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\SchoolImportRequest;
use App\Models\Category;
use App\Models\Mediums;
use App\Models\SessionYear;
use App\Services\People\SchoolImports;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * /school-imports: homerooms and learners (with guardians) from a CSV, checked
 * before anything is written (Action Plan 0.11).
 * engineering/specs/people/school-imports.md.
 */
class SchoolImportController extends Controller
{
    public function __construct(private readonly SchoolImports $imports) {}

    public function index(SchoolImportRequest $request): View
    {
        $allowed = $this->imports->allowed($request->user());
        $kind = $request->validated('kind') ?? array_key_first($allowed);

        return $this->page($request, (string) $kind, null);
    }

    public function template(SchoolImportRequest $request, string $kind): Response
    {
        return response($this->imports->template($request->user(), $kind), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="navigateed-'.$kind.'.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /** Reads the file and checks every row; nothing is written. */
    public function check(SchoolImportRequest $request): View|Response
    {
        $kind = (string) $request->validated('kind');
        try {
            $csv = $this->csv($request);
            $check = $this->imports->check($request->user(), $kind, $csv, $this->mapping($request), $this->options($request));
        } catch (ValidationException $e) {
            return response($this->page($request, $kind, null, $e->errors(), $csv ?? ''), 422);
        }

        return $this->page($request, $kind, $check, [], $csv);
    }

    public function store(SchoolImportRequest $request): View|Response|RedirectResponse
    {
        $kind = (string) $request->validated('kind');
        $csv = (string) $request->validated('csv');
        $mapping = $this->mapping($request) ?? [];
        try {
            $counts = $this->imports->commit($request->user(), $kind, $csv, $mapping, $this->options($request), (string) $request->validated('fingerprint'));
        } catch (ValidationException $e) {
            try {
                $check = $this->imports->check($request->user(), $kind, $csv, $mapping, $this->options($request));
            } catch (ValidationException) {
                $check = null;
            }

            return response($this->page($request, $kind, $check, $e->errors(), $csv), 422);
        }

        $message = $kind === 'staff'
            ? __(':created staff added and :invited invited to set their password.', ['created' => $counts['created'], 'invited' => $counts['invited']])
            : ($kind === 'learners'
            ? __(':created learners imported, :linked guardian links made, :invited guardians invited and :lurits LURITS numbers recorded.', [
                'created' => $counts['created'], 'linked' => $counts['guardians_linked'], 'invited' => $counts['guardians_invited'], 'lurits' => $counts['lurits_recorded']])
                .($counts['lurits_claimed'] > 0 ? ' '.trans_choice(':count LURITS number is already registered to another learner and waits for Navigate Ed support.|:count LURITS numbers are already registered to other learners and wait for Navigate Ed support.', $counts['lurits_claimed']) : '')
                .($counts['lurits_kept'] > 0 ? ' '.trans_choice(':count learner was given a different LURITS number meanwhile, which is kept.|:count learners were given a different LURITS number meanwhile, which is kept.', $counts['lurits_kept']) : '')
            : __(':created homerooms imported.', ['created' => $counts['created']]));

        return redirect()->route('school-imports.index', ['kind' => $kind])->with('success', $message);
    }

    /**
     * @param  array<string, mixed>|null  $check
     * @param  array<string, list<string>>  $errors
     */
    private function page(SchoolImportRequest $request, string $kind, ?array $check, array $errors = [], string $csv = ''): View
    {
        $allowed = $this->imports->allowed($request->user());
        abort_unless(isset($allowed[$kind]), 403);

        return view('people.school-imports', [
            'kind' => $kind,
            'kinds' => array_keys($allowed),
            'fields' => $allowed[$kind]->fields(),
            'check' => $check,
            'csv' => $csv,
            'mediums' => Mediums::query()->orderBy('name')->pluck('name', 'id')->all(),
            'years' => SessionYear::query()->orderByDesc('start_date')->pluck('name', 'id')->all(),
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id')->all(),
            'problems' => collect($errors)->flatten()->map(fn ($problem) => (string) $problem)->values()->all(),
        ]);
    }

    private function csv(SchoolImportRequest $request): string
    {
        $file = $request->file('file');
        $csv = SchoolImports::normalise($file !== null ? (string) file_get_contents($file->getRealPath()) : (string) $request->validated('csv'));
        if ($csv === '') {
            throw ValidationException::withMessages(['csv' => __('Choose a CSV file, or paste its contents.')]);
        }

        return $csv;
    }

    /** @return array<string, string>|null null until the school has seen the suggested columns */
    private function mapping(SchoolImportRequest $request): ?array
    {
        if (! $request->boolean('mapped') || $request->file('file') !== null) {
            return null;
        }

        return array_map(fn ($header) => (string) $header, array_filter((array) $request->validated('mapping', []), fn ($header) => $header !== null && $header !== ''));
    }

    /** @return array<string, mixed> */
    private function options(SchoolImportRequest $request): array
    {
        return array_filter([
            'medium_id' => $request->validated('medium_id'),
            'session_year_id' => $request->validated('session_year_id'),
            'category_id' => $request->validated('category_id'),
            'starts_on' => $request->validated('starts_on'),
            'invite' => $request->boolean('invite'),
            'from_sasams' => $request->boolean('from_sasams'),
        ], fn ($value) => $value !== null && $value !== false);
    }
}
